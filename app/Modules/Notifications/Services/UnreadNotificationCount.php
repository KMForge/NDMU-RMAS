<?php

namespace App\Modules\Notifications\Services;

use App\Models\User;

class UnreadNotificationCount
{
    /** @var array<int, int> */
    private array $counts = [];

    public function for(User $user): int
    {
        $userId = (int) $user->getKey();

        return $this->counts[$userId] ??= $user->unreadNotifications()->count();
    }
}
