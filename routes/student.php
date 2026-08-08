<?php

use App\Http\Controllers\DisabledFeatureController;
use App\Http\Controllers\Student\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('student')->name('student.')->middleware([
    'auth', 'verified', 'active', 'permission:dashboards.student.view',
])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/official-forms/{form}/source', DisabledFeatureController::class)
        ->where('form', 'RES-[0-9]{3}')
        ->middleware('throttle:60,1')
        ->name('official-forms.source');

    Route::post('/documents', DisabledFeatureController::class)
        ->middleware('throttle:document-uploads')
        ->name('documents.store');

    Route::post('/consultations', DisabledFeatureController::class)
        ->middleware('throttle:consultation-bookings')
        ->name('consultations.store');

    Route::post('/classes/join', DisabledFeatureController::class)
        ->middleware('throttle:class-joining')
        ->name('classes.join');
    Route::get('/classes/{researchClass}', DisabledFeatureController::class)
        ->middleware('permission:classes.view-enrolled')
        ->whereNumber('researchClass')
        ->name('classes.show');

    Route::prefix('/revisions/{revisionRequest}')
        ->whereNumber('revisionRequest')
        ->middleware('throttle:revision-actions')
        ->group(function (): void {
            Route::patch('/start', DisabledFeatureController::class)
                ->name('revisions.start');
            Route::post('/documents', DisabledFeatureController::class)
                ->middleware('throttle:document-uploads')
                ->name('revisions.submit');
        });
});
