<?php

namespace App\Modules\TitlePresentations\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClassGroup;
use App\Models\TitlePresentation;
use App\Models\User;
use App\Modules\DefenseScheduling\Actions\ScheduleDefense;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ScheduleTitlePresentation
{
    public function __construct(private readonly ScheduleDefense $scheduleDefense) {}

    public function handle(User $actor, OfficialFormInstance $instance, int $roomId, CarbonInterface $startsAt, CarbonInterface $endsAt): TitlePresentation
    {
        return DB::transaction(function () use ($actor, $instance, $roomId, $startsAt, $endsAt): TitlePresentation {
            $locked = OfficialFormInstance::query()->with(['definition', 'currentVersion'])->lockForUpdate()->findOrFail($instance->id);
            $group = ResearchClassGroup::query()->with('researchClass')->lockForUpdate()->findOrFail($locked->research_class_group_id);
            $actor->refresh();

            if (strtoupper($locked->definition->code) !== 'RES-026' || $locked->status !== 'submitted' || $locked->currentVersion === null) {
                throw new InvalidArgumentException('A valid submitted RES-026 is required before scheduling a Title Presentation.');
            }
            $eligible = $actor->user_type === UserType::Faculty
                && $actor->status === AccountStatus::Active
                && $actor->approved_at !== null
                && $actor->email_verified_at !== null
                && $actor->can('defenses.manage')
                && (int) $group->researchClass?->facilitator_id === (int) $actor->id;

            if (! $eligible) {
                throw new AuthorizationException('Only the owning Research Facilitator may schedule this Title Presentation.');
            }
            if (TitlePresentation::query()->where('official_form_instance_id', $locked->id)->exists()) {
                throw new InvalidArgumentException('This RES-026 already has a Title Presentation schedule. Use rescheduling to preserve history.');
            }

            $defense = $this->scheduleDefense->handle($actor, $group, 'title_presentation', $roomId, $startsAt, $endsAt, []);
            $presentation = TitlePresentation::query()->create([
                'defense_id' => $defense->id,
                'official_form_instance_id' => $locked->id,
                'official_form_version_id' => $locked->currentVersion->id,
                'status' => 'scheduled',
            ]);

            AuditLog::query()->create([
                'user_id' => $actor->id, 'actor_name' => $actor->name, 'actor_email' => $actor->email,
                'event' => 'TITLE_PRESENTATION_SCHEDULED', 'auditable_type' => TitlePresentation::class, 'auditable_id' => $presentation->id,
                'description' => 'Scheduled Title Presentation for a submitted RES-026.',
                'subject_snapshot' => ['academic_actor_type' => 'research_facilitator', 'group_id' => $group->id, 'res026_version_id' => $locked->currentVersion->id, 'defense_id' => $defense->id],
            ]);

            return $presentation->load('defense.currentSchedule.room');
        }, 3);
    }
}
