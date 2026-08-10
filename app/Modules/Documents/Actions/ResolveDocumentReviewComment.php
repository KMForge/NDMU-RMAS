<?php

namespace App\Modules\Documents\Actions;

use App\Models\Document;
use App\Models\DocumentReviewAudit;
use App\Models\DocumentReviewComment;
use App\Models\User;
use App\Modules\Documents\Exceptions\DocumentReviewException;
use App\Modules\Documents\Support\DocumentReviewerAccess;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ResolveDocumentReviewComment
{
    public function __construct(
        private readonly DocumentReviewerAccess $reviewerAccess,
    ) {}

    public function handle(
        User $reviewer,
        Document $document,
        DocumentReviewComment $comment,
    ): DocumentReviewComment {
        try {
            return DB::transaction(function () use (
                $reviewer,
                $document,
                $comment,
            ): DocumentReviewComment {
                $lockedDocument = Document::query()
                    ->whereKey($document->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $lockedDocument->is_current) {
                    throw new DocumentReviewException(
                        'Cannot resolve findings on a historical or superseded document version.',
                    );
                }

                if (! $this->reviewerAccess->canReview($reviewer, $lockedDocument)) {
                    throw new DocumentReviewException(
                        'You are not authorized to resolve findings on this document.',
                    );
                }

                $lockedComment = DocumentReviewComment::query()
                    ->whereKey($comment->getKey())
                    ->where('document_id', $lockedDocument->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedComment === null) {
                    throw new DocumentReviewException(
                        'The document comment was not found.',
                    );
                }

                if ($lockedComment->resolved_at !== null) {
                    throw new DocumentReviewException(
                        'This document comment has already been resolved.',
                    );
                }

                $lockedComment->update([
                    'resolved_by' => $reviewer->getKey(),
                    'resolved_at' => now(),
                ]);

                DocumentReviewAudit::query()->create([
                    'document_id' => $lockedDocument->getKey(),
                    'reviewer_id' => $reviewer->getKey(),
                    'student_id' => $lockedDocument->user_id,
                    'action' => 'comment_resolved',
                    'decision' => $lockedDocument->status->value,
                    'occurred_at' => now(),
                    'metadata' => [
                        'comment_id' => $lockedComment->getKey(),
                        'severity' => $lockedComment->severity,
                    ],
                ]);

                return $lockedComment->refresh();
            }, 3);
        } catch (DocumentReviewException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            throw new DocumentReviewException(
                'The comment could not be resolved. Please try again.',
            );
        }
    }
}
