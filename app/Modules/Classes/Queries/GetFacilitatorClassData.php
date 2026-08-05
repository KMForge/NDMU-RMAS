<?php

namespace App\Modules\Classes\Queries;

use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\User;
use Illuminate\Support\Str;

class GetFacilitatorClassData
{
    /** @return array<string, mixed> */
    public function for(
        User $facilitator,
        ?string $search = null,
        string $status = 'all',
    ): array {
        $classes = ResearchClass::query()
            ->where('facilitator_id', $facilitator->getKey())
            ->withCount([
                'enrollments as active_students_count' => fn ($query) => $query->where('status', 'active'),
                'enrollments as pending_join_requests_count' => fn ($query) => $query->where('status', 'pending'),
                'groups',
            ])
            ->with([
                'groups.adviser:id,name,email',
                'groups.members.student:id,name,email,student_id,program,year_level',
            ])
            ->latest()
            ->get();

        $joinRequestScope = ResearchClassEnrollment::query()
            ->whereHas('researchClass', fn ($query) => $query->where('facilitator_id', $facilitator->getKey()));
        $counts = (clone $joinRequestScope)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $search = Str::limit(trim((string) $search), 100, '');
        $allowedStatuses = ['pending', 'active', 'rejected', 'all'];
        $status = in_array($status, $allowedStatuses, true) ? $status : 'all';
        $requests = (clone $joinRequestScope)
            ->with([
                'researchClass:id,facilitator_id,name',
                'student:id,name,email,student_id,program,year_level',
            ])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $pattern = '%'.Str::lower($search).'%';

                $query->where(function ($requestQuery) use ($pattern): void {
                    $requestQuery
                        ->whereHas('student', fn ($studentQuery) => $studentQuery
                            ->whereRaw('LOWER(name) LIKE ?', [$pattern])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$pattern])
                            ->orWhereRaw('LOWER(student_id) LIKE ?', [$pattern]))
                        ->orWhereHas('researchClass', fn ($classQuery) => $classQuery
                            ->whereRaw('LOWER(name) LIKE ?', [$pattern]));
                });
            })
            ->latest('requested_at')
            ->get();
        $requestStats = [
            'pending' => (int) $counts->get('pending', 0),
            'approved' => (int) $counts->get('active', 0),
            'rejected' => (int) $counts->get('rejected', 0),
            'total' => (int) $counts->sum(),
        ];

        return [
            'researchClasses' => $classes,
            'classJoinRequests' => $requests,
            'classRequestStats' => $requestStats,
            'requestStats' => $requestStats,
            'requestSearch' => $search,
            'requestStatus' => $status,
            'classAdviserOptions' => User::query()
                ->permission('classes.serve-as-adviser')
                ->where('status', 'active')
                ->whereNotNull('approved_at')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ];
    }
}
