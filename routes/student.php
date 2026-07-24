<?php

use App\Http\Controllers\Student\DocumentController;
use Illuminate\Support\Facades\Route;

Route::prefix('student')->name('student.')->middleware([
    'auth', 'verified', 'active', 'role:student-researcher', 'permission:research.view-own',
])->group(function (): void {
    Route::view('/dashboard', 'pages.student-dashboard', ['area' => 'Student Researcher'])->name('dashboard');

    Route::post('/documents', [DocumentController::class, 'store'])
        ->middleware('throttle:document-uploads')
        ->name('documents.store');
});
