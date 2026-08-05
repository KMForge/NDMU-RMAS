<?php

namespace App\Modules\Authorization\Services;

use App\Models\User;

class ResolveUserDashboard
{
    /**
     * Permissions, rather than account type or role names, determine which
     * workspace a user may open. The order only selects the default landing
     * page when a user has access to several workspaces.
     *
     * @var array<string, string>
     */
    private const DASHBOARDS = [
        'dashboards.admin.view' => 'admin.dashboard',
        'dashboards.facilitator.view' => 'facilitator.dashboard',
        'dashboards.dean.view' => 'dean.dashboard',
        'dashboards.adviser.view' => 'adviser.dashboard',
        'dashboards.panelist.view' => 'panelist.dashboard',
        'dashboards.student.view' => 'student.dashboard',
    ];

    public function routeFor(User $user): ?string
    {
        foreach (self::DASHBOARDS as $permission => $route) {
            if ($user->can($permission)) {
                return $route;
            }
        }

        return null;
    }

    /** @return array<string, string> */
    public function availableFor(User $user): array
    {
        return collect(self::DASHBOARDS)
            ->filter(fn (string $route, string $permission): bool => $user->can($permission))
            ->all();
    }
}
