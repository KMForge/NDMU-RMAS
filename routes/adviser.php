<?php

use Illuminate\Support\Facades\Route;

Route::prefix('adviser')->name('adviser.')->middleware([
    'auth', 'verified', 'active', 'role:research-adviser', 'permission:research.view-assigned',
])->group(function (): void {
    Route::view('/dashboard', 'pages.dashboard', ['area' => 'Research Adviser'])->name('dashboard');
});
