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
        string $status = 'needs_attention',
        ?int $selectedDocumentId = null,
    ): array {
        $search = Str::limit(trim($search), 100, '');
        $status = in_array($status, [
            'needs_attention',
            'pending',
            'under_review',
            'revision_requested',
            'accepted',
            'rejected',
            'all',
        ], true) ? $status : 'needs_attention';

        $scope = $this->reviewerAccess->scopeFor(Document::query(), $reviewer);
        $documents = (clone $scope)
            ->with([
                'user:id,name,email,student_id,program',
                'researchClassGroup:id,name,leader_student_id',
                'researchClassGroup.leader:id,name',
            ])
            ->when(
                $status !== 'all',
                function (Builder $query) use ($status): void {
                    if ($status === 'needs_attention') {
                        $query->where('is_current', true)
                            ->whereIn('status', [
                                DocumentStatus::Pending->value,
                                DocumentStatus::Submitted->value,
                                DocumentStatus::UnderReview->value,
                            ]);
                    } elseif ($status === 'pending') {
                        $query->whereIn('status', [
                            DocumentStatus::Pending->value,
                            DocumentStatus::Submitted->value,
                        ]);
                    } else {
                        $query->where('status', $status);
                    }
                },
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
                        })
                        ->orWhereHas('researchClassGroup', function (Builder $groupQuery) use ($pattern): void {
                            $groupQuery->whereRaw('LOWER(name) LIKE ?', [$pattern]);
                        });
                });
            })
            ->latest('submitted_at')
            ->paginate(10, ['*'], 'documents_page')
            ->withQueryString();

        $selectedDocument = $selectedDocumentId === null
            ? $documents->first()
            : (clone $scope)
                ->with([
                    'user:id,name,email,student_id,program',
                    'researchClassGroup:id,name,leader_student_id,adviser_id',
                    'researchClassGroup.leader:id,name',
                    'reviews' => fn ($query) => $query->with(['reviewer:id,name', 'supersedes'])->latest('reviewed_at'),
                ])
                ->whereKey($selectedDocumentId)
                ->first();

        if ($selectedDocument !== null && ! $selectedDocument->relationLoaded('reviews')) {
            $selectedDocument->load([
                'reviews' => fn ($query) => $query->with(['reviewer:id,name', 'supersedes'])->latest('reviewed_at'),
            ]);
        }

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
