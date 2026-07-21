<?php

use Illuminate\Support\Facades\Route;

Route::prefix('student')->name('student.')->middleware([
    'auth', 'verified', 'active', 'role:student-researcher', 'permission:research.view-own',
])->group(function (): void {
    Route::view('/dashboard', 'pages.dashboard', ['area' => 'Student Researcher'])->name('dashboard');
});
