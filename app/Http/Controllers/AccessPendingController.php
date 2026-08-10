<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Models\User;
use App\Modules\Authorization\Services\ResolveUserDashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccessPendingController extends Controller
{
    public function __invoke(Request $request, ResolveUserDashboard $dashboards): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $route = $dashboards->routeFor($user);

        if ($route !== null) {
            return redirect()->route($route);
        }

        abort_unless($user->user_type === UserType::Faculty, 403);

        return view('pages.access-pending', ['user' => $user]);
    }
}
