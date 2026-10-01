<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\OfficialForms\Services\InstitutionalActorResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class RemoveResearchClassGroupAdviser
{
    public function __construct(
        private readonly InstitutionalActorResolver $institutionalActors,
    ) {}

    public function handle(
        User $coordinator,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
    ): void {
        DB::transaction(function () use ($coordinator, $researchClass, $group): void {
            $lockedClass = ResearchClass::query()->lockForUpdate()->findOrFail($researchClass->getKey());

            $lockedGroup = ResearchClassGroup::query()
                ->whereKey($group->getKey())
                ->where('research_class_id', $lockedClass->getKey())
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            $lockedClass->loadMissing('facilitator.facultyProfile', 'groups.members.student.studentProfile.program');
            $lockedGroup?->loadMissing('researchClass', 'members.student.studentProfile.program');
            if ($lockedGroup === null
                || ! $this->institutionalActors->isProgramCoordinator($coordinator, $lockedClass, $lockedGroup)
                || ! $coordinator->can('classes.assign-advisers')) {
                throw new AuthorizationException('Only the authorized Program Coordinator may manage adviser assignments for this class.');
            }

            if ($lockedGroup->adviser_id === null) {
                throw new ClassOperationException('The group does not currently have an active adviser.');
            }

            throw new ClassOperationException('An active adviser cannot be removed directly. Submit and approve a RES-030 Adviser Change Request Form instead.');
        }, 3);
    }
}
