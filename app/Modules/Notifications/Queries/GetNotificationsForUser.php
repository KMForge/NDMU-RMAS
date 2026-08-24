<?php

namespace App\Modules\Notifications\Queries;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetNotificationsForUser
{
    public function execute(User $user, string $filter = 'all'): LengthAwarePaginator
    {
        $query = $user->notifications()->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        return $query->paginate(20)->withQueryString();
    }
}
