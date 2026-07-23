<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'active'])
    ->name('dashboard');

require __DIR__.'/auth.php';
