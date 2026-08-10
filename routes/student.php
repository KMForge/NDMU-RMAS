<?php

use App\Http\Controllers\DisabledFeatureController;
use App\Http\Controllers\Student\ConsultationController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\DocumentController;
use App\Http\Controllers\Student\ResearchClassController;
use App\Http\Controllers\Student\RevisionRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('student')->name('student.')->middleware([
    'auth', 'verified', 'active', 'permission:dashboards.student.view', 'workspace.context',
])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/official-forms/{form}/source', DisabledFeatureController::class)
        ->where('form', 'RES-[0-9]{3}')
        ->middleware('throttle:60,1')
        ->name('official-forms.source');

    Route::post('/documents', [DocumentController::class, 'store'])
        ->middleware(['permission:documents.upload', 'throttle:document-uploads'])
        ->name('documents.store');

    Route::post('/consultations', [ConsultationController::class, 'store'])
        ->middleware(['permission:consultations.request', 'throttle:consultation-bookings'])
        ->name('consultations.store');

    Route::post('/consultations/{consultationRequest}/cancel', [ConsultationController::class, 'cancel'])
        ->whereNumber('consultationRequest')
        ->name('consultations.cancel');

    Route::post('/consultations/{consultationRequest}/respond', [ConsultationController::class, 'respondToReschedule'])
        ->whereNumber('consultationRequest')
        ->name('consultations.respond');

    Route::post('/classes/join', [ResearchClassController::class, 'store'])
        ->middleware(['permission:classes.join', 'throttle:class-joining'])
        ->name('classes.join');
    Route::get('/classes/{researchClass}', [ResearchClassController::class, 'show'])
        ->middleware('permission:classes.view-enrolled')
        ->whereNumber('researchClass')
        ->name('classes.show');

    Route::prefix('/revisions/{revisionRequest}')
        ->whereNumber('revisionRequest')
        ->middleware('throttle:revision-actions')
        ->group(function (): void {
            Route::patch('/start', [RevisionRequestController::class, 'start'])
                ->name('revisions.start');
            Route::post('/documents', [RevisionRequestController::class, 'submit'])
                ->middleware('throttle:document-uploads')
                ->name('revisions.submit');
        });
});
