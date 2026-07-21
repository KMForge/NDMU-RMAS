<?php

use Illuminate\Support\Facades\Route;

// Placeholder authentication views until the full auth flow is implemented.
Route::view('/login', 'pages.login')
    ->middleware(['guest', 'throttle:authentication'])
    ->name('login');

Route::view('/register', 'pages.register')
    ->middleware(['guest'])
    ->name('register');

Route::view('/verify-email', 'pages.verify-email')
    ->middleware('auth')
    ->name('verification.notice');
