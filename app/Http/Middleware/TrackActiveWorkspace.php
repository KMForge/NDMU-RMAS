<?php

namespace App\Http\Middleware;

use App\Modules\Authorization\Services\ResolveUserDashboard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackActiveWorkspace
{
    public function __construct(private readonly ResolveUserDashboard $dashboards) {}

    public function handle(Request $request, Closure $next): Response
    {
        $workspace = $this->dashboards->workspaceForRoute($request->route()?->getName());

        if ($workspace !== null) {
            $request->session()->put('active_workspace', $workspace);
        }

        return $next($request);
    }
}
