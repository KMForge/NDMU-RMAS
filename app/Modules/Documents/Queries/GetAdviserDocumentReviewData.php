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
        $statusCounts = (clone $scope)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $commentCounts = DocumentReviewComment::query()
            ->whereIn('document_id', clone $assignedDocumentIds)
            ->selectRaw('COUNT(*) as comments_count')
            ->selectRaw(
                "SUM(CASE WHEN severity = 'critical' AND resolved_at IS NULL THEN 1 ELSE 0 END) as critical_count",
            )
            ->first();

        return [
            'reviewDocuments' => $documents,
            'selectedReviewDocument' => $selectedDocument,
            'documentReviewComments' => $comments,
            'documentReviewStats' => [
                'approved' => (int) $statusCounts->get(DocumentStatus::Accepted->value, 0),
                'revisions' => (int) $statusCounts->get(DocumentStatus::RevisionRequested->value, 0),
                'comments' => (int) ($commentCounts?->comments_count ?? 0),
                'critical' => (int) ($commentCounts?->critical_count ?? 0),
            ],
            'documentReviewSearch' => $search,
            'documentReviewStatus' => $status,
        ];
    }
}
