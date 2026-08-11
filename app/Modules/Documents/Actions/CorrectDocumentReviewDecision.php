<?php

namespace App\Modules\Documents\Actions;

use App\Enums\DocumentStatus;
use App\Enums\RevisionStatus;
use App\Models\Document;
use App\Models\DocumentReview;
use App\Models\DocumentReviewAudit;
use App\Models\DocumentReviewComment;
use App\Models\RevisionRequest;
use App\Models\RevisionRequestEvent;
use App\Models\User;
use App\Modules\Documents\Exceptions\DocumentReviewException;
use App\Modules\Documents\Support\DocumentReviewerAccess;
use App\Modules\Revisions\Actions\CreateRevisionCycleFromReview;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CorrectDocumentReviewDecision
{
    public function __construct(
        private readonly DocumentReviewerAccess $reviewerAccess,
    ) {}

    public function handle(
        User $reviewer,
        Document $document,
        string $decision,
        string $reason,
        ?string $notes = null,
        ?string $ipAddress = null,
    ): DocumentReview {
        try {
            return DB::transaction(function () use (
                $reviewer,
                $document,
                $decision,
                $reason,
                $notes,
                $ipAddress,
            ): DocumentReview {
                $lockedDocument = Document::query()
                    ->with(['user:id,name,email', 'researchClassGroup'])
                    ->whereKey($document->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $this->reviewerAccess->canReview($reviewer, $lockedDocument)) {
                    throw new DocumentReviewException(
                        'You are not authorized to correct decisions for this document.',
                    );
                }

                $originalReview = DocumentReview::query()
                    ->where('document_id', $lockedDocument->getKey())
                    ->where('is_superseded', false)
                    ->latest('reviewed_at')
                    ->lockForUpdate()
                    ->first();

                if ($originalReview === null) {
                    throw new DocumentReviewException(
                        'No prior review decision exists to correct for this document.',
                    );
                }

                $newerDocuments = Document::query()
                    ->where('research_class_group_id', $lockedDocument->research_class_group_id)
                    ->where('document_stage', $lockedDocument->document_stage?->value)
                    ->where('version_number', '>', $lockedDocument->version_number)
                    ->get();

                if (! $lockedDocument->is_current || $newerDocuments->isNotEmpty()) {
                    $isCaseA = false;

                    if ($originalReview->decision === DocumentStatus::RevisionRequested->value) {
                        $cycle = RevisionRequest::query()
                            ->where('source_document_review_id', $originalReview->getKey())
                            ->first();

                        if ($cycle !== null && $cycle->submitted_document_id !== null) {
                            if ($newerDocuments->count() === 1 && $newerDocuments->first()->getKey() === $cycle->submitted_document_id) {
                                $isCaseA = true;
                            }
                        }
                    }

                    if (! $isCaseA) {
                        throw new DocumentReviewException(
                            'Cannot correct a decision on a historical or superseded document version.',
                        );
                    }
                }

                if (trim($reason) === '') {
                    throw new DocumentReviewException(
                        'A valid correction reason must be provided.',
                    );
                }

                if ($decision === DocumentStatus::Accepted->value) {
                    $hasUnresolvedBlockingComments = DocumentReviewComment::query()
                        ->where('document_id', $lockedDocument->getKey())
                        ->whereIn('severity', ['revision', 'critical'])
                        ->whereNull('resolved_at')
                        ->exists();

                    if ($hasUnresolvedBlockingComments) {
                        throw new DocumentReviewException(
                            'Cannot accept document while it has unresolved revision or critical findings.',
                        );
                    }
                }

                $originalReview->update(['is_superseded' => true]);

                $correctedReview = DocumentReview::query()->create([
                    'document_id' => $lockedDocument->getKey(),
                    'reviewer_id' => $reviewer->getKey(),
                    'supersedes_review_id' => $originalReview->getKey(),
                    'is_superseded' => false,
                    'decision' => $decision,
                    'review_notes' => $notes,
                    'correction_reason' => trim($reason),
                    'reviewed_at' => now(),
                ]);

                $lockedDocument->update(['status' => DocumentStatus::from($decision)]);

                DocumentReviewAudit::query()->create([
                    'document_id' => $lockedDocument->getKey(),
                    'reviewer_id' => $reviewer->getKey(),
                    'student_id' => $lockedDocument->user_id,
                    'action' => 'review_decision_corrected',
                    'decision' => $correctedReview->decision,
                    'ip_address' => filter_var($ipAddress, FILTER_VALIDATE_IP) !== false ? $ipAddress : null,
                    'occurred_at' => now(),
                    'metadata' => [
                        'original_review_id' => $originalReview->getKey(),
                        'original_decision' => $originalReview->decision,
                        'correction_reason' => trim($reason),
                        'document_stage' => $lockedDocument->document_stage?->value,
                        'version_number' => $lockedDocument->version_number,
                    ],
                ]);

                // Reconciliation Direction A: Original review was RevisionRequested, now corrected to Accepted/Rejected
                if ($originalReview->decision === DocumentStatus::RevisionRequested->value
                    && $decision !== DocumentStatus::RevisionRequested->value) {
                    $activeCycles = RevisionRequest::query()
                        ->where(function ($q) use ($originalReview, $lockedDocument) {
                            $q->where('source_document_review_id', $originalReview->getKey())
                                ->orWhere('document_id', $lockedDocument->getKey());
                        })
                        ->whereIn('status', [
                            RevisionStatus::Open,
                            RevisionStatus::InProgress,
                            RevisionStatus::Submitted,
                        ])
                        ->get();

                    foreach ($activeCycles as $cycle) {
                        $fromStatus = $cycle->status;
                        $cycle->update([
                            'status' => RevisionStatus::Cancelled,
                            'invalidated_at' => now(),
                            'invalidated_reason' => trim($reason),
                        ]);

                        RevisionRequestEvent::query()->create([
                            'revision_request_id' => $cycle->getKey(),
                            'actor_id' => $reviewer->getKey(),
                            'document_id' => $cycle->submitted_document_id ?? $lockedDocument->getKey(),
                            'action' => 'invalidated_by_review_correction',
                            'from_status' => $fromStatus->value,
                            'to_status' => RevisionStatus::Cancelled->value,
                            'notes' => "Revision cycle invalidated because review decision was corrected from Revision Requested to {$decision}. Reason: ".trim($reason),
                            'occurred_at' => now(),
                            'metadata' => [
                                'original_review_id' => $originalReview->getKey(),
                                'corrected_review_id' => $correctedReview->getKey(),
                                'original_decision' => $originalReview->decision,
                                'new_decision' => $decision,
                                'correction_reason' => trim($reason),
                                'has_submitted_response' => $cycle->submitted_document_id !== null,
                            ],
                        ]);
                    }
                }

                // Reconciliation Direction B: Original review was Accepted/Rejected, now corrected to RevisionRequested
                if ($decision === DocumentStatus::RevisionRequested->value) {
                    app(CreateRevisionCycleFromReview::class)
                        ->handle($lockedDocument, $correctedReview);
                }

                return $correctedReview->load(['reviewer:id,name', 'supersedes']);
            }, 3);
        } catch (DocumentReviewException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            throw new DocumentReviewException(
                'The review decision correction could not be saved. Please try again.',
            );
        }
    }
}
