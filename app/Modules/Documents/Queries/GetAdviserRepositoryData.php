<?php

namespace App\Modules\Documents\Queries;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Support\DocumentReviewerAccess;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class GetAdviserRepositoryData
{
    public function __construct(
        private readonly DocumentReviewerAccess $reviewerAccess,
    ) {}

    /**
     * @return array{
     *     repositoryDocuments: LengthAwarePaginator,
     *     repositoryStats: array{total: int, approved: int, pending: int, evaluation: int},
     *     repositorySearch: string,
     *     repositoryStatus: string
     * }
     */
    public function for(User $adviser, string $search = '', string $status = 'all'): array
    {
        $search = Str::limit(trim($search), 100, '');
        $status = in_array($status, [
            'all',
            'approved',
            'pending',
            'evaluation',
            'revisions',
            'rejected',
        ], true) ? $status : 'all';

        $scope = $this->scopeFor($adviser);
        $statusCounts = (clone $scope)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $documents = (clone $scope)
            ->with('user:id,name,email,student_id,program')
            ->when($status !== 'all', function (Builder $query) use ($status): void {
                $statuses = match ($status) {
                    'approved' => [DocumentStatus::Accepted->value],
                    'pending' => [
                        DocumentStatus::Pending->value,
                        DocumentStatus::Submitted->value,
                    ],
                    'evaluation' => [DocumentStatus::UnderReview->value],
                    'revisions' => [DocumentStatus::RevisionRequested->value],
                    'rejected' => [DocumentStatus::Rejected->value],
                };

                $query->whereIn('status', $statuses);
            })
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
            ->paginate(9, ['*'], 'repository_page')
            ->withQueryString();

        return [
            'repositoryDocuments' => $documents,
            'repositoryStats' => [
                'total' => (int) $statusCounts->sum(),
                'approved' => (int) $statusCounts->get(DocumentStatus::Accepted->value, 0),
                'pending' => (int) $statusCounts->get(DocumentStatus::Pending->value, 0)
                    + (int) $statusCounts->get(DocumentStatus::Submitted->value, 0),
                'evaluation' => (int) $statusCounts->get(DocumentStatus::UnderReview->value, 0),
            ],
            'repositorySearch' => $search,
            'repositoryStatus' => $status,
        ];
    }

    /**
     * @return Builder<Document>
     */
    private function scopeFor(User $adviser): Builder
    {
        return Document::query()
            ->where(function (Builder $query) use ($adviser): void {
                $query
                    ->where('user_id', $adviser->getKey())
                    ->orWhere(function (Builder $assignedQuery) use ($adviser): void {
                        $this->reviewerAccess->scopeFor($assignedQuery, $adviser);
                    });
            });
    }
}
