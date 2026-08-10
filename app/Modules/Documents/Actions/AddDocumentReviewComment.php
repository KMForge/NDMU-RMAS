<?php

namespace App\Modules\Documents\Actions;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentReviewAudit;
use App\Models\DocumentReviewComment;
use App\Models\User;
use App\Modules\Documents\Exceptions\DocumentReviewException;
use App\Modules\Documents\Support\DocumentReviewerAccess;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AddDocumentReviewComment
{
    public function __construct(
        private readonly DocumentReviewerAccess $reviewerAccess,
    ) {}

    /**
     * @param  array{
     *     comment: string,
     *     page_number: ?int,
     *     severity: string
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

                if (! $lockedDocument->is_current) {
                    throw new DocumentReviewException(
                        'Cannot add comments to a historical or superseded document version.',
                    );
                }

                if (! $this->reviewerAccess->canReview($reviewer, $lockedDocument)) {
                    throw new DocumentReviewException(
                        'You are not authorized to comment on this document.',
                    );
                }

                if (in_array($lockedDocument->status, [
                    DocumentStatus::Accepted,
                    DocumentStatus::Rejected,
                    DocumentStatus::RevisionRequested,
                ], true)) {
                    throw new DocumentReviewException(
                        'Cannot add new findings to a document that has already received a final review decision. Use decision correction first.',
                    );
                }

                if (strtolower((string) $lockedDocument->file_type) === 'docx' && ($data['page_number'] ?? null) !== null) {
                    throw new DocumentReviewException(
                        'Page numbers are not supported for DOCX files.',
                    );
                }

                $comment = DocumentReviewComment::query()->create([
                    'document_id' => $lockedDocument->getKey(),
                    'author_id' => $reviewer->getKey(),
                    'comment' => $data['comment'],
                    'severity' => $data['severity'],
                    'page_number' => strtolower((string) $lockedDocument->file_type) === 'pdf' ? ($data['page_number'] ?? null) : null,
                ]);

                if ($lockedDocument->status === DocumentStatus::Pending) {
                    $lockedDocument->update(['status' => DocumentStatus::UnderReview]);
                }

                DocumentReviewAudit::query()->create([
                    'document_id' => $lockedDocument->getKey(),
                    'reviewer_id' => $reviewer->getKey(),
                    'student_id' => $lockedDocument->user_id,
                    'action' => 'comment_added',
                    'decision' => $lockedDocument->status->value,
                    'occurred_at' => now(),
                    'metadata' => [
                        'comment_id' => $comment->getKey(),
                        'severity' => $comment->severity,
                        'page_number' => $comment->page_number,
                    ],
                ]);

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
