<?php

namespace App\Modules\TitlePresentations\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\OfficialFormVersion;
use App\Models\ResearchClassGroup;
use App\Models\TitlePresentation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecordApprovedTitle
{
    public function handle(User $actor, TitlePresentation $presentation, int $approvedTitleNumber, ?string $remarks = null): TitlePresentation
    {
        if (! in_array($approvedTitleNumber, [1, 2, 3], true)) {
            throw new InvalidArgumentException('Approved Research Title No. must be 1, 2, or 3.');
        }

        return DB::transaction(function () use ($actor, $presentation, $approvedTitleNumber, $remarks): TitlePresentation {
            $locked = TitlePresentation::query()->with('defense')->lockForUpdate()->findOrFail($presentation->id);
            $group = ResearchClassGroup::query()->with('researchClass')->lockForUpdate()->findOrFail($locked->defense->research_class_group_id);
            $version = OfficialFormVersion::query()->lockForUpdate()->findOrFail($locked->official_form_version_id);
            $actor->refresh();
            $eligible = $actor->user_type === UserType::Faculty
                && $actor->status === AccountStatus::Active
                && $actor->approved_at !== null
                && $actor->email_verified_at !== null
                && $actor->can('defenses.manage')
                && (int) $group->researchClass?->facilitator_id === (int) $actor->id;

            if (! $eligible) {
                throw new AuthorizationException('Only the owning Research Facilitator may record the approved title number.');
            }
            if ($locked->status !== 'presented') {
                throw new InvalidArgumentException('The Title Presentation must be completed before recording its approved title result.');
            }
            $topics = array_values($version->payload['topics'] ?? []);
            if (count($topics) !== 3 || trim((string) ($topics[$approvedTitleNumber - 1] ?? '')) === '') {
                throw new InvalidArgumentException('The selected title number does not reference a valid title in the exact submitted RES-026 version.');
            }
            $locked->update([
                'status' => 'awaiting_panel_signatures', 'approved_title_number' => $approvedTitleNumber,
                'remarks' => trim((string) $remarks) ?: null, 'result_recorded_by' => $actor->id, 'result_recorded_at' => now(),
            ]);
            if ($locked->defense && $locked->defense->status !== 'completed') {
                $locked->defense->update(['status' => 'completed']);
                $locked->defense->currentSchedule?->update(['status' => 'completed']);
            }
            AuditLog::query()->create([
                'user_id' => $actor->id, 'actor_name' => $actor->name, 'actor_email' => $actor->email,
                'event' => 'RES026_APPROVED_TITLE_RECORDED', 'auditable_type' => TitlePresentation::class, 'auditable_id' => $locked->id,
                'description' => "Recorded Approved Research Title No. {$approvedTitleNumber}.",
                'subject_snapshot' => ['academic_actor_type' => 'research_facilitator', 'approved_title_number' => $approvedTitleNumber, 'official_form_version_id' => $version->id],
            ]);

            return $locked->fresh();
        }, 3);
    }
}
