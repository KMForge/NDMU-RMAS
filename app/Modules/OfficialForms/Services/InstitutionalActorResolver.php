<?php

namespace App\Modules\OfficialForms\Services;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\Program;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
     * Resolve explicit program scope, with unique-only legacy department fallback.
     */
    public function programCoordinatorForClass(?ResearchClass $class): ?User
    {
        if ($class === null) {
            return null;
        }

        // Explicit class scope also works before any students have enrolled.
        if ($class->program_id !== null) {
            return $this->programCoordinatorForProgramId((int) $class->program_id);
        }

        $programIds = $class->groups->flatMap(fn ($group) => $group->members->map(fn ($member) => $member->student?->studentProfile?->program_id))->filter()->unique();
        if ($programIds->count() === 1) {
            return $this->programCoordinatorForProgramId((int) $programIds->first());
        }
        if ($programIds->count() > 1) {
            return null;
        }
        $deptId = null;

        // Classes without an enrolled group fall back to the facilitator's
        // primary department until a program can establish class scope.
        $deptId ??= $class->facilitator?->facultyProfile?->department_id;

        if ($deptId !== null) {
            return $this->programCoordinatorForDepartmentId($deptId);
        }

        // Legacy unscoped classes may use a coordinator only when unique.
        if ($this->fallbackCoordinatorResolved) {
            return $this->fallbackCoordinator;
        }

        $this->fallbackCoordinatorResolved = true;

        $candidates = $this->coordinatorCandidates()->limit(2)->get();

        return $this->fallbackCoordinator = $candidates->count() === 1 ? $candidates->first() : null;
    }

    public function programCoordinatorForGroup(?ResearchClassGroup $group): ?User
    {
        if ($group === null) {
            return null;
        }

        if ($group->researchClass?->program_id !== null) {
            return $this->programCoordinatorForProgramId((int) $group->researchClass->program_id);
        }

        $programIds = $group->members->map(fn ($member) => $member->student?->studentProfile?->program_id)->filter()->unique();
        if ($programIds->count() === 1) {
            return $this->programCoordinatorForProgramId((int) $programIds->first());
        }

        return $programIds->isEmpty() ? $this->programCoordinatorForClass($group->researchClass) : null;
    }

    public function isProgramCoordinator(User $user, ?ResearchClass $class = null, ?ResearchClassGroup $group = null): bool
    {
        if ($user->user_type !== UserType::Faculty || $user->status !== AccountStatus::Active) {
            return false;
        }

        if ($user->hasRole('program-coordinator') || $user->hasRole('department-chair') || $user->can('forms.res-041.receive')) {
            if ($group !== null) {
                return (int) $this->programCoordinatorForGroup($group)?->id === (int) $user->id;
            }
            if ($class !== null) {
                return (int) $this->programCoordinatorForClass($class)?->id === (int) $user->id;
            }

            return true;
        }

        return false;
    }

    public function hasConfiguredProgramCoordinator(?ResearchClass $class = null, ?ResearchClassGroup $group = null): bool
    {
        // Configured but invalid/ambiguous scope must also close legacy actor overrides.
        if ($group !== null) {
            $class = $group->researchClass;
            $programIds = $group->members->map(fn ($member) => $member->student?->studentProfile?->program_id)->filter()->unique();
            if ($programIds->isNotEmpty()) {
                return true;
            }
        }
        if ($class?->program_id !== null || ($class !== null && $class->groups->contains(fn ($candidate) => $candidate->members->contains(fn ($member) => $member->student?->studentProfile?->program_id !== null)))) {
            return true;
        }
        if ($this->coordinatorCandidates()->exists()) {
            return true;
        }
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

        $candidates = $this->coordinatorCandidates()
            ->whereHas('facultyProfile', fn ($fp) => $fp
                ->where('department_id', $departmentId)
                ->orWhereHas('departments', fn ($departments) => $departments->whereKey($departmentId)))
            ->limit(2)->get();

        return $this->coordinatorsByDepartment[$departmentId] = $candidates->count() === 1 ? $candidates->first() : null;
    }

    public function programCoordinatorForProgramId(int $programId): ?User
    {
        $program = Program::query()->find($programId);
        if ($program === null) {
            return null;
        }
        if ($program->coordinator_user_id === null) {
            return $this->programCoordinatorForDepartmentId((int) $program->department_id);
        }

        // A stale or deactivated explicit assignment must not select a substitute.
        return $this->coordinatorCandidates()->whereKey($program->coordinator_user_id)
            ->whereHas('facultyProfile', fn ($query) => $query->where('department_id', $program->department_id)
                ->orWhereHas('departments', fn ($departments) => $departments->whereKey($program->department_id)))
            ->first();
    }

    private function coordinatorCandidates(): Builder
    {
        return User::query()
            ->where('user_type', UserType::Faculty)
            ->where('status', AccountStatus::Active)
            ->whereNotNull('approved_at')
            ->whereNotNull('email_verified_at')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['program-coordinator', 'department-chair']));
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
