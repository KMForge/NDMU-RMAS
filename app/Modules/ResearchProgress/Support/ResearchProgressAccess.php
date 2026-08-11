<?php

namespace App\Modules\ResearchProgress\Support;

use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserHistory;
use App\Models\ResearchClassGroupMember;
use App\Models\ResearchClassGroupMemberHistory;
use App\Models\User;

class ResearchProgressAccess
{
    public function canView(User $user, ResearchClassGroup $group): bool
    {
        $group->loadMissing('researchClass');

        if ($user->can('progress.view-own')) {
            $member = ResearchClassGroupMember::query()->where('research_class_group_id', $group->getKey())->where('student_id', $user->getKey())->exists();
            $former = ResearchClassGroupMemberHistory::query()->where('research_class_group_id', $group->getKey())->where('student_id', $user->getKey())->exists();
            if ($member || $former) {
                return true;
            }
        }

        if ($user->can('progress.view-assigned')) {
            if ($group->adviser_id === $user->getKey()) {
                return true;
            }
            if (! $group->isActive() && ResearchClassGroupAdviserHistory::query()
                ->where('research_class_group_id', $group->getKey())->where('adviser_id', $user->getKey())->exists()) {
                return true;
            }
        }

        if ($user->can('progress.view-owned-classes') && $group->researchClass?->facilitator_id === $user->getKey()) {
            return true;
        }

        return $user->can('progress.view-all');
    }

    public function canManage(User $user, ResearchClassGroup $group): bool
    {
        $group->loadMissing('researchClass');

        return $group->isActive()
            && $user->can('progress.manage-owned-classes')
            && $group->researchClass?->facilitator_id === $user->getKey();
    }
}
