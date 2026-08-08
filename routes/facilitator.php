<?php

use App\Http\Controllers\DisabledFeatureController;
use App\Http\Controllers\Facilitator\DashboardController;
use App\Http\Controllers\Facilitator\ResearchClassController;
use Illuminate\Support\Facades\Route;

Route::prefix('facilitator')->name('facilitator.')->middleware([
    'auth', 'verified', 'active', 'permission:dashboards.facilitator.view',
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
            Route::post('/groups', DisabledFeatureController::class)
                ->middleware(['permission:classes.manage-groups', 'throttle:class-creation'])
                ->name('classes.groups.store');
            Route::put('/groups/{group}/students/{enrollment}', DisabledFeatureController::class)
                ->middleware(['permission:classes.manage-groups', 'throttle:class-join-decisions'])
                ->whereNumber(['group', 'enrollment'])
                ->name('classes.groups.students.assign');
            Route::put('/groups/{group}/adviser', DisabledFeatureController::class)
                ->middleware(['permission:classes.assign-advisers', 'throttle:class-join-decisions'])
                ->whereNumber('group')
                ->name('classes.groups.adviser.assign');

            Route::prefix('/join-requests/{joinRequest}')
                ->whereNumber('joinRequest')
                ->middleware(['permission:classes.manage-join-requests', 'throttle:class-join-decisions'])
                ->group(function (): void {
                    Route::patch('/approve', DisabledFeatureController::class)
                        ->name('classes.join-requests.approve');
                    Route::patch('/reject', DisabledFeatureController::class)
                        ->name('classes.join-requests.reject');
                });
        });
});
