<?php

namespace App\Modules\TitlePresentations\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\ResearchClassGroup;
use App\Models\TitlePresentation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CompleteTitlePresentation
{
    public function handle(User $actor, TitlePresentation $presentation): TitlePresentation
    {
        return DB::transaction(function () use ($actor, $presentation): TitlePresentation {
            $locked = TitlePresentation::query()->with('defense.activePanelAssignments')->lockForUpdate()->findOrFail($presentation->id);
            $group = ResearchClassGroup::query()->with('researchClass')->lockForUpdate()->findOrFail($locked->defense->research_class_group_id);
            $actor->refresh();
            $eligible = $actor->user_type === UserType::Faculty
                && $actor->status === AccountStatus::Active
                && $actor->approved_at !== null
                && $actor->email_verified_at !== null
                && $actor->can('defenses.manage')
                && (int) $group->researchClass?->facilitator_id === (int) $actor->id;

            if (! $eligible) {
                throw new AuthorizationException('Only the owning Research Facilitator may complete this presentation event.');
            }
            if ($locked->status !== 'panel_assigned' || $locked->defense->activePanelAssignments->count() !== 3) {
                throw new InvalidArgumentException('The scheduled Title Presentation must have its complete panel before it can be marked completed.');
            }
            $locked->update(['status' => 'presented', 'presented_at' => now(), 'presentation_completed_by' => $actor->id]);
            AuditLog::query()->create([
                'user_id' => $actor->id, 'actor_name' => $actor->name, 'actor_email' => $actor->email,
                'event' => 'TITLE_PRESENTATION_COMPLETED', 'auditable_type' => TitlePresentation::class, 'auditable_id' => $locked->id,
                'description' => 'Marked the Title Presentation event completed.', 'subject_snapshot' => ['academic_actor_type' => 'research_facilitator'],
            ]);

            return $locked->fresh();
        }, 3);
    }
}
