<?php

use Illuminate\Support\Facades\Route;

// Authentication UI will be added with the chosen first-party-compatible starter flow.
Route::view('/login', 'pages.authentication-not-configured')
    ->middleware(['guest', 'throttle:authentication'])
    ->name('login');

Route::view('/register', 'pages.authentication-not-configured')
    ->middleware(['guest'])
    ->name('register');

Route::view('/verify-email', 'pages.verify-email')
    ->middleware('auth')
    ->name('verification.notice');
