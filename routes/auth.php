<?php

use App\Http\Controllers\Authentication\AuthenticatedSessionController;
use App\Http\Controllers\Authentication\NewPasswordController;
use App\Http\Controllers\Authentication\PasswordResetLinkController;
use App\Http\Controllers\Authentication\RegisteredStudentController;
use App\Http\Controllers\Authentication\VerifyStudentEmailController;
use Illuminate\Support\Facades\Route;

Route::view('/login', 'pages.login')
    ->middleware('guest')
    ->name('login');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware(['guest', 'throttle:authentication'])
    ->name('login.store');

Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
    ->middleware('guest')
    ->name('password.request');

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware(['guest', 'throttle:password-reset-links'])
    ->name('password.email');

Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
    ->middleware('guest')
    ->name('password.reset');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware(['guest', 'throttle:password-resets'])
    ->name('password.update');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::view('/register', 'pages.register')
    ->middleware(['guest'])
    ->name('register');

Route::post('/register', [RegisteredStudentController::class, 'store'])
    ->middleware(['guest', 'throttle:student-registration'])
    ->name('register.store');

Route::get('/verify-email/{id}/{hash}', VerifyStudentEmailController::class)
    ->middleware(['guest', 'signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::view('/verify-email', 'pages.verify-email')
    ->middleware('auth')
    ->name('verification.notice');
