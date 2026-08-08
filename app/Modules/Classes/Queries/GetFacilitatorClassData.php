<?php

namespace App\Modules\Classes\Queries;

use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GetFacilitatorClassData
{
    /** @return array<string, mixed> */
    public function for(User $facilitator, mixed $search = null, mixed $status = null): array
    {
        $classes = ResearchClass::query()
            ->where('facilitator_id', $facilitator->getKey())
            ->withCount([
                'enrollments as active_students_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->latest()
            ->get();

        $requestSearch = Str::limit(strip_tags((string) $search), 100, '');
        $requestStatus = in_array($status, ['pending', 'active', 'rejected', 'all'], true)
            ? (string) $status
            : 'pending';

        $ownedClassIds = $classes->pluck('id');
        $baseRequestQuery = ResearchClassEnrollment::query()
            ->whereIn('research_class_id', $ownedClassIds)
            ->whereIn('status', ['pending', 'active', 'rejected']);

        $classRequestStats = [
            'pending' => (clone $baseRequestQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseRequestQuery)->where('status', 'active')->count(),
            'rejected' => (clone $baseRequestQuery)->where('status', 'rejected')->count(),
            'total' => (clone $baseRequestQuery)->count(),
        ];

        $joinRequests = (clone $baseRequestQuery)
            ->with([
                'researchClass:id,name,facilitator_id',
                'student:id,name,email,student_id,program,year_level',
            ])
            ->when($requestStatus !== 'all', fn ($query) => $query->where('status', $requestStatus))
            ->when($requestSearch !== '', function ($query) use ($requestSearch): void {
                $query->where(function ($query) use ($requestSearch): void {
                    $query
                        ->whereHas('student', function ($query) use ($requestSearch): void {
                            $query
                                ->where('name', 'like', "%{$requestSearch}%")
                                ->orWhere('email', 'like', "%{$requestSearch}%")
                                ->orWhere('student_id', 'like', "%{$requestSearch}%");
                        })
                        ->orWhereHas('researchClass', function ($query) use ($requestSearch): void {
                            $query->where('name', 'like', "%{$requestSearch}%");
                        });
                });
            })
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 WHEN 'rejected' THEN 2 ELSE 3 END")
            ->latest('requested_at')
            ->limit(100)
            ->get();

        return [
            'researchClasses' => $classes,
            'classJoinRequests' => $joinRequests,
            'classRequestStats' => $classRequestStats,
            'requestStats' => $classRequestStats,
            'requestSearch' => $requestSearch,
            'requestStatus' => $requestStatus,
            'classAdviserOptions' => new Collection,
        ];
    }
}
