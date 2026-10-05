<?php

namespace App\Modules\Classes\Queries;

use App\Enums\AccountStatus;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\User;
use App\Modules\OfficialForms\Services\InstitutionalActorResolver;
use Illuminate\Support\Str;

class GetFacilitatorClassData
{
    public function __construct(
        private readonly InstitutionalActorResolver $institutionalActors = new InstitutionalActorResolver,
    ) {}

    /** @return array<string, mixed> */
    public function for(User $facilitator, mixed $search = null, mixed $status = null, string $activeTab = 'dashboard'): array
    {
        $canAssignAdvisers = $facilitator->can('classes.assign-advisers');
        $classesQuery = ResearchClass::query()
            ->when(! $canAssignAdvisers, fn ($query) => $query->where('facilitator_id', $facilitator->getKey()));
        if (in_array($activeTab, ['dashboard', 'classes'], true)) {
            if ($canAssignAdvisers) {
                $classesQuery->with(['facilitator.facultyProfile', 'groups.members.student.studentProfile.program']);
            }
            $classesQuery->withCount([
                'enrollments as active_students_count' => fn ($query) => $query->where('status', 'active'),
                'groups as active_groups_count' => fn ($query) => $query->where('status', 'active'),
            ])
                ->latest();
        } else {
            $classesQuery->select(['id', 'facilitator_id']);
            if ($canAssignAdvisers) {
                $classesQuery->with(['facilitator.facultyProfile', 'groups.members.student.studentProfile.program']);
            }
        }
        $classes = $classesQuery
            ->get()
            ->filter(fn (ResearchClass $class): bool => (int) $class->facilitator_id === (int) $facilitator->getKey()
                || ($canAssignAdvisers
                    && $this->institutionalActors->isProgramCoordinator($facilitator, $class)))
            ->values();

        $requestSearch = Str::limit(strip_tags((string) $search), 100, '');
        $requestStatus = in_array($status, ['pending', 'active', 'rejected', 'all'], true)
            ? (string) $status
            : 'pending';

        $ownedClassIds = $classes->pluck('id');
        $classRequestStats = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'total' => 0,
        ];
        $joinRequests = collect();

        if ($ownedClassIds->isNotEmpty()) {
            $baseRequestQuery = ResearchClassEnrollment::query()
                ->whereIn('research_class_id', $ownedClassIds)
                ->whereIn('status', ['pending', 'active', 'rejected']);

            $counts = (clone $baseRequestQuery)
                ->selectRaw("COUNT(*) AS total, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS approved, SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected")
                ->first();
            $classRequestStats = [
                'pending' => (int) ($counts?->pending ?? 0),
                'approved' => (int) ($counts?->approved ?? 0),
                'rejected' => (int) ($counts?->rejected ?? 0),
                'total' => (int) ($counts?->total ?? 0),
            ];

            if ($activeTab === 'join-requests') {
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
            }
        }

        $advisers = $activeTab === 'dashboard'
            ? User::query()
                ->permission('classes.serve-as-adviser')
                ->where('status', AccountStatus::Active)
                ->whereNotNull('approved_at')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'department'])
            : collect();

        return [
            'researchClasses' => $classes,
            'classJoinRequests' => $joinRequests,
            'classRequestStats' => $classRequestStats,
            'requestStats' => $classRequestStats,
            'requestSearch' => $requestSearch,
            'requestStatus' => $requestStatus,
            'classAdviserOptions' => $advisers,
        ];
    }
}
