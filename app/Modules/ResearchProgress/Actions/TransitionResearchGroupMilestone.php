<?php

namespace App\Modules\ResearchProgress\Actions;

use App\Enums\ResearchMilestoneStatus;
use App\Models\ResearchClassGroup;
use App\Models\ResearchGroupMilestone;
use App\Models\ResearchGroupMilestoneEvent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionResearchGroupMilestone
{
    public function execute(
        User $actor,
        ResearchGroupMilestone $milestone,
        ResearchMilestoneStatus $target,
        ?string $reason = null,
        ?string $remarks = null,
        bool $overrideOrder = false,
        bool $directCompletion = false,
        ?string $ipAddress = null,
    ): ResearchGroupMilestone {
        return DB::transaction(function () use ($actor, $milestone, $target, $reason, $remarks, $overrideOrder, $directCompletion, $ipAddress): ResearchGroupMilestone {
            $locked = ResearchGroupMilestone::query()
                ->with(['definition', 'group.researchClass'])
                ->whereKey($milestone->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $group = ResearchClassGroup::query()->whereKey($locked->research_class_group_id)->lockForUpdate()->firstOrFail();

            if (! $actor->can('manage', $locked)) {
                throw new AuthorizationException;
            }

            $from = $locked->status;
            $reason = $this->clean($reason, 2000);
            $remarks = $this->clean($remarks, 4000);

            if ($from === $target) {
                throw ValidationException::withMessages(['status' => 'The milestone already has this status.']);
            }

            if ($from === ResearchMilestoneStatus::Completed) {
                if (! in_array($target, [ResearchMilestoneStatus::Pending, ResearchMilestoneStatus::InProgress, ResearchMilestoneStatus::NotApplicable], true) || $reason === null) {
                    throw ValidationException::withMessages(['reason' => 'A reason is required to correct a completed milestone.']);
                }
            } elseif ($from === ResearchMilestoneStatus::NotApplicable) {
                if ($target !== ResearchMilestoneStatus::Pending || $reason === null) {
                    throw ValidationException::withMessages(['reason' => 'A reason is required to re-enable a not-applicable milestone.']);
                }
            } elseif ($target === ResearchMilestoneStatus::InProgress && $from !== ResearchMilestoneStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'Only a pending milestone may be started.']);
            } elseif ($target === ResearchMilestoneStatus::Completed) {
                if ($from === ResearchMilestoneStatus::Pending && (! $directCompletion || $reason === null || ! $actor->can('overrideOrder', $locked))) {
                    throw ValidationException::withMessages(['status' => 'Start this milestone first. Direct completion requires controlled override authority and a reason.']);
                }
                if (! in_array($from, [ResearchMilestoneStatus::Pending, ResearchMilestoneStatus::InProgress], true)) {
                    throw ValidationException::withMessages(['status' => 'This milestone cannot be completed from its current state.']);
                }
            } elseif ($target === ResearchMilestoneStatus::NotApplicable) {
                if (! in_array($from, [ResearchMilestoneStatus::Pending, ResearchMilestoneStatus::InProgress, ResearchMilestoneStatus::Completed], true) || $reason === null) {
                    throw ValidationException::withMessages(['reason' => 'A reason is required to mark a milestone not applicable.']);
                }
            } elseif ($target === ResearchMilestoneStatus::Pending && $from !== ResearchMilestoneStatus::Completed) {
                throw ValidationException::withMessages(['status' => 'Only a completed milestone may be corrected to pending.']);
            }

            if (in_array($target, [ResearchMilestoneStatus::InProgress, ResearchMilestoneStatus::Completed], true)) {
                $hasIncompletePrerequisite = ResearchGroupMilestone::query()
                    ->join('milestone_definitions', 'milestone_definitions.id', '=', 'research_group_milestones.milestone_definition_id')
                    ->where('research_group_milestones.research_class_group_id', $group->getKey())
                    ->where('milestone_definitions.is_active', true)
                    ->where('milestone_definitions.sequence', '<', $locked->definition->sequence)
                    ->whereNotIn('research_group_milestones.status', [
                        ResearchMilestoneStatus::Completed->value,
                        ResearchMilestoneStatus::NotApplicable->value,
                    ])
                    ->lockForUpdate()
                    ->exists();

                if ($hasIncompletePrerequisite && (! $overrideOrder || $reason === null || ! $actor->can('overrideOrder', $locked))) {
                    throw ValidationException::withMessages(['override_order' => 'Earlier applicable milestones must be completed first. A controlled override requires permission and a reason.']);
                }
            }

            $oldValues = $this->snapshot($locked);
            $now = now();
            $attributes = ['status' => $target, 'remarks' => $remarks, 'updated_by' => $actor->getKey()];

            if ($target === ResearchMilestoneStatus::InProgress) {
                $attributes += ['started_at' => $now, 'started_by' => $actor->getKey(), 'completed_at' => null, 'completed_by' => null, 'not_applicable_reason' => null];
            } elseif ($target === ResearchMilestoneStatus::Completed) {
                $attributes += ['started_at' => $locked->started_at ?? $now, 'started_by' => $locked->started_by ?? $actor->getKey(), 'completed_at' => $now, 'completed_by' => $actor->getKey(), 'not_applicable_reason' => null];
            } elseif ($target === ResearchMilestoneStatus::NotApplicable) {
                $attributes += ['started_at' => null, 'started_by' => null, 'completed_at' => null, 'completed_by' => null, 'not_applicable_reason' => $reason];
            } else {
                $attributes += ['started_at' => null, 'started_by' => null, 'completed_at' => null, 'completed_by' => null, 'not_applicable_reason' => null];
            }

            $locked->update($attributes);
            $locked->refresh();

            ResearchGroupMilestoneEvent::query()->create([
                'research_group_milestone_id' => $locked->getKey(),
                'actor_id' => $actor->getKey(),
                'event' => in_array($from, [ResearchMilestoneStatus::Completed, ResearchMilestoneStatus::NotApplicable], true) ? 'status_corrected' : 'status_changed',
                'from_status' => $from,
                'to_status' => $target,
                'reason' => $reason,
                'old_values' => $oldValues,
                'new_values' => $this->snapshot($locked),
                'override_order' => $overrideOrder || $directCompletion,
                'ip_address' => $ipAddress,
                'occurred_at' => $now,
            ]);

            return $locked->load(['definition', 'evidences', 'events.actor:id,name,email']);
        }, 3);
    }

    private function clean(?string $value, int $limit): ?string
    {
        $value = trim(strip_tags((string) $value));

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }

    /** @return array<string, mixed> */
    private function snapshot(ResearchGroupMilestone $milestone): array
    {
        return [
            'status' => $milestone->status->value,
            'started_at' => $milestone->started_at?->toAtomString(),
            'completed_at' => $milestone->completed_at?->toAtomString(),
            'not_applicable_reason' => $milestone->not_applicable_reason,
            'remarks' => $milestone->remarks,
        ];
    }
}
