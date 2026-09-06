<?php

namespace App\Modules\TitlePresentations\Actions;

use App\Enums\AccountStatus;
use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\OfficialFormVersion;
use App\Models\ResearchClassGroup;
use App\Models\TitlePresentation;
use App\Models\User;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecordDisapprovedTitlePresentation
{
    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications,
    ) {}

    public function handle(User $actor, TitlePresentation $presentation, string $remarks): TitlePresentation
    {
        $remarks = trim($remarks);
        if ($remarks === '') {
            throw new InvalidArgumentException('Remarks detailing why all titles were disapproved are required.');
        }

        return DB::transaction(function () use ($actor, $presentation, $remarks): TitlePresentation {
            $locked = TitlePresentation::query()->with('defense')->lockForUpdate()->findOrFail($presentation->id);
            $group = ResearchClassGroup::query()->with('researchClass')->lockForUpdate()->findOrFail($locked->defense->research_class_group_id);
            $version = OfficialFormVersion::query()->with('instance')->lockForUpdate()->findOrFail($locked->official_form_version_id);
            $actor->refresh();

            $eligible = $actor->user_type === UserType::Faculty
                && $actor->status === AccountStatus::Active
                && $actor->approved_at !== null
                && $actor->email_verified_at !== null
                && $actor->can('defenses.manage')
                && (int) $group->researchClass?->facilitator_id === (int) $actor->id;

            if (! $eligible) {
                throw new AuthorizationException('Only the owning Research Facilitator may record the presentation verdict.');
            }

            if ($locked->status !== 'presented') {
                throw new InvalidArgumentException('The Title Presentation must be completed before recording its verdict.');
            }

            $locked->update([
                'status' => 'disapproved',
                'approved_title_number' => null,
                'remarks' => $remarks,
                'result_recorded_by' => $actor->id,
                'result_recorded_at' => now(),
            ]);

            if ($locked->defense) {
                $locked->defense->update(['status' => 'completed']);
                $locked->defense->currentSchedule?->update(['status' => 'completed']);
            }

            // Unlock RES-026 instance for revision/re-proposal
            if ($version->instance) {
                $version->instance->update(['status' => 'revision_required']);
            }

            // Mark current Title Proposal document as Revision Requested so group can upload v2
            Document::query()
                ->where('research_class_group_id', $group->id)
                ->where('document_stage', DocumentStage::TitleProposal->value)
                ->where('is_current', true)
                ->update(['status' => DocumentStatus::RevisionRequested->value]);

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'TITLE_PRESENTATION_DISAPPROVED',
                'auditable_type' => TitlePresentation::class,
                'auditable_id' => $locked->id,
                'description' => 'Disapproved all proposed titles during Title Presentation. Re-proposal required.',
                'subject_snapshot' => [
                    'academic_actor_type' => 'research_facilitator',
                    'group_id' => $group->id,
                    'official_form_version_id' => $version->id,
                    'remarks' => $remarks,
                ],
            ]);

            $students = $group->members()->with('student')->get()->pluck('student')->filter();

            $this->notifications->sendToMany(
                recipients: $students,
                eventKey: 'defense.title-presentation.disapproved',
                title: 'Title Presentation: Re-Proposal Required',
                message: "Your group's proposed titles were disapproved by the panel. Reason: {$remarks}. Please prepare 3 new research topics, upload a new Title Proposal document, and submit a new RES-026.",
                category: 'defense',
                routeName: 'student.dashboard',
                routeParameters: ['tab' => 'proposal'],
                sourceType: TitlePresentation::class,
                sourceId: $locked->getKey(),
                actor: $actor,
                contextLabel: $group->name,
                actingAs: 'Research Facilitator',
            );

            return $locked->fresh();
        }, 3);
    }
}
