<?php

namespace App\Modules\TitlePresentations\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\Defense;
use App\Models\OfficialFormInstance;
use App\Models\TitlePresentation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LinkScheduledTitlePresentation
{
    public function handle(User $actor, Defense $defense, bool $required = true): ?TitlePresentation
    {
        if ($defense->defense_type !== 'title_presentation') {
            return null;
        }

        return DB::transaction(function () use ($actor, $defense, $required): ?TitlePresentation {
            $locked = Defense::query()->with(['group.researchClass', 'currentSchedule', 'activePanelAssignments'])->lockForUpdate()->findOrFail($defense->id);
            if ($actor->user_type !== UserType::Faculty || $actor->status !== AccountStatus::Active
                || ! $actor->can('defenses.manage') || (int) $locked->group?->researchClass?->facilitator_id !== (int) $actor->id) {
                throw new AuthorizationException('Only the owning facilitator may link this Title Presentation.');
            }

            $existing = TitlePresentation::query()->where('defense_id', $locked->id)->first();
            if ($existing !== null) {
                return $existing;
            }

            if ($locked->currentSchedule?->status !== 'current' || $locked->status !== 'scheduled') {
                throw new InvalidArgumentException('A current scheduled Title Presentation is required.');
            }

            $instances = OfficialFormInstance::query()
                ->where('research_class_group_id', $locked->research_class_group_id)
                ->whereHas('definition', fn ($query) => $query->where('code', 'RES-026'))
                ->where('status', 'submitted')
                ->whereNotNull('current_version_id')
                ->with('currentVersion')
                ->lockForUpdate()
                ->get();

            if ($instances->isEmpty() && ! $required) {
                return null;
            }
            if ($instances->count() !== 1) {
                throw new InvalidArgumentException('Exactly one submitted RES-026 is required to link this group’s Title Presentation.');
            }
            $instance = $instances->first();
            if ($instance->currentVersion === null || $instance->titlePresentation !== null) {
                throw new InvalidArgumentException('The submitted RES-026 is missing its version or is already linked to another presentation.');
            }
            if ($instance->currentVersion->created_at->gt($locked->currentSchedule->created_at)) {
                throw new InvalidArgumentException('The current RES-026 version was created after this schedule. Its historical version must be reviewed before linking.');
            }

            $roster = $locked->activePanelAssignments->keyBy('panel_position');
            $complete = $locked->activePanelAssignments->count() === 3
                && $roster->has(['chairperson', 'member_1', 'member_2'])
                && $locked->activePanelAssignments->pluck('user_id')->unique()->count() === 3;
            $presentation = TitlePresentation::query()->create([
                'defense_id' => $locked->id,
                'official_form_instance_id' => $instance->id,
                'official_form_version_id' => $instance->current_version_id,
                'status' => $complete ? 'panel_assigned' : 'scheduled',
            ]);

            AuditLog::query()->create([
                'user_id' => $actor->id, 'actor_name' => $actor->name, 'actor_email' => $actor->email,
                'event' => 'TITLE_PRESENTATION_LINKED', 'auditable_type' => TitlePresentation::class, 'auditable_id' => $presentation->id,
                'description' => 'Linked the existing Title Presentation schedule and panel to its submitted RES-026 version.',
                'subject_snapshot' => ['defense_id' => $locked->id, 'group_id' => $locked->research_class_group_id, 'res026_version_id' => $instance->current_version_id],
            ]);

            return $presentation;
        }, 3);
    }
}
