<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\RegisterStudentRequest;
use App\Modules\Registration\Actions\RegisterStudent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;

class RegisteredStudentController extends Controller
{
    public function store(RegisterStudentRequest $request, RegisterStudent $register): RedirectResponse
    {
        $student = $register->handle($request->validated());

        event(new Registered($student));

        return to_route('login')->with(
            'status',
            'Registration submitted. Verify your institutional email, then wait for administrator approval.',
        );
    }
}
