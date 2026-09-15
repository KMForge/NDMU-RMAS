<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserOnboardingCompletion;
use App\Modules\Authorization\Services\ResolveUserDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    public function __invoke(Request $request, string $workspace, ResolveUserDashboard $dashboards): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['completed', 'skipped'])],
        ]);

        /** @var User $user */
        $user = $request->user();

        abort_if($dashboards->routeForWorkspace($user, $workspace) === null, 403);

        UserOnboardingCompletion::query()->updateOrCreate(
            ['user_id' => $user->id, 'workspace' => $workspace],
            ['status' => $validated['status'], 'completed_at' => now()],
        );

        return response()->json(['message' => 'Workspace introduction saved.']);
    }
}
