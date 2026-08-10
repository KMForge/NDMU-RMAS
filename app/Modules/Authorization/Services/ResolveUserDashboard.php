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
     * @var array<string, array{
     *     permission: string,
     *     route: string,
     *     label: string,
     *     description: string,
     *     icon: string
     * }>
     */
    private const WORKSPACES = [
        'admin' => ['permission' => 'dashboards.admin.view', 'route' => 'admin.dashboard', 'label' => 'Administration', 'description' => 'System management', 'icon' => 'ph-shield-check'],
        'facilitator' => ['permission' => 'dashboards.facilitator.view', 'route' => 'facilitator.dashboard', 'label' => 'Research Facilitator', 'description' => 'Classes and research operations', 'icon' => 'ph-chalkboard-teacher'],
        'dean' => ['permission' => 'dashboards.dean.view', 'route' => 'dean.dashboard', 'label' => 'College Oversight', 'description' => 'Approvals and oversight', 'icon' => 'ph-buildings'],
        'adviser' => ['permission' => 'dashboards.adviser.view', 'route' => 'adviser.dashboard', 'label' => 'Thesis Adviser', 'description' => 'Assigned research groups', 'icon' => 'ph-user-focus'],
        'panelist' => ['permission' => 'dashboards.panelist.view', 'route' => 'panelist.dashboard', 'label' => 'Panel Member', 'description' => 'Defense evaluation', 'icon' => 'ph-clipboard-text'],
        'student' => ['permission' => 'dashboards.student.view', 'route' => 'student.dashboard', 'label' => 'Student Researcher', 'description' => 'Student research workspace', 'icon' => 'ph-student'],
    ];

    public function routeFor(User $user): ?string
    {
        foreach (self::WORKSPACES as $workspace) {
            if ($user->can($workspace['permission'])) {
                return $workspace['route'];
            }
        }

        return null;
    }

    /** @return array<string, string> */
    public function availableFor(User $user): array
    {
        return collect(self::WORKSPACES)
            ->filter(fn (array $workspace): bool => $user->can($workspace['permission']))
            ->mapWithKeys(fn (array $workspace): array => [$workspace['permission'] => $workspace['route']])
            ->all();
    }

    /**
     * @return array<string, array{permission: string, route: string, label: string, description: string, icon: string}>
     */
    public function workspacesFor(User $user): array
    {
        return collect(self::WORKSPACES)
            ->filter(fn (array $workspace): bool => $user->can($workspace['permission']))
            ->all();
    }

    public function routeForWorkspace(User $user, string $workspace): ?string
    {
        $definition = self::WORKSPACES[$workspace] ?? null;

        return $definition !== null && $user->can($definition['permission'])
            ? $definition['route']
            : null;
    }

    public function workspaceForRoute(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        foreach (self::WORKSPACES as $workspace => $definition) {
            $routePrefix = (string) str($definition['route'])->before('.');

            if ($routeName === $definition['route'] || str_starts_with($routeName, $routePrefix.'.')) {
                return $workspace;
            }
        }

        return null;
    }
}
