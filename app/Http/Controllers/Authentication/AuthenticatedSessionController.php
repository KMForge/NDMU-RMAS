<?php

namespace App\Http\Controllers\Authentication;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\LoginRequest;
use App\Models\User;
use App\Modules\Authorization\Services\ResolveUserDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request, ResolveUserDashboard $dashboard): JsonResponse|RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('The provided credentials are incorrect.'),
            ]);
        }

        /** @var User $user */
        $user = $request->user();

        $route = $dashboard->routeFor($user);

        if (! $user->isActiveAndApproved() || ($route === null && $user->user_type !== UserType::Faculty)) {
            $this->endSession($request);

            throw ValidationException::withMessages([
                'email' => __('The provided credentials are incorrect.'),
            ]);
        }

        $request->session()->regenerate();

        $destination = route($route ?? 'access.pending');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Login successful.',
                'redirect_url' => $destination,
                'user_type' => $user->user_type->value,
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->values(),
                ],
            ]);
        }

        return redirect()->to($destination);
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
}
