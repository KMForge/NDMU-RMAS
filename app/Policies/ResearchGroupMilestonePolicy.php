<?php

namespace App\Policies;

use App\Models\ResearchGroupMilestone;
use App\Models\User;
use App\Modules\ResearchProgress\Support\ResearchProgressAccess;

class ResearchGroupMilestonePolicy
{
    public function __construct(private readonly ResearchProgressAccess $access) {}

    public function view(User $user, ResearchGroupMilestone $milestone): bool
    {
        $group = $milestone->group()->with('researchClass')->first();

        return $group !== null && $this->access->canView($user, $group);
    }

    public function manage(User $user, ResearchGroupMilestone $milestone): bool
    {
        $group = $milestone->group()->with('researchClass')->first();

        return $group !== null && $this->access->canManage($user, $group);
    }

    public function overrideOrder(User $user, ResearchGroupMilestone $milestone): bool
    {
        return $this->manage($user, $milestone) && $user->can('progress.override-order');
    }
}
