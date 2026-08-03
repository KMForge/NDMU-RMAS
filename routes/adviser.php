<?php

use App\Http\Controllers\Adviser\ConsultationController;
use App\Http\Controllers\Adviser\DashboardController;
use App\Http\Controllers\Adviser\DocumentReviewController;
use App\Http\Controllers\Adviser\RepositoryDocumentController;
use App\Http\Controllers\Adviser\ResearchClassController;
use App\Http\Controllers\Adviser\RevisionRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('adviser')->name('adviser.')->middleware([
    'auth', 'verified', 'active', 'role:research-adviser', 'permission:research.view-assigned',
])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::post('/classes', [ResearchClassController::class, 'store'])
        ->middleware('permission:classes.create')
        ->name('classes.store');
    Route::get('/classes/{researchClass}', [ResearchClassController::class, 'show'])
        ->whereNumber('researchClass')
        ->name('classes.show');
    Route::post('/repository/documents', [RepositoryDocumentController::class, 'store'])
        ->middleware(['permission:documents.upload', 'throttle:document-uploads'])
        ->name('repository.documents.store');

    Route::prefix('/consultations/{consultationRequest}')
        ->whereNumber('consultationRequest')
        ->middleware(['permission:consultations.manage-assigned', 'throttle:consultation-decisions'])
        ->group(function (): void {
            Route::post('/complete', [ConsultationController::class, 'complete'])
                ->name('consultations.complete');
            Route::patch('/approve', [ConsultationController::class, 'approve'])
                ->name('consultations.approve');
            Route::patch('/reject', [ConsultationController::class, 'reject'])
                ->name('consultations.reject');
        });

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
        });

    Route::prefix('/revisions/{revisionRequest}')
        ->whereNumber('revisionRequest')
        ->middleware(['permission:revisions.resolve', 'throttle:revision-actions'])
        ->group(function (): void {
            Route::patch('/resolve', [RevisionRequestController::class, 'resolve'])
                ->name('revisions.resolve');
            Route::patch('/reopen', [RevisionRequestController::class, 'reopen'])
                ->name('revisions.reopen');
        });
});
