<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $role = UserRole::highestFor($user);

        abort_if($role === null, 403, 'No dashboard is assigned to this account.');

        return redirect()->route($role->dashboardRoute());
    }
}
