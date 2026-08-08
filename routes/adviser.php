<?php

use App\Http\Controllers\Adviser\DashboardController;
use App\Http\Controllers\Adviser\ResearchGroupAdviserRequestController;
use App\Http\Controllers\DisabledFeatureController;
use Illuminate\Support\Facades\Route;

Route::prefix('adviser')->name('adviser.')->middleware([
    'auth', 'verified', 'active', 'permission:dashboards.adviser.view',
])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::patch('/group-requests/{adviserRequest}/respond', [ResearchGroupAdviserRequestController::class, 'respond'])
        ->middleware(['permission:classes.serve-as-adviser', 'throttle:consultation-decisions'])
        ->whereNumber('adviserRequest')
        ->name('group-requests.respond');

    Route::post('/classes', DisabledFeatureController::class)
        ->middleware('permission:classes.create')
        ->name('classes.store');
    Route::get('/classes/{researchClass}', DisabledFeatureController::class)
        ->whereNumber('researchClass')
        ->name('classes.show');
    Route::post('/repository/documents', DisabledFeatureController::class)
        ->middleware(['permission:documents.upload', 'throttle:document-uploads'])
        ->name('repository.documents.store');

    Route::prefix('/consultations/{consultationRequest}')
        ->whereNumber('consultationRequest')
        ->middleware(['permission:consultations.manage-assigned', 'throttle:consultation-decisions'])
        ->group(function (): void {
            Route::post('/complete', DisabledFeatureController::class)
                ->name('consultations.complete');
            Route::patch('/approve', DisabledFeatureController::class)
                ->name('consultations.approve');
            Route::patch('/reject', DisabledFeatureController::class)
                ->name('consultations.reject');
        });

    Route::prefix('/documents/{document}')
        ->whereNumber('document')
        ->middleware(['permission:documents.review', 'throttle:document-reviews'])
        ->group(function (): void {
            Route::post('/comments', DisabledFeatureController::class)
                ->name('documents.comments.store');
            Route::patch('/comments/{comment}/resolve', DisabledFeatureController::class)
                ->whereNumber('comment')
                ->name('documents.comments.resolve');
            Route::patch('/review', DisabledFeatureController::class)
                ->name('documents.review');
        });

    Route::prefix('/revisions/{revisionRequest}')
        ->whereNumber('revisionRequest')
        ->middleware(['permission:revisions.resolve', 'throttle:revision-actions'])
        ->group(function (): void {
            Route::patch('/resolve', DisabledFeatureController::class)
                ->name('revisions.resolve');
            Route::patch('/reopen', DisabledFeatureController::class)
                ->name('revisions.reopen');
        });
});
