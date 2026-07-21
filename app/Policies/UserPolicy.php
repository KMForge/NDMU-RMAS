<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function view(User $user, User $subject): bool
    {
        return $user->is($subject) || $user->can('users.manage');
    }

    public function update(User $user, User $subject): bool
    {
        return $user->is($subject) || $user->can('users.manage');
    }

    public function manageRoles(User $user, User $subject): bool
    {
        return ! $user->is($subject) && $user->can('users.manage');
    }
}
