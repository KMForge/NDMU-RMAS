<?php

namespace App\Http\Controllers\Authentication;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\LoginRequest;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use App\Modules\Authorization\Services\ResolveUserDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request, ResolveUserDashboard $dashboard, AuditLogWriter $auditLogs): JsonResponse|RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $auditLogs->write(
                actor: null,
                event: 'auth.login.failed',
                description: 'An authentication attempt was denied.',
                requestContext: AuditRequestContext::fromRequest($request),
                subjectName: 'Authentication identifier '.hash_hmac('sha256', mb_strtolower(trim((string) $credentials['email'])), (string) config('app.key')),
                outcome: 'denied',
                allowSystemActor: true,
            );

            throw ValidationException::withMessages([
                'email' => __('The provided credentials are incorrect.'),
            ]);
        }

        /** @var User $user */
        $user = $request->user();

        $route = $dashboard->routeFor($user);

        if (! $user->isActiveAndApproved() || ($route === null && $user->user_type !== UserType::Faculty)) {
            $auditLogs->write(
                actor: $user,
                event: 'auth.login.blocked',
                description: 'Authentication was blocked by account access policy.',
                requestContext: AuditRequestContext::fromRequest($request),
                auditable: $user,
                subjectName: $user->name,
                subjectEmail: $user->email,
                outcome: 'denied',
            );
            $this->endSession($request);

            throw ValidationException::withMessages([
                'email' => __('The provided credentials are incorrect.'),
            ]);
        }

        $request->session()->regenerate();

        $auditLogs->write(
            actor: $user,
            event: 'auth.login.succeeded',
            description: 'User authentication succeeded.',
            requestContext: AuditRequestContext::fromRequest($request),
            auditable: $user,
            subjectName: $user->name,
            subjectEmail: $user->email,
        );

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

    public function destroy(Request $request, AuditLogWriter $auditLogs): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user !== null) {
            $auditLogs->write(
                actor: $user,
                event: 'auth.logout',
                description: 'User session ended.',
                requestContext: AuditRequestContext::fromRequest($request),
                auditable: $user,
                subjectName: $user->name,
                subjectEmail: $user->email,
            );
        }

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
