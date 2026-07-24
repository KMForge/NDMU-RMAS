<?php

namespace App\Modules\Documents\Queries;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentReviewComment;
use App\Models\User;
use App\Modules\Documents\Support\DocumentReviewerAccess;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GetAdviserDocumentReviewData
{
    public function __construct(
        private readonly DocumentReviewerAccess $reviewerAccess,
    ) {}

    /**
     * @return array{
     *     reviewDocuments: LengthAwarePaginator,
     *     selectedReviewDocument: ?Document,
     *     documentReviewComments: Collection<int, DocumentReviewComment>,
     *     documentReviewStats: array{approved: int, revisions: int, comments: int, critical: int},
     *     documentReviewSearch: string,
     *     documentReviewStatus: string
     * }
     */
    public function for(
        User $reviewer,
        string $search = '',
        string $status = 'pending',
        ?int $selectedDocumentId = null,
    ): array {
        $search = Str::limit(trim($search), 100, '');
        $status = in_array($status, [
            'pending',
            'under_review',
            'revision_requested',
            'accepted',
            'rejected',
            'all',
        ], true) ? $status : 'pending';

        $scope = $this->reviewerAccess->scopeFor(Document::query(), $reviewer);
        $documents = (clone $scope)
            ->with('user:id,name,email,student_id,program')
            ->when(
                $status !== 'all',
                fn (Builder $query) => $status === 'pending'
                    ? $query->whereIn('status', [
                        DocumentStatus::Pending->value,
                        DocumentStatus::Submitted->value,
                    ])
                    : $query->where('status', $status),
            )
            ->when($search !== '', function (Builder $query) use ($search): void {
                $pattern = '%'.Str::lower($search).'%';

                $query->where(function (Builder $searchQuery) use ($pattern): void {
                    $searchQuery
                        ->whereRaw('LOWER(original_filename) LIKE ?', [$pattern])
                        ->orWhereHas('user', function (Builder $userQuery) use ($pattern): void {
                            $userQuery
                                ->whereRaw('LOWER(name) LIKE ?', [$pattern])
                                ->orWhereRaw('LOWER(email) LIKE ?', [$pattern])
                                ->orWhereRaw('LOWER(student_id) LIKE ?', [$pattern]);
                        });
                });
            })
            ->latest('submitted_at')
            ->paginate(8, ['*'], 'documents_page')
            ->withQueryString();

        $selectedDocument = $selectedDocumentId === null
            ? $documents->first()
            : (clone $scope)
                ->with('user:id,name,email,student_id,program')
                ->whereKey($selectedDocumentId)
                ->first();

        $comments = $selectedDocument === null
            ? collect()
            : DocumentReviewComment::query()
                ->with([
                    'author:id,name',
                    'resolver:id,name',
                ])
                ->where('document_id', $selectedDocument->getKey())
                ->oldest()
                ->get();

        $assignedDocumentIds = (clone $scope)->select('documents.id');

        return [
            'reviewDocuments' => $documents,
            'selectedReviewDocument' => $selectedDocument,
            'documentReviewComments' => $comments,
            'documentReviewStats' => [
                'approved' => (clone $scope)
                    ->where('status', DocumentStatus::Accepted->value)
                    ->count(),
                'revisions' => (clone $scope)
                    ->where('status', DocumentStatus::RevisionRequested->value)
                    ->count(),
                'comments' => DocumentReviewComment::query()
                    ->whereIn('document_id', clone $assignedDocumentIds)
                    ->count(),
                'critical' => DocumentReviewComment::query()
                    ->whereIn('document_id', clone $assignedDocumentIds)
                    ->where('severity', 'critical')
                    ->whereNull('resolved_at')
                    ->count(),
            ],
            'documentReviewSearch' => $search,
            'documentReviewStatus' => $status,
        ];
    }
}
