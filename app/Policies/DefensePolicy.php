<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\Defense;
use App\Models\ResearchClassGroup;
use App\Models\User;

class DefensePolicy
{
    public function view(User $user, Defense $defense): bool
    {
        if ($user->status !== AccountStatus::Active) {
            return false;
        }

        if (! $user->can('defenses.view')) {
            return false;
        }

        $group = $defense->group;
        if (! $group) {
            return false;
        }

        // Student Researcher: Enrolled active group member
        if ($user->user_type === UserType::Student) {
            return $group->enrollments()
                ->where('student_id', $user->id)
                ->where('status', 'active')
                ->exists();
        }

        if ($user->user_type === UserType::Faculty) {
            // Thesis Adviser
            if ((int) $group->adviser_id === (int) $user->id) {
                return true;
            }

            // Research Class Facilitator
            if ($group->researchClass && (int) $group->researchClass->facilitator_id === (int) $user->id) {
                return true;
            }

            // Defense Panel Member
            return $defense->activePanelAssignments()
                ->where('user_id', $user->id)
                ->exists();
        }

        // System Admin read-only visibility
        if ($user->user_type === UserType::Admin && $user->can('research.view-all')) {
            return true;
        }

        return false;
    }

    public function schedule(User $user, ResearchClassGroup $group): bool
    {
        if ($user->user_type !== UserType::Faculty) {
            return false;
        }

        if ($user->status !== AccountStatus::Active) {
            return false;
        }

        if (! $user->can('defenses.manage')) {
            return false;
        }

        return $group->researchClass && (int) $group->researchClass->facilitator_id === (int) $user->id;
    }

    public function manage(User $user, Defense $defense): bool
    {
        if ($user->user_type !== UserType::Faculty) {
            return false;
        }

        if ($user->status !== AccountStatus::Active) {
            return false;
        }

        if (! $user->can('defenses.manage')) {
            return false;
        }

        $group = $defense->group;
        if (! $group || ! $group->researchClass) {
            return false;
        }

        return (int) $group->researchClass->facilitator_id === (int) $user->id;
    }
}
