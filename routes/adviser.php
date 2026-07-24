<?php

use App\Http\Controllers\Adviser\ClassJoinRequestController;
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

    Route::get('/classes/{researchClass}', [ResearchClassController::class, 'show'])
        ->whereNumber('researchClass')
        ->name('classes.show');

    Route::prefix('/classes/{researchClass}/join-requests/{joinRequest}')
        ->whereNumber(['researchClass', 'joinRequest'])
        ->middleware('throttle:class-join-decisions')
        ->group(function (): void {
            Route::patch('/approve', [ClassJoinRequestController::class, 'approve'])
                ->name('classes.join-requests.approve');
            Route::patch('/reject', [ClassJoinRequestController::class, 'reject'])
                ->name('classes.join-requests.reject');
        });
});
