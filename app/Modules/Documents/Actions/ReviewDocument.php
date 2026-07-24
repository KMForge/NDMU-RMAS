<?php

namespace App\Modules\Documents\Actions;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentReview;
use App\Models\User;
use App\Modules\Documents\Exceptions\DocumentReviewException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ReviewDocument
{
    public function handle(
        User $reviewer,
        Document $document,
        string $decision,
        ?string $notes,
    ): DocumentReview {
        try {
            return DB::transaction(function () use (
                $reviewer,
                $document,
                $decision,
                $notes,
            ): DocumentReview {
                $lockedDocument = Document::query()
                    ->whereKey($document->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (in_array($lockedDocument->status, [
                    DocumentStatus::Accepted,
                    DocumentStatus::Rejected,
                    DocumentStatus::RevisionRequested,
                ], true)) {
                    throw new DocumentReviewException(
                        'This document has already received a final review decision.',
                    );
                }

                $review = DocumentReview::query()->create([
                    'document_id' => $lockedDocument->getKey(),
                    'reviewer_id' => $reviewer->getKey(),
                    'decision' => $decision,
                    'review_notes' => $notes,
                    'reviewed_at' => now(),
                ]);

                $lockedDocument->update(['status' => DocumentStatus::from($decision)]);

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
