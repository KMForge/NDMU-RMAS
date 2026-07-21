<?php

use App\Http\Controllers\Authentication\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::view('/login', 'pages.login')
    ->middleware('guest')
    ->name('login');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware(['guest', 'throttle:authentication'])
    ->name('login.store');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::view('/register', 'pages.register')
    ->middleware(['guest'])
    ->name('register');

Route::view('/verify-email', 'pages.verify-email')
    ->middleware('auth')
    ->name('verification.notice');
