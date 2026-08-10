<?php

namespace App\Modules\Documents\Actions;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentReview;
use App\Models\DocumentReviewAudit;
use App\Models\DocumentReviewComment;
use App\Models\User;
use App\Modules\Documents\Exceptions\DocumentReviewException;
use App\Modules\Documents\Support\DocumentReviewerAccess;
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

                if (! $lockedDocument->is_current) {
                    throw new DocumentReviewException(
                        'Cannot correct a decision on a historical or superseded document version.',
                    );
                }

                if (! $this->reviewerAccess->canReview($reviewer, $lockedDocument)) {
                    throw new DocumentReviewException(
                        'You are not authorized to correct decisions for this document.',
                    );
                }

                $newerVersionExists = Document::query()
                    ->where('research_class_group_id', $lockedDocument->research_class_group_id)
                    ->where('document_stage', $lockedDocument->document_stage?->value)
                    ->where('version_number', '>', $lockedDocument->version_number)
                    ->exists();

                if ($newerVersionExists) {
                    throw new DocumentReviewException(
                        'Cannot correct decision because a newer document version has been submitted.',
                    );
                }

                $originalReview = DocumentReview::query()
                    ->where('document_id', $lockedDocument->getKey())
                    ->where('is_superseded', false)
                    ->latest('reviewed_at')
                    ->first();

                if ($originalReview === null) {
                    throw new DocumentReviewException(
                        'No prior review decision exists to correct for this document.',
                    );
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
