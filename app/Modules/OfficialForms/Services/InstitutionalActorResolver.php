<?php

namespace App\Modules\OfficialForms\Services;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use Illuminate\Support\Collection;

class InstitutionalActorResolver
{
    /** @var Collection<int, User>|null */
    private ?Collection $eligibleDeans = null;

    /** @var array<int, User|null> */
    private array $coordinatorsByDepartment = [];

    private bool $fallbackCoordinatorResolved = false;

    private ?User $fallbackCoordinator = null;

    /**
     * Resolve the single authoritative College Dean for the institution.
     *
     * NDMU-RMAS currently serves one college. Returning null when zero or more
     * than one eligible Dean exists makes academic approval fail closed rather
     * than silently selecting the wrong user.
     */
    public function dean(): ?User
    {
        $candidates = $this->deanCandidates();

        return $candidates->count() === 1 ? $candidates->first() : null;
    }

    public function isDean(User $user): bool
    {
        return (int) $this->dean()?->getKey() === (int) $user->getKey();
    }

    public function hasConfiguredDean(): bool
    {
        return $this->deanCandidates()->isNotEmpty();
    }

    /**
     * Resolve the Program Coordinator for a given research class or group based on its department.
     */
    public function programCoordinatorForClass(?ResearchClass $class): ?User
    {
        if ($class === null) {
            return null;
        }

        // A class belongs to the students' academic program even when its
        // facilitator's home department is elsewhere.
        $student = $class->groups->first()?->members->first()?->student;
        $deptId = $student?->studentProfile?->program?->department_id;

        // Classes without an enrolled group fall back to the facilitator's
        // primary department until a program can establish class scope.
        $deptId ??= $class->facilitator?->facultyProfile?->department_id;

        if ($deptId !== null) {
            return $this->programCoordinatorForDepartmentId($deptId);
        }

        // Fallback: return any active faculty with program-coordinator role
        if ($this->fallbackCoordinatorResolved) {
            return $this->fallbackCoordinator;
        }

        $this->fallbackCoordinatorResolved = true;

        return $this->fallbackCoordinator = User::query()
            ->where('user_type', UserType::Faculty)
            ->where('status', AccountStatus::Active)
            ->whereNotNull('approved_at')
            ->whereNotNull('email_verified_at')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['program-coordinator', 'department-chair']))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
    }

    public function programCoordinatorForGroup(?ResearchClassGroup $group): ?User
    {
        if ($group === null) {
            return null;
        }

        // Check group's research class
        if ($group->researchClass !== null) {
            $coord = $this->programCoordinatorForClass($group->researchClass);
            if ($coord !== null) {
                return $coord;
            }
        }

        // Check student member's program department
        $student = $group->members->first()?->student;
        $deptId = $student?->studentProfile?->program?->department_id;
        if ($deptId !== null) {
            return $this->programCoordinatorForDepartmentId($deptId);
        }

        return null;
    }

    public function isProgramCoordinator(User $user, ?ResearchClass $class = null, ?ResearchClassGroup $group = null): bool
    {
        if ($user->user_type !== UserType::Faculty || $user->status !== AccountStatus::Active) {
            return false;
        }

        if ($user->hasRole('program-coordinator') || $user->hasRole('department-chair') || $user->can('forms.res-041.receive')) {
            if ($class !== null) {
                $coord = $this->programCoordinatorForClass($class);
                if ($coord !== null) {
                    return (int) $coord->id === (int) $user->id;
                }
            }
            if ($group !== null) {
                $coord = $this->programCoordinatorForGroup($group);
                if ($coord !== null) {
                    return (int) $coord->id === (int) $user->id;
                }
            }

            return true;
        }

        return false;
    }

    public function hasConfiguredProgramCoordinator(?ResearchClass $class = null, ?ResearchClassGroup $group = null): bool
    {
        if ($group !== null && $this->programCoordinatorForGroup($group) !== null) {
            return true;
        }

        if ($class !== null && $this->programCoordinatorForClass($class) !== null) {
            return true;
        }

        return false;
    }

    public function programCoordinatorForDepartmentId(int $departmentId): ?User
    {
        if (array_key_exists($departmentId, $this->coordinatorsByDepartment)) {
            return $this->coordinatorsByDepartment[$departmentId];
        }

        return $this->coordinatorsByDepartment[$departmentId] = User::query()
            ->where('user_type', UserType::Faculty)
            ->where('status', AccountStatus::Active)
            ->whereNotNull('approved_at')
            ->whereNotNull('email_verified_at')
            ->whereHas('facultyProfile', fn ($fp) => $fp
                ->where('department_id', $departmentId)
                ->orWhereHas('departments', fn ($departments) => $departments->whereKey($departmentId)))
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['program-coordinator', 'department-chair']))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
    }

    /** @return Collection<int, User> */
    private function deanCandidates(): Collection
    {
        return $this->eligibleDeans ??= User::query()
            ->where('user_type', UserType::Faculty)
            ->where('status', AccountStatus::Active)
            ->whereNotNull('approved_at')
            ->whereNotNull('email_verified_at')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['dean', 'college-dean']))
            ->orderBy('id')
            ->limit(2)
            ->get();
    }
}
