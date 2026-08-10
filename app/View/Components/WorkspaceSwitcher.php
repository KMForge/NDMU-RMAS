<?php

namespace App\View\Components;

use App\Models\User;
use App\Modules\Authorization\Services\ResolveUserDashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

class WorkspaceSwitcher extends Component
{
    /** @var array<string, array{permission: string, route: string, label: string, description: string, icon: string}> */
    public array $workspaces = [];

    public ?string $currentWorkspace = null;

    public function __construct(ResolveUserDashboard $dashboards, Request $request, ?string $current = null)
    {
        $user = $request->user();

        if ($user instanceof User) {
            $this->workspaces = $dashboards->workspacesFor($user);
            $this->currentWorkspace = $current ?? $dashboards->workspaceForRoute($request->route()?->getName());

        }
    }

    public function render(): View
    {
        return view('components.workspace-switcher');
    }
}
