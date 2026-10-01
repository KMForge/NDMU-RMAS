<?php

namespace App\Policies;

use App\Models\ResearchClass;
use App\Models\User;
use App\Modules\OfficialForms\Services\InstitutionalActorResolver;

class ResearchClassPolicy
{
    public function __construct(
        private readonly InstitutionalActorResolver $institutionalActors = new InstitutionalActorResolver,
    ) {}

    public function view(User $user, ResearchClass $researchClass): bool
    {
        if ($user->can('classes.view-own') && $researchClass->facilitator_id === $user->getKey()) {
            return true;
        }

        if ($user->can('classes.assign-advisers')
            && $this->institutionalActors->isProgramCoordinator($user, $researchClass)) {
            return true;
        }

        return $user->can('classes.view-assigned')
            && $researchClass->groups()
                ->where('status', 'active')
                ->where('adviser_id', $user->getKey())
                ->exists();
    }

    public function manageJoinRequests(User $user, ResearchClass $researchClass): bool
    {
        return $user->can('classes.manage-join-requests')
            && $researchClass->facilitator_id === $user->getKey();
    }

    public function viewEnrolled(User $user, ResearchClass $researchClass): bool
    {
        return $user->can('classes.view-enrolled')
            && $researchClass->enrollments()
                ->where('student_id', $user->getKey())
                ->where('status', 'active')
                ->exists();
    }
}
