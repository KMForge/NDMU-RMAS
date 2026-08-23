<?php

namespace App\Modules\ResearchProgress\Actions;

use App\Enums\ResearchMilestoneStatus;
use App\Models\AuditLog;
use App\Models\MilestoneDefinition;
use App\Models\MilestoneEvidence;
use App\Models\ResearchClassGroup;
use App\Models\ResearchGroupMilestone;
use App\Models\ResearchGroupMilestoneEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SynchronizeWorkflowMilestone
{
    public function start(
        ResearchClassGroup $group,
        string $milestoneCode,
        User $actor,
        string $evidenceType,
        int $evidenceId,
        ?string $summary = null,
        ?string $ipAddress = null,
    ): ResearchGroupMilestone {
        return $this->synchronize(
            $group,
            $milestoneCode,
            ResearchMilestoneStatus::InProgress,
            $actor,
            $evidenceType,
            $evidenceId,
            $summary,
            $ipAddress,
        );
    }

    public function complete(
        ResearchClassGroup $group,
        string $milestoneCode,
        User $actor,
        string $evidenceType,
        int $evidenceId,
        ?string $summary = null,
        ?string $ipAddress = null,
    ): ResearchGroupMilestone {
        return $this->synchronize(
            $group,
            $milestoneCode,
            ResearchMilestoneStatus::Completed,
            $actor,
            $evidenceType,
            $evidenceId,
            $summary,
            $ipAddress,
        );
    }

    private function synchronize(
        ResearchClassGroup $group,
        string $milestoneCode,
        ResearchMilestoneStatus $target,
        User $actor,
        string $evidenceType,
        int $evidenceId,
        ?string $summary,
        ?string $ipAddress,
    ): ResearchGroupMilestone {
        return DB::transaction(function () use ($group, $milestoneCode, $target, $actor, $evidenceType, $evidenceId, $summary, $ipAddress): ResearchGroupMilestone {
            $lockedGroup = ResearchClassGroup::query()->lockForUpdate()->findOrFail($group->getKey());
            $definition = MilestoneDefinition::query()
                ->where('code', $milestoneCode)
                ->where('is_active', true)
                ->firstOrFail();

            ResearchGroupMilestone::query()->firstOrCreate([
                'research_class_group_id' => $lockedGroup->getKey(),
                'milestone_definition_id' => $definition->getKey(),
            ]);

            $milestone = ResearchGroupMilestone::query()
                ->where('research_class_group_id', $lockedGroup->getKey())
                ->where('milestone_definition_id', $definition->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($milestone->status === ResearchMilestoneStatus::NotApplicable) {
                throw new InvalidArgumentException('An authoritative workflow cannot update a milestone marked not applicable until an administrator re-enables it.');
            }

            $from = $milestone->status;
            $now = now();
            $stateChanged = false;

            if ($target === ResearchMilestoneStatus::InProgress && $from === ResearchMilestoneStatus::Pending) {
                $milestone->update([
                    'status' => $target,
                    'started_at' => $now,
                    'started_by' => $actor->getKey(),
                    'updated_by' => $actor->getKey(),
                ]);
                $stateChanged = true;
            } elseif ($target === ResearchMilestoneStatus::Completed && $from !== ResearchMilestoneStatus::Completed) {
                $milestone->update([
                    'status' => $target,
                    'started_at' => $milestone->started_at ?? $now,
                    'started_by' => $milestone->started_by ?? $actor->getKey(),
                    'completed_at' => $now,
                    'completed_by' => $actor->getKey(),
                    'updated_by' => $actor->getKey(),
                ]);
                $stateChanged = true;
            }

            $milestone->refresh();
            $cleanSummary = $this->cleanSummary($summary);
            $evidence = MilestoneEvidence::query()->firstOrCreate(
                [
                    'research_group_milestone_id' => $milestone->getKey(),
                    'evidence_type' => $evidenceType,
                    'evidence_id' => $evidenceId,
                ],
                [
                    'linked_by' => $actor->getKey(),
                    'summary' => $cleanSummary,
                    'linked_at' => $now,
                ],
            );

            if ($evidence->wasRecentlyCreated) {
                ResearchGroupMilestoneEvent::query()->create([
                    'research_group_milestone_id' => $milestone->getKey(),
                    'actor_id' => $actor->getKey(),
                    'event' => 'workflow_evidence_linked',
                    'from_status' => $milestone->status,
                    'to_status' => $milestone->status,
                    'new_values' => [
                        'evidence_type' => $evidenceType,
                        'evidence_id' => $evidenceId,
                        'summary' => $cleanSummary,
                    ],
                    'override_order' => false,
                    'ip_address' => $this->validIp($ipAddress),
                    'occurred_at' => $now,
                ]);
            }

            if ($stateChanged) {
                ResearchGroupMilestoneEvent::query()->create([
                    'research_group_milestone_id' => $milestone->getKey(),
                    'actor_id' => $actor->getKey(),
                    'event' => $target === ResearchMilestoneStatus::Completed
                        ? 'workflow_completed'
                        : 'workflow_started',
                    'from_status' => $from,
                    'to_status' => $target,
                    'old_values' => ['status' => $from->value],
                    'new_values' => [
                        'status' => $target->value,
                        'evidence_type' => $evidenceType,
                        'evidence_id' => $evidenceId,
                    ],
                    'override_order' => false,
                    'ip_address' => $this->validIp($ipAddress),
                    'occurred_at' => $now,
                ]);

                AuditLog::query()->create([
                    'user_id' => $actor->getKey(),
                    'actor_name' => $actor->name,
                    'actor_email' => $actor->email,
                    'event' => 'research_milestone.workflow_synchronized',
                    'auditable_type' => ResearchGroupMilestone::class,
                    'auditable_id' => $milestone->getKey(),
                    'description' => "Automatically changed {$definition->name} from {$from->label()} to {$target->label()} using authoritative workflow evidence.",
                    'subject_snapshot' => [
                        'research_class_group_id' => $lockedGroup->getKey(),
                        'milestone_code' => $definition->code,
                        'from_status' => $from->value,
                        'to_status' => $target->value,
                        'evidence_type' => $evidenceType,
                        'evidence_id' => $evidenceId,
                    ],
                ]);
            }

            return $milestone->load(['definition', 'evidences', 'events.actor:id,name,email']);
        }, 3);
    }

    private function cleanSummary(?string $summary): ?string
    {
        $summary = trim(strip_tags((string) $summary));

        return $summary === '' ? null : mb_substr($summary, 0, 500);
    }

    private function validIp(?string $ipAddress): ?string
    {
        return filter_var($ipAddress, FILTER_VALIDATE_IP) !== false ? $ipAddress : null;
    }
}
