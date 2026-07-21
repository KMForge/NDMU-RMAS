<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('The provided credentials are incorrect.'),
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();

        if (! $user->isActiveAndApproved()) {
            $this->endSession($request);

            throw ValidationException::withMessages([
                'email' => __('The provided credentials are incorrect.'),
            ]);
        }

        $destination = $this->dashboardFor($user);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Login successful.',
                'redirect_url' => $destination,
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->values(),
                ],
            ]);
        }

        return redirect()->intended($destination);
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        $this->endSession($request);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Logged out successfully.']);
        }

        return redirect()->route('home');
    }

    private function endSession(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function dashboardFor(User $user): string
    {
        $routes = [
            'system-administrator' => 'admin.dashboard',
            'college-dean' => 'dean.dashboard',
            'research-facilitator' => 'facilitator.dashboard',
            'research-adviser' => 'adviser.dashboard',
            'panelist' => 'panelist.dashboard',
            'student-researcher' => 'student.dashboard',
        ];

        foreach ($routes as $role => $route) {
            if ($user->hasRole($role)) {
                return route($route);
            }
        }

        return route('home');
    }
}
