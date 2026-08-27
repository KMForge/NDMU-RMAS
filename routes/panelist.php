<?php

use App\Http\Controllers\Panelist\DashboardController;
use App\Http\Controllers\Panelist\DocumentCommentController;
use App\Http\Controllers\Panelist\EvaluationController;
use Illuminate\Support\Facades\Route;

Route::prefix('panelist')->name('panelist.')->middleware([
    'auth', 'verified', 'active', 'permission:dashboards.panelist.view', 'workspace.context',
])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/evaluations/{round}', [EvaluationController::class, 'show'])->name('evaluations.show');
    Route::post('/evaluations/{round}/draft', [EvaluationController::class, 'saveDraft'])->name('evaluations.draft');
    Route::post('/evaluations/{round}/submit', [EvaluationController::class, 'submit'])->name('evaluations.submit');
    Route::post('/documents/{document}/comments', [DocumentCommentController::class, 'store'])
        ->whereNumber('document')
        ->middleware(['permission:evaluations.create', 'throttle:document-reviews'])
        ->name('documents.comments.store');
});
