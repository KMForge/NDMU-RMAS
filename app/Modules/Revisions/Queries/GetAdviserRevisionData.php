<?php

namespace App\Modules\Revisions\Queries;

use App\Models\RevisionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class GetAdviserRevisionData
{
    /**
     * @return array<string, mixed>
     */
    public function for(User $adviser, mixed $searchInput = null, mixed $statusInput = null): array
    {
        $scope = RevisionRequest::query()
            ->where('requested_by', $adviser->getKey());
        $counts = (clone $scope)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $search = Str::limit(trim(is_string($searchInput) ? $searchInput : ''), 100, '');
        $allowedStatuses = ['open', 'in_progress', 'submitted', 'resolved', 'all'];
        $status = in_array($statusInput, $allowedStatuses, true)
            ? (string) $statusInput
            : 'submitted';

        $revisions = (clone $scope)
            ->with([
                'assignee:id,name,email,student_id,program,year_level',
                'document:id,user_id,original_filename,file_type,file_size,submitted_at,status',
                'submittedDocuments' => fn ($query) => $query
                    ->select([
                        'id',
                        'user_id',
                        'revision_request_id',
                        'original_filename',
                        'file_type',
                        'file_size',
                        'submitted_at',
                        'status',
                    ])
                    ->latest('submitted_at'),
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
                        ->orWhereHas('assignee', function (Builder $studentQuery) use ($pattern): void {
                            $studentQuery->where(function (Builder $identityQuery) use ($pattern): void {
                                $identityQuery
                                    ->whereRaw('LOWER(name) LIKE ?', [$pattern])
                                    ->orWhereRaw('LOWER(email) LIKE ?', [$pattern])
                                    ->orWhereRaw('LOWER(student_id) LIKE ?', [$pattern]);
                            });
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
