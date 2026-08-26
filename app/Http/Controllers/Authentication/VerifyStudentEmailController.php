<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyStudentEmailController extends Controller
{
    public function __invoke(Request $request, int $id, AuditLogWriter $auditLogs): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        abort_unless(hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification())), 403);

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
            $auditLogs->write(
                actor: $user,
                event: 'auth.email-verified',
                description: 'Institutional email verification was completed.',
                requestContext: AuditRequestContext::fromRequest($request),
                auditable: $user,
                subjectName: $user->name,
                subjectEmail: $user->email,
            );
        }

        return to_route('login')->with(
            'status',
            'Email verified. You can sign in after an administrator approves your registration.',
        );
    }
}
