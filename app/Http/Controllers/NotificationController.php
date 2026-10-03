<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Authorization\Actions\SwitchWorkspace;
use App\Modules\Authorization\Services\ResolveUserDashboard;
use App\Modules\Notifications\Queries\GetNotificationsForUser;
use App\Modules\Notifications\Services\NotificationDestinationResolver;
use App\Modules\Notifications\Services\NotificationWorkspaceResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, GetNotificationsForUser $query): View
    {
        $filter = in_array($request->query('filter'), ['all', 'unread', 'read'], true)
            ? (string) $request->query('filter')
            : 'all';

        /** @var User $user */
        $user = $request->user();

        return view('pages.notifications.index', [
            'notifications' => $query->execute($user, $filter),
            'unreadCount' => $user->unreadNotifications()->count(),
            'filter' => $filter,
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $notification): JsonResponse|RedirectResponse
    {
        $owned = $this->ownedNotification($request, $notification);
        $owned->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Notification marked as read.']);
        }

        return back();
    }

    public function readAll(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'All notifications marked as read.']);
        }

        return back();
    }

    public function open(
        Request $request,
        string $notification,
        NotificationDestinationResolver $resolver,
        NotificationWorkspaceResolver $workspaceResolver,
        ResolveUserDashboard $dashboards,
        SwitchWorkspace $switchWorkspace,
    ): RedirectResponse {
        $owned = $this->ownedNotification($request, $notification);
        $owned->markAsRead();

        /** @var User $user */
        $user = $request->user();
        $targetWorkspace = $workspaceResolver->fromData($owned->data);

        if ($targetWorkspace !== null
            && $request->session()->get('active_workspace') !== $targetWorkspace
            && $dashboards->routeForWorkspace($user, $targetWorkspace) !== null) {
            $switchWorkspace->handle($user, $targetWorkspace, $request);
        }

        return redirect()->to($resolver->resolve($owned));
    }

    private function ownedNotification(Request $request, string $notification): DatabaseNotification
    {
        /** @var DatabaseNotification $owned */
        $owned = $request->user()->notifications()->whereKey($notification)->firstOrFail();

        return $owned;
    }
}
