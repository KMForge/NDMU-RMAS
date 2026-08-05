<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserSignature;

class UserSignaturePolicy
{
    public function create(User $user): bool
    {
        return ! $user->hasRole('system-administrator');
    }

    public function view(User $user, UserSignature $signature): bool
    {
        return $this->create($user) && $signature->user_id === $user->getKey();
    }

    public function delete(User $user, UserSignature $signature): bool
    {
        return $this->view($user, $signature);
    }
}
