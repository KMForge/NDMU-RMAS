<?php

namespace App\Modules\DefenseScheduling\Services;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\OfficialForms\Services\InstitutionalActorResolver;

class ManageDefenseCommitteeAccess
{
    public function __construct(
        private readonly InstitutionalActorResolver $institutionalActors,
    ) {}

    public function forClass(User $actor, ResearchClass $researchClass): bool
    {
        if ($actor->user_type !== UserType::Faculty || $actor->status !== AccountStatus::Active) {
            return false;
        }

        $isOwningFacilitator = (int) $researchClass->facilitator_id === (int) $actor->id
            && $actor->can('defenses.manage');

        $coordinator = $this->institutionalActors->programCoordinatorForClass($researchClass);
        $isScopedCoordinator = ($actor->hasRole('program-coordinator') || $actor->hasRole('department-chair'))
            && $coordinator !== null
            && (int) $coordinator->id === (int) $actor->id;

        return $isOwningFacilitator || $isScopedCoordinator;
    }

    public function forGroup(User $actor, ResearchClassGroup $group): bool
    {
        return $group->researchClass !== null
            && $this->forClass($actor, $group->researchClass);
    }
}
