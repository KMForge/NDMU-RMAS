<?php

namespace App\Policies;

use App\Models\DefenseEvaluationRound;
use App\Models\User;

class DefenseEvaluationRoundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('evaluations.view-own')
            || $user->can('evaluations.view-assigned')
            || $user->can('evaluations.create')
            || $user->can('evaluations.release')
            || $user->can('defenses.manage');
    }

    public function view(User $user, DefenseEvaluationRound $round): bool
    {
        if ($user->can('defenses.manage') || $user->can('evaluations.release')) {
            $class = $round->defense?->group?->researchClass;

            return $class && (int) $class->facilitator_id === (int) $user->id;
        }

        if ($user->can('evaluations.create')) {
            return $round->roundPanelists()->where('panelist_user_id', $user->id)->exists();
        }

        if ($user->can('evaluations.view-assigned')) {
            return (int) $round->defense?->group?->adviser_id === (int) $user->id;
        }

        return $round->status === 'released' && $round->roundStudents()->where('student_id', $user->id)->exists();
    }

    public function open(User $user): bool
    {
        return $user->can('defenses.manage');
    }

    public function evaluate(User $user, DefenseEvaluationRound $round): bool
    {
        return $user->can('evaluations.create')
            && in_array($round->status, ['open', 'in_progress'], true)
            && $round->roundPanelists()->where('panelist_user_id', $user->id)->exists();
    }

    public function release(User $user, DefenseEvaluationRound $round): bool
    {
        return $user->can('evaluations.release') && $round->status === 'finalized';
    }
}
