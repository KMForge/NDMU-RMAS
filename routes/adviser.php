<?php

use App\Http\Controllers\Adviser\ConsultationController;
use App\Http\Controllers\Adviser\DashboardController;
use App\Http\Controllers\Adviser\DocumentReviewController;
use App\Http\Controllers\Adviser\ResearchGroupAdviserRequestController;
use App\Http\Controllers\DisabledFeatureController;
use Illuminate\Support\Facades\Route;

Route::prefix('adviser')->name('adviser.')->middleware([
    'auth', 'verified', 'active', 'permission:dashboards.adviser.view', 'workspace.context',
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
            Route::post('/approve', [ConsultationController::class, 'approve'])
                ->name('consultations.approve');
            Route::post('/propose-reschedule', [ConsultationController::class, 'proposeReschedule'])
                ->name('consultations.propose-reschedule');
            Route::post('/reject', [ConsultationController::class, 'reject'])
                ->name('consultations.reject');
            Route::post('/complete', [ConsultationController::class, 'complete'])
                ->name('consultations.complete');
            Route::patch('/meeting-details', [ConsultationController::class, 'updateMeetingDetails'])
                ->name('consultations.meeting-details.update');
        });

    Route::post('/consultations/records/{record}/correct', [ConsultationController::class, 'correctRecord'])
        ->whereNumber('record')
        ->middleware(['permission:consultations.manage-assigned', 'throttle:consultation-decisions'])
        ->name('consultations.records.correct');

    Route::prefix('/documents/{document}')
        ->whereNumber('document')
        ->middleware(['permission:documents.review', 'throttle:document-reviews'])
        ->group(function (): void {
            Route::post('/comments', [DocumentReviewController::class, 'comment'])
                ->name('documents.comments.store');
            Route::patch('/comments/{comment}/resolve', [DocumentReviewController::class, 'resolve'])
                ->whereNumber('comment')
                ->name('documents.comments.resolve');
            Route::patch('/review', [DocumentReviewController::class, 'review'])
                ->name('documents.review');
            Route::patch('/review/correct', [DocumentReviewController::class, 'correct'])
                ->name('documents.review.correct');
        });

    Route::prefix('/revisions/{revisionRequest}')
        ->whereNumber('revisionRequest')
        ->middleware(['permission:revisions.resolve', 'throttle:revision-actions'])
        ->group(function (): void {
            Route::patch('/resolve', DisabledFeatureController::class)
                ->name('revisions.resolve');
        });
});
