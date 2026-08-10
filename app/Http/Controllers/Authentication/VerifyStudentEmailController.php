<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyStudentEmailController extends Controller
{
    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        abort_unless(hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification())), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return to_route('login')->with(
            'status',
            'Email verified. You can sign in after an administrator approves your registration.',
        );
    }
}
