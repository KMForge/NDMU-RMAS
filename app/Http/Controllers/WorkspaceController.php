<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Authorization\Actions\SwitchWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function __invoke(Request $request, string $workspace, SwitchWorkspace $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        return redirect()->route($action->handle($user, $workspace, $request));
    }
}
