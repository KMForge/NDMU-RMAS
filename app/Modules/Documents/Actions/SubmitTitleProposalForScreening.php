<?php

namespace App\Modules\Documents\Actions;

use App\Enums\AccountStatus;
use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\ResearchProgress\Actions\SynchronizeWorkflowMilestone;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubmitTitleProposalForScreening
{
    public function __construct(private readonly SynchronizeWorkflowMilestone $synchronizeMilestone) {}

    public function handle(User $actor, Document $document): Document
    {
        return DB::transaction(function () use ($actor, $document): Document {
            $locked = Document::query()->lockForUpdate()->findOrFail($document->id);
            $group = ResearchClassGroup::query()->with('researchClass')->lockForUpdate()->findOrFail($locked->research_class_group_id);
            $actor->refresh();

            $eligible = $actor->user_type === UserType::Student
                && $actor->status === AccountStatus::Active
                && $actor->approved_at !== null
                && $actor->email_verified_at !== null
                && $actor->can('documents.upload')
                && $group->isLeader($actor);

            if (! $eligible) {
                throw new AuthorizationException('Only the current Group Leader may submit this Title Proposal for screening.');
            }

            if ($locked->document_stage !== DocumentStage::TitleProposal || ! $locked->is_current || $locked->status !== DocumentStatus::Draft) {
                throw new InvalidArgumentException('Only the current draft Title Proposal document may be submitted for screening.');
            }

            $locked->update([
                'status' => DocumentStatus::Submitted,
                'submitted_at' => now(),
            ]);

            $this->synchronizeMilestone->start(
                $group,
                'research-title-presentation',
                $actor,
                'document',
                $locked->getKey(),
                "Title Proposal v{$locked->version_number} submitted for facilitator screening.",
            );

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'TITLE_PROPOSAL_DOCUMENT_SUBMITTED',
                'auditable_type' => Document::class,
                'auditable_id' => $locked->id,
                'description' => "Submitted Title Proposal v{$locked->version_number} for facilitator screening.",
                'subject_snapshot' => ['academic_actor_type' => 'student_group_leader', 'group_id' => $group->id, 'version_number' => $locked->version_number],
            ]);

            return $locked->fresh();
        }, 3);
    }
}
