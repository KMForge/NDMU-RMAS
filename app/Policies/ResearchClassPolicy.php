<?php

namespace App\Policies;

use App\Models\ResearchClass;
use App\Models\User;

class ResearchClassPolicy
{
    public function view(User $user, ResearchClass $researchClass): bool
    {
        return $user->can('classes.view-own')
            && $researchClass->adviser_id === $user->getKey();
    }

    public function manageJoinRequests(User $user, ResearchClass $researchClass): bool
    {
        return $user->can('classes.manage-join-requests')
            && $researchClass->adviser_id === $user->getKey();
    }
}
