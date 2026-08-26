<?php

namespace App\Modules\Authorization\Actions;

use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use App\Modules\Authorization\Services\ResolveUserDashboard;
use Illuminate\Http\Request;

class SwitchWorkspace
{
    public function __construct(
        private readonly ResolveUserDashboard $dashboards,
        private readonly AuditLogWriter $auditLogs,
    ) {}

    public function handle(User $user, string $workspace, Request $request): string
    {
        $route = $this->dashboards->routeForWorkspace($user, $workspace);

        abort_if($route === null, 403, 'You do not have access to that workspace.');

        $fromWorkspace = $request->session()->get('active_workspace');

        if (! is_string($fromWorkspace) || $this->dashboards->routeForWorkspace($user, $fromWorkspace) === null) {
            $fromWorkspace = null;
        }

        $this->auditLogs->write(
            actor: $user,
            event: 'workspace.switched',
            description: 'User switched between authorized workspaces.',
            requestContext: AuditRequestContext::fromRequest($request),
            auditable: $user,
            subjectName: $user->name,
            subjectEmail: $user->email,
            oldValues: ['workspace' => $fromWorkspace],
            newValues: ['workspace' => $workspace],
            actorContext: $workspace,
        );

        $request->session()->put('active_workspace', $workspace);

        return $route;
    }
}
