<?php

use App\Http\Controllers\Facilitator\ClassJoinRequestController;
use App\Http\Controllers\Facilitator\DashboardController;
use App\Http\Controllers\Facilitator\ResearchClassController;
use App\Http\Controllers\Facilitator\ResearchClassGroupController;
use Illuminate\Support\Facades\Route;

Route::prefix('facilitator')->name('facilitator.')->middleware([
    'auth', 'verified', 'active', 'role:research-facilitator', 'permission:defenses.manage',
])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::post('/classes', [ResearchClassController::class, 'store'])
        ->middleware(['permission:classes.create', 'throttle:class-creation'])
        ->name('classes.store');
    Route::get('/classes/{researchClass}', [ResearchClassController::class, 'show'])
        ->middleware('permission:classes.view-own')
        ->whereNumber('researchClass')
        ->name('classes.show');

    Route::prefix('/classes/{researchClass}')
        ->whereNumber('researchClass')
        ->group(function (): void {
            Route::post('/groups', [ResearchClassGroupController::class, 'store'])
                ->middleware(['permission:classes.manage-groups', 'throttle:class-creation'])
                ->name('classes.groups.store');
            Route::put('/groups/{group}/students/{enrollment}', [ResearchClassGroupController::class, 'assignStudent'])
                ->middleware(['permission:classes.manage-groups', 'throttle:class-join-decisions'])
                ->whereNumber(['group', 'enrollment'])
                ->name('classes.groups.students.assign');
            Route::put('/groups/{group}/adviser', [ResearchClassGroupController::class, 'assignAdviser'])
                ->middleware(['permission:classes.assign-advisers', 'throttle:class-join-decisions'])
                ->whereNumber('group')
                ->name('classes.groups.adviser.assign');

            Route::prefix('/join-requests/{joinRequest}')
                ->whereNumber('joinRequest')
                ->middleware(['permission:classes.manage-join-requests', 'throttle:class-join-decisions'])
                ->group(function (): void {
                    Route::patch('/approve', [ClassJoinRequestController::class, 'approve'])
                        ->name('classes.join-requests.approve');
                    Route::patch('/reject', [ClassJoinRequestController::class, 'reject'])
                        ->name('classes.join-requests.reject');
                });
        });
});
