<?php

namespace App\Modules\Authorization\Actions;

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Authorization\Services\ResolveUserDashboard;
use Illuminate\Http\Request;

class SwitchWorkspace
{
    public function __construct(private readonly ResolveUserDashboard $dashboards) {}

    public function handle(User $user, string $workspace, Request $request): string
    {
        $route = $this->dashboards->routeForWorkspace($user, $workspace);

        abort_if($route === null, 403, 'You do not have access to that workspace.');

        $fromWorkspace = $request->session()->get('active_workspace');

        if (! is_string($fromWorkspace) || $this->dashboards->routeForWorkspace($user, $fromWorkspace) === null) {
            $fromWorkspace = null;
        }

        AuditLog::query()->create([
            'user_id' => $user->getKey(),
            'actor_name' => $user->name,
            'actor_email' => $user->email,
            'subject_name' => $user->name,
            'subject_email' => $user->email,
            'event' => 'workspace.switched',
            'auditable_type' => User::class,
            'auditable_id' => $user->getKey(),
            'description' => 'User switched between authorized workspaces.',
            'old_values' => ['workspace' => $fromWorkspace],
            'new_values' => ['workspace' => $workspace],
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'created_at' => now(),
        ]);

        $request->session()->put('active_workspace', $workspace);

        return $route;
    }
}
