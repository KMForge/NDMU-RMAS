<?php

namespace App\Modules\Documents\Actions;

use App\Models\Document;
use App\Models\DocumentReviewComment;
use App\Models\User;
use App\Modules\Documents\Exceptions\DocumentReviewException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ResolveDocumentReviewComment
{
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
                $lockedComment = DocumentReviewComment::query()
                    ->whereKey($comment->getKey())
                    ->where('document_id', $document->getKey())
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
