<?php

namespace App\Modules\Documents\Actions;

use App\Enums\AccountStatus;
use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentReview;
use App\Models\DocumentReviewAudit;
use App\Models\ResearchClassGroup;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ScreenTitleProposalDocument
{
    public function handle(User $actor, Document $document, string $decision, ?string $remarks, ?string $ipAddress = null): DocumentReview
    {
        if (! in_array($decision, ['revision_required', 'approved_for_presentation'], true)) {
            throw new InvalidArgumentException('The screening decision is invalid.');
        }

        $remarks = trim((string) $remarks);
        if ($decision === 'revision_required' && $remarks === '') {
            throw new InvalidArgumentException('Revision remarks are required when returning a Title Proposal.');
        }

        return DB::transaction(function () use ($actor, $document, $decision, $remarks, $ipAddress): DocumentReview {
            $locked = Document::query()->lockForUpdate()->findOrFail($document->id);
            $group = ResearchClassGroup::query()->with('researchClass')->lockForUpdate()->findOrFail($locked->research_class_group_id);
            $actor->refresh();

            $eligible = $actor->user_type === UserType::Faculty
                && $actor->status === AccountStatus::Active
                && $actor->approved_at !== null
                && $actor->email_verified_at !== null
                && $actor->can('documents.review')
                && (int) $group->researchClass?->facilitator_id === (int) $actor->id;

            if (! $eligible) {
                throw new AuthorizationException('Only the owning Research Facilitator may screen this Title Proposal.');
            }

            if ($locked->document_stage !== DocumentStage::TitleProposal || ! $locked->is_current || $locked->status !== DocumentStatus::Submitted) {
                throw new InvalidArgumentException('This Title Proposal is not awaiting facilitator screening.');
            }

            $status = $decision === 'revision_required'
                ? DocumentStatus::RevisionRequested
                : DocumentStatus::ApprovedForPresentation;

            $review = DocumentReview::query()->create([
                'document_id' => $locked->id,
                'reviewer_id' => $actor->id,
                'decision' => $status->value,
                'review_notes' => $remarks !== '' ? $remarks : null,
                'reviewed_at' => now(),
            ]);
            $locked->update(['status' => $status]);

            DocumentReviewAudit::query()->create([
                'document_id' => $locked->id,
                'reviewer_id' => $actor->id,
                'student_id' => $locked->user_id,
                'action' => 'title_proposal_screening_decision',
                'decision' => $status->value,
                'ip_address' => filter_var($ipAddress, FILTER_VALIDATE_IP) !== false ? $ipAddress : null,
                'occurred_at' => now(),
                'metadata' => ['group_id' => $group->id, 'version_number' => $locked->version_number],
            ]);

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => $decision === 'revision_required' ? 'TITLE_PROPOSAL_RETURNED_FOR_REVISION' : 'TITLE_PROPOSAL_APPROVED_FOR_PRESENTATION',
                'auditable_type' => Document::class,
                'auditable_id' => $locked->id,
                'description' => $decision === 'revision_required' ? 'Returned Title Proposal for revision.' : 'Approved Title Proposal for Title Presentation.',
                'subject_snapshot' => ['academic_actor_type' => 'research_facilitator', 'group_id' => $group->id, 'version_number' => $locked->version_number],
            ]);

            return $review->load('reviewer:id,name');
        }, 3);
    }
}
