<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Models\User;
use App\Modules\Authorization\Services\ResolveUserDashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ResolveUserDashboard $dashboard): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $route = $dashboard->routeFor($user);

        if ($route === null && $user->user_type === UserType::Faculty) {
            return redirect()->route('access.pending');
        }

        abort_if($route === null, 403, 'No dashboard is assigned to this account.');

        return redirect()->route($route);
    }
}
