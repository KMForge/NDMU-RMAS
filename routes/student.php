<?php

use App\Http\Controllers\Student\ConsultationController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\DocumentController;
use Illuminate\Support\Facades\Route;

Route::prefix('student')->name('student.')->middleware([
    'auth', 'verified', 'active', 'role:student-researcher', 'permission:research.view-own',
])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::post('/documents', [DocumentController::class, 'store'])
        ->middleware('throttle:document-uploads')
        ->name('documents.store');

    Route::post('/consultations', [ConsultationController::class, 'store'])
        ->middleware('throttle:consultation-bookings')
        ->name('consultations.store');
});
