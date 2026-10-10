<?php

namespace App\Modules\ResearchProgress\Actions;

use App\Enums\ResearchMilestoneStatus;
use App\Models\AuditLog;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchGroupMilestone;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CorrectClassWideEndorsementProgress
{
    /** @return list<int> Historical evidence and events are retained. */
    public function execute(ResearchClass $class, User $actor): array
    {
        abort_unless($actor->can('progress.view-all'), 403);

        return DB::transaction(function () use ($class, $actor): array {
            $forms = OfficialFormInstance::query()->where('research_class_id', $class->id)
                ->whereNull('research_class_group_id')
                ->whereHas('definition', fn ($q) => $q->where('code', 'RES-041'))->pluck('id');
            $milestones = ResearchGroupMilestone::query()
                ->whereHas('group', fn ($q) => $q->where('research_class_id', $class->id))
                ->whereHas('definition', fn ($q) => $q->where('code', 'revision-research-proposal'))
                ->where('status', ResearchMilestoneStatus::Completed->value)->lockForUpdate()->get();
            $corrected = [];
            foreach ($milestones as $milestone) {
                $event = $milestone->events()->whereColumn('from_status', '!=', 'to_status')->latest('id')->first();
                if ($event?->event !== 'workflow_completed'
                    || ($event->new_values['evidence_type'] ?? null) !== 'official_form'
                    || ! $forms->contains((int) ($event->new_values['evidence_id'] ?? 0))) {
                    continue;
                }
                $target = $event->from_status === ResearchMilestoneStatus::InProgress
                    ? ResearchMilestoneStatus::InProgress : ResearchMilestoneStatus::Pending;
                $old = $milestone->only(['status', 'started_at', 'started_by', 'completed_at', 'completed_by', 'updated_by']);
                $new = ['status' => $target->value, 'invalid_form_id' => $event->new_values['evidence_id']];
                $milestone->update([
                    'status' => $target, 'completed_at' => null, 'completed_by' => null,
                    'started_at' => $target === ResearchMilestoneStatus::Pending ? null : $milestone->started_at,
                    'started_by' => $target === ResearchMilestoneStatus::Pending ? null : $milestone->started_by,
                    'updated_by' => $actor->id,
                ]);
                $reason = 'Corrected class-wide RES-041 completion; no group-scoped revision evidence.';
                $milestone->events()->create([
                    'actor_id' => $actor->id, 'event' => 'workflow_scope_corrected',
                    'from_status' => ResearchMilestoneStatus::Completed, 'to_status' => $target,
                    'old_values' => $old, 'new_values' => $new,
                    'reason' => $reason, 'override_order' => false, 'occurred_at' => now(),
                ]);
                AuditLog::query()->create([
                    'user_id' => $actor->id, 'actor_name' => $actor->name,
                    'event' => 'research_milestone.class_scope_corrected',
                    'auditable_type' => ResearchGroupMilestone::class, 'auditable_id' => $milestone->id,
                    'description' => $reason, 'old_values' => $old, 'new_values' => $new,
                ]);
                $corrected[] = $milestone->id;
            }

            return $corrected;
        });
    }
}
