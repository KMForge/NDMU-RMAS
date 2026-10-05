<?php

namespace App\Modules\Dashboard\Services;

use App\Events\UserLiveStateUpdated;
use App\Models\ResearchClassEnrollment;
use App\Models\User;
use App\Modules\Notifications\Services\NotificationWorkspaceResolver;
use App\Modules\Notifications\Services\UnreadNotificationCount;
use App\Modules\OfficialForms\Services\GetPendingAcademicActionsForUser;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserLiveStateService
{
    public function __construct(
        private readonly UnreadNotificationCount $unreadNotifications,
        private readonly NotificationWorkspaceResolver $workspaceResolver,
        private readonly GetPendingAcademicActionsForUser $pendingActions,
    ) {}

    /**
     * Compute the full live state payload for a user.
     *
     * @return array{unread_notifications: int, recent_notifications: array<int, array<string, mixed>>, badges: array<string, int>}
     */
    public function compute(User $user): array
    {
        $unreadCount = $this->unreadNotifications->for($user);

        $recentNotifications = $user->notifications()
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn ($notification) => [
                'id' => (string) $notification->id,
                'title' => (string) data_get($notification->data, 'title', 'Notification'),
                'message' => (string) data_get($notification->data, 'message', ''),
                'context_label' => (string) data_get($notification->data, 'context_label', 'NDMU-RMAS'),
                'workspace_label' => $this->workspaceResolver->labelForData($notification->data ?? []),
                'target_workspace' => $this->workspaceResolver->fromData($notification->data ?? []),
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at_human' => $notification->created_at?->diffForHumans() ?? 'Just now',
                'open_url' => route('notifications.open', $notification->id),
            ])
            ->all();

        $pendingFormsCount = 0;
        try {
            $pendingFormsCount = $this->pendingActions->execute($user)->count();
        } catch (Throwable $e) {
            Log::warning('Error computing pending actions in UserLiveStateService: '.$e->getMessage());
        }

        $badges = [
            'notifications' => $unreadCount,
            'forms' => $pendingFormsCount,
            'pending' => $pendingFormsCount,
        ];

        // Facilitator counters
        if ($user->can('dashboards.facilitator.view')) {
            try {
                $classIds = $user->facilitatorClasses()->pluck('id');
                $pendingJoinRequests = $classIds->isNotEmpty()
                    ? ResearchClassEnrollment::query()
                        ->whereIn('research_class_id', $classIds)
                        ->where('status', 'pending')
                        ->count()
                    : 0;

                $badges['join-requests'] = $pendingJoinRequests;
                $badges['join_requests'] = $pendingJoinRequests;
                $badges['classes'] = $classIds->count();
            } catch (Throwable $e) {
                // Ignore query exceptions
            }
        }

        return [
            'unread_notifications' => $unreadCount,
            'recent_notifications' => $recentNotifications,
            'badges' => $badges,
        ];
    }

    /**
     * Broadcast live state update to user's private channel.
     *
     * @param  array<string, string>|null  $toast
     */
    public function broadcast(User $user, ?array $toast = null): void
    {
        try {
            $state = $this->compute($user);
            broadcast(new UserLiveStateUpdated($user, $state, $toast));
        } catch (Throwable $e) {
            // Broadcasting failure should not break HTTP request
            Log::warning('Failed to broadcast UserLiveStateUpdated: '.$e->getMessage(), [
                'user_id' => $user->id,
            ]);
        }
    }
}
