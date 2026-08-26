<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\ResetPasswordRequest;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(string $token): View
    {
        return view('pages.reset-password', [
            'token' => $token,
            'email' => request()->string('email')->toString(),
        ]);
    }

    public function store(ResetPasswordRequest $request, AuditLogWriter $auditLogs): JsonResponse|RedirectResponse
    {
        $resetUser = null;
        $status = Password::reset(
            $request->safe()->only(['email', 'password', 'password_confirmation', 'token']),
            function (User $user, string $password) use (&$resetUser): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                $resetUser = $user;
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The password could not be reset.',
                    'errors' => ['email' => [__($status)]],
                ], 422);
            }

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        if ($resetUser instanceof User) {
            $auditLogs->write(
                actor: $resetUser,
                event: 'auth.password-reset.completed',
                description: 'Account password reset was completed.',
                requestContext: AuditRequestContext::fromRequest($request),
                auditable: $resetUser,
                subjectName: $resetUser->name,
                subjectEmail: $resetUser->email,
            );
        }

        $message = 'Your password has been reset. You may now sign in.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect_url' => route('login'),
            ]);
        }

        return to_route('login')->with('status', $message);
    }
}
