<?php

namespace App\Modules\Consultations\Queries;

use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GetAdviserConsultationData
{
    /**
     * @return array{
     *     consultationRequests: LengthAwarePaginator,
     *     consultationRecords: Collection<int, object>,
     *     consultationStats: array{pending: int, approved: int, completed: int, rejected: int, total: int},
     *     consultationSearch: string,
     *     consultationStatus: string
     * }
     */
    public function for(User $adviser, string $search = '', string $status = 'pending'): array
    {
        $search = Str::limit(trim($search), 100, '');
        $status = in_array($status, ['pending', 'approved', 'completed', 'rejected', 'all'], true)
            ? $status
            : 'pending';

        if (! $this->tablesExist([
            'consultation_requests',
            'adviser_assignments',
            'faculty_profiles',
            'users',
            'research_projects',
        ])) {
            return $this->emptyResult($search, $status);
        }

        $scope = $this->assignedRequests($adviser);
        $statusCounts = (clone $scope)
            ->selectRaw('consultation_requests.status, COUNT(*) as aggregate')
            ->groupBy('consultation_requests.status')
            ->pluck('aggregate', 'status');

        $requests = (clone $scope)
            ->leftJoin('users as students', 'students.id', '=', 'consultation_requests.requested_by')
            ->leftJoin('research_projects as projects', 'projects.id', '=', 'consultation_requests.research_project_id')
            ->when(
                $status !== 'all',
                fn (Builder $query) => $query->where('consultation_requests.status', $status),
            )
            ->when($search !== '', function (Builder $query) use ($search): void {
                $pattern = '%'.Str::lower($search).'%';

                $query->where(function (Builder $searchQuery) use ($pattern): void {
                    $searchQuery
                        ->whereRaw('LOWER(students.name) LIKE ?', [$pattern])
                        ->orWhereRaw('LOWER(students.email) LIKE ?', [$pattern])
                        ->orWhereRaw('LOWER(projects.title) LIKE ?', [$pattern])
                        ->orWhereRaw('LOWER(consultation_requests.agenda) LIKE ?', [$pattern]);
                });
            })
            ->select([
                'consultation_requests.*',
                'students.name as student_name',
                'students.email as student_email',
                'projects.title as research_title',
            ])
            ->orderByRaw("CASE WHEN consultation_requests.status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('consultation_requests.preferred_at')
            ->paginate(10, ['*'], 'consultations_page')
            ->withQueryString();

        return [
            'consultationRequests' => $requests,
            'consultationRecords' => $this->recordsFor($adviser),
            'consultationStats' => [
                'pending' => (int) $statusCounts->get('pending', 0),
                'approved' => (int) $statusCounts->get('approved', 0),
                'completed' => (int) $statusCounts->get('completed', 0),
                'rejected' => (int) $statusCounts->get('rejected', 0),
                'total' => (int) $statusCounts->sum(),
            ],
            'consultationSearch' => $search,
            'consultationStatus' => $status,
        ];
    }

    private function assignedRequests(User $adviser): Builder
    {
        return ConsultationRequest::query()
            ->join(
                'adviser_assignments as consultation_assignments',
                'consultation_assignments.id',
                '=',
                'consultation_requests.adviser_assignment_id',
            )
            ->join(
                'faculty_profiles as consultation_faculty',
                'consultation_faculty.id',
                '=',
                'consultation_assignments.adviser_id',
            )
            ->where('consultation_faculty.user_id', $adviser->getKey())
            ->where('consultation_assignments.status', 'active')
            ->whereNull('consultation_assignments.ended_at');
    }

    /**
     * @return Collection<int, object>
     */
    private function recordsFor(User $adviser): Collection
    {
        if (! $this->tablesExist(['consultation_records'])) {
            return collect();
        }

        return DB::table('consultation_records as records')
            ->join('adviser_assignments as assignments', 'assignments.id', '=', 'records.adviser_assignment_id')
            ->join('faculty_profiles as faculty', 'faculty.id', '=', 'assignments.adviser_id')
            ->leftJoin('research_projects as projects', 'projects.id', '=', 'records.research_project_id')
            ->where('faculty.user_id', $adviser->getKey())
            ->select([
                'records.id',
                'records.consulted_at',
                'records.consultation_mode',
                'records.location',
                'records.agenda',
                'records.discussion',
                'records.recommendations',
                'records.next_consultation_at',
                'projects.title as research_title',
            ])
            ->latest('records.consulted_at')
            ->limit(20)
            ->get();
    }

    /**
     * @return array{
     *     consultationRequests: LengthAwarePaginator,
     *     consultationRecords: Collection<int, object>,
     *     consultationStats: array{pending: int, approved: int, completed: int, rejected: int, total: int},
     *     consultationSearch: string,
     *     consultationStatus: string
     * }
     */
    private function emptyResult(string $search, string $status): array
    {
        return [
            'consultationRequests' => new Paginator([], 0, 10),
            'consultationRecords' => collect(),
            'consultationStats' => [
                'pending' => 0,
                'approved' => 0,
                'completed' => 0,
                'rejected' => 0,
                'total' => 0,
            ],
            'consultationSearch' => $search,
            'consultationStatus' => $status,
        ];
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function tablesExist(array $tables): bool
    {
        return collect($tables)->every(
            fn (string $table): bool => Schema::hasTable($table),
        );
    }
}
