<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use App\Modules\Registration\Actions\ActivateVerifiedStudent;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyStudentEmailController extends Controller
{
    public function __invoke(
        Request $request,
        int $id,
        AuditLogWriter $auditLogs,
        ActivateVerifiedStudent $activateVerifiedStudent,
    ): RedirectResponse {
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

        $user = $activateVerifiedStudent->handle(
            $user,
            AuditRequestContext::fromRequest($request),
        );

        $status = $user->isActiveAndApproved()
            ? 'Institutional email verified. Your student account is active and you can now sign in.'
            : 'Institutional email verified. This account cannot be activated automatically. Please contact the Research Office.';

        return to_route('login')->with('status', $status);
    }
}
