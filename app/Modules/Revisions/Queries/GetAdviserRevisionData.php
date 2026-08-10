<?php

namespace App\Modules\Revisions\Queries;

use App\Models\RevisionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GetAdviserRevisionData
{
    /**
     * @return array<string, mixed>
     */
    public function for(User $adviser, mixed $searchInput = null, mixed $statusInput = null): array
    {
        if (! Schema::hasTable('revision_requests')) {
            return [
                'revisionRequests' => new LengthAwarePaginator([], 0, 10),
                'revisionStats' => [
                    'open' => 0,
                    'in_progress' => 0,
                    'submitted' => 0,
                    'resolved' => 0,
                    'total' => 0,
                ],
                'revisionSearch' => '',
                'revisionStatus' => 'submitted',
            ];
        }

        $scope = RevisionRequest::query()
            ->where(function (Builder $query) use ($adviser): void {
                $query->whereHas('researchClassGroup', fn ($g) => $g->where('adviser_id', $adviser->getKey()))
                    ->orWhere('requested_by', $adviser->getKey());
            });

        $counts = (clone $scope)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $search = Str::limit(trim(is_string($searchInput) ? $searchInput : ''), 100, '');
        $allowedStatuses = ['open', 'in_progress', 'submitted', 'resolved', 'cancelled', 'all'];
        $status = in_array($statusInput, $allowedStatuses, true)
            ? (string) $statusInput
            : 'submitted';

        $revisions = (clone $scope)
            ->with([
                'researchClassGroup:id,name,research_title,adviser_id,status',
                'researchClassGroup.leader:id,name,email',
                'sourceDocument:id,user_id,original_filename,file_type,file_size,document_stage,version_number,submitted_at,status',
                'sourceReview:id,reviewer_id,decision,review_notes,reviewed_at',
                'submittedDocument:id,user_id,original_filename,file_type,file_size,document_stage,version_number,submitted_at,status',
                'requester:id,name,email',
                'events' => fn ($query) => $query->with('actor:id,name')->latest('occurred_at'),
            ])
            ->when(
                $status !== 'all',
                fn (Builder $query) => $query->where('status', $status),
            )
            ->when($search !== '', function (Builder $query) use ($search): void {
                $pattern = '%'.Str::lower($search).'%';

                $query->where(function (Builder $searchQuery) use ($pattern): void {
                    $searchQuery
                        ->whereRaw('LOWER(title) LIKE ?', [$pattern])
                        ->orWhereHas('researchClassGroup', function (Builder $groupQuery) use ($pattern): void {
                            $groupQuery->whereRaw('LOWER(name) LIKE ?', [$pattern])
                                ->orWhereRaw('LOWER(research_title) LIKE ?', [$pattern]);
                        })
                        ->orWhereHas('sourceDocument', function (Builder $docQuery) use ($pattern): void {
                            $docQuery->whereRaw('LOWER(original_filename) LIKE ?', [$pattern]);
                        });
                });
            })
            ->latest()
            ->paginate(10, ['*'], 'revisions_page')
            ->withQueryString();

        return [
            'revisionRequests' => $revisions,
            'revisionStats' => [
                'open' => (int) $counts->get('open', 0),
                'in_progress' => (int) $counts->get('in_progress', 0),
                'submitted' => (int) $counts->get('submitted', 0),
                'resolved' => (int) $counts->get('resolved', 0),
                'total' => (int) $counts->sum(),
            ],
            'revisionSearch' => $search,
            'revisionStatus' => $status,
        ];
    }
}
