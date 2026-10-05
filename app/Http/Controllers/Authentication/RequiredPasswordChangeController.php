<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\ChangeRequiredPasswordRequest;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use App\Modules\Authorization\Services\ResolveUserDashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RequiredPasswordChangeController extends Controller
{
    public function edit(): View
    {
        /** @var User $user */
        $user = request()->user();

        return view('pages.change-required-password', [
            'temporaryPasswordExpired' => $user->temporary_password_expires_at?->isPast() ?? false,
        ]);
    }

    public function update(
        ChangeRequiredPasswordRequest $request,
        ResolveUserDashboard $dashboard,
        AuditLogWriter $auditLogs,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $password = $request->string('password')->toString();

        if ($user->temporary_password_expires_at?->isPast()) {
            throw ValidationException::withMessages([
                'password' => 'This temporary password has expired. Use Forgot Password or ask an administrator to issue a new one.',
            ]);
        }

        if (Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Your new password must be different from the temporary password.',
            ]);
        }

        $user->forceFill([
            'password' => $password,
            'must_change_password' => false,
            'temporary_password_expires_at' => null,
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->regenerate();

        $auditLogs->write(
            actor: $user,
            event: 'auth.required-password-change.completed',
            description: 'The account owner replaced an administrator-issued temporary password.',
            requestContext: AuditRequestContext::fromRequest($request),
            auditable: $user,
            subjectName: $user->name,
            subjectEmail: $user->email,
        );

        return to_route($dashboard->routeFor($user) ?? 'access.pending')
            ->with('status', 'Your password has been changed successfully.');
    }
}
