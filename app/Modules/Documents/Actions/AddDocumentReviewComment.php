<?php

namespace App\Modules\Documents\Actions;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentReviewComment;
use App\Models\User;
use App\Modules\Documents\Exceptions\DocumentReviewException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AddDocumentReviewComment
{
    /**
     * @param  array{
     *     comment: string,
     *     page_number: ?int,
     *     severity: string,
     *     parent_id: ?int
     * }  $data
     */
    public function handle(User $reviewer, Document $document, array $data): DocumentReviewComment
    {
        try {
            return DB::transaction(function () use ($reviewer, $document, $data): DocumentReviewComment {
                $lockedDocument = Document::query()
                    ->whereKey($document->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($data['parent_id'] !== null) {
                    $parentExists = DocumentReviewComment::query()
                        ->whereKey($data['parent_id'])
                        ->where('document_id', $lockedDocument->getKey())
                        ->exists();

                    if (! $parentExists) {
                        throw new DocumentReviewException(
                            'The comment you are replying to was not found.',
                        );
                    }
                }

                $comment = DocumentReviewComment::query()->create([
                    'document_id' => $lockedDocument->getKey(),
                    'author_id' => $reviewer->getKey(),
                    ...$data,
                ]);

                if ($lockedDocument->status === DocumentStatus::Pending) {
                    $lockedDocument->update(['status' => DocumentStatus::UnderReview]);
                }

                return $comment->load('author:id,name');
            }, 3);
        } catch (DocumentReviewException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            throw new DocumentReviewException(
                'The comment could not be saved. Please try again.',
            );
        }
    }
}
