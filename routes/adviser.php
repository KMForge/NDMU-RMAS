<?php

use App\Http\Controllers\Adviser\DashboardController;
use App\Http\Controllers\Adviser\ResearchClassController;
use Illuminate\Support\Facades\Route;

Route::prefix('adviser')->name('adviser.')->middleware([
    'auth', 'verified', 'active', 'role:research-adviser', 'permission:research.view-assigned',
])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::post('/classes', [ResearchClassController::class, 'store'])
        ->middleware('throttle:class-creation')
        ->name('classes.store');
});
