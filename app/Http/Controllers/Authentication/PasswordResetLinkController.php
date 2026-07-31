<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\ForgotPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('pages.forgot-password');
    }

    public function store(ForgotPasswordRequest $request): JsonResponse|RedirectResponse
    {
        Password::sendResetLink([
            'email' => $request->string('email')->toString(),
        ]);

        $message = 'If an account exists for that email address, a password reset link has been sent.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }
}
