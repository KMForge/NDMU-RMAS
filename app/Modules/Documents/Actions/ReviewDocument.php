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
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use App\Modules\Revisions\Actions\CreateRevisionCycleFromReview;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ReviewDocument
{
    public function __construct(
        private readonly DocumentReviewerAccess $reviewerAccess,
        private readonly WorkflowNotificationDispatcher $notifications,
    ) {}

    public function handle(
        User $reviewer,
        Document $document,
        string $decision,
        ?string $notes,
        ?string $ipAddress = null,
    ): DocumentReview {
        try {
            return DB::transaction(function () use (
                $reviewer,
                $document,
                $decision,
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
                        'Cannot review a historical or superseded document version.',
                    );
                }

                if (! $this->reviewerAccess->canReview($reviewer, $lockedDocument)) {
                    throw new DocumentReviewException(
                        'You are not authorized to review this document.',
                    );
                }

                if (in_array($lockedDocument->status, [
                    DocumentStatus::Accepted,
                    DocumentStatus::Rejected,
                    DocumentStatus::RevisionRequested,
                ], true)) {
                    throw new DocumentReviewException(
                        'This document has already received a final review decision. Use decision correction to amend an accidental decision.',
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

                $review = DocumentReview::query()->create([
                    'document_id' => $lockedDocument->getKey(),
                    'reviewer_id' => $reviewer->getKey(),
                    'decision' => $decision,
                    'review_notes' => $notes,
                    'reviewed_at' => now(),
                ]);

                $lockedDocument->update(['status' => DocumentStatus::from($decision)]);

                DocumentReviewAudit::query()->create([
                    'document_id' => $lockedDocument->getKey(),
                    'reviewer_id' => $reviewer->getKey(),
                    'student_id' => $lockedDocument->user_id,
                    'action' => 'review_decision_recorded',
                    'decision' => $review->decision,
                    'ip_address' => filter_var($ipAddress, FILTER_VALIDATE_IP) !== false ? $ipAddress : null,
                    'occurred_at' => now(),
                    'metadata' => [
                        'original_filename' => $lockedDocument->original_filename,
                        'file_type' => $lockedDocument->file_type,
                        'document_stage' => $lockedDocument->document_stage?->value,
                        'version_number' => $lockedDocument->version_number,
                    ],
                ]);

                if ($decision === DocumentStatus::RevisionRequested->value) {
                    app(CreateRevisionCycleFromReview::class)
                        ->handle($lockedDocument, $review);
                }

                $group = $lockedDocument->researchClassGroup;
                $students = $group?->members()->with('student')->get()->pluck('student')->filter() ?? collect();
                $decisionLabel = str($decision)->headline()->lower();

                $this->notifications->sendToMany(
                    recipients: $students,
                    eventKey: 'document.review.decision-recorded',
                    title: 'Document review decision available',
                    message: "{$lockedDocument->original_filename} was marked {$decisionLabel} by {$reviewer->name}.",
                    category: 'document',
                    routeName: 'student.dashboard',
                    routeParameters: ['tab' => $decision === DocumentStatus::RevisionRequested->value ? 'revisions' : 'proposal'],
                    sourceType: DocumentReview::class,
                    sourceId: $review->getKey(),
                    actor: $reviewer,
                    contextLabel: $group?->name,
                    actingAs: 'Student Researcher',
                    occurrence: $decision,
                );

                return $review->load('reviewer:id,name');
            }, 3);
        } catch (DocumentReviewException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            throw new DocumentReviewException(
                'The review decision could not be saved. Please try again.',
            );
        }
    }
}
