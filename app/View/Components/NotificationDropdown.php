<?php

namespace App\View\Components;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class NotificationDropdown extends Component
{
    /** @var Collection<int, DatabaseNotification> */
    public Collection $recentNotifications;

    public int $unreadCount = 0;

    public function __construct(public int $limit = 5)
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            $this->recentNotifications = collect();

            return;
        }

        $this->unreadCount = $user->unreadNotifications()->count();
        $this->recentNotifications = $user->notifications()
            ->latest()
            ->limit(max(1, min($this->limit, 10)))
            ->get();
    }

    public function render(): View
    {
        return view('components.notification-dropdown');
    }
}
