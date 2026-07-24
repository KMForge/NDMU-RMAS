<?php

use Illuminate\Support\Facades\Route;

Route::prefix('dean')->name('dean.')->middleware([
    'auth', 'verified', 'active', 'role:college-dean', 'permission:research.view-college',
])->group(function (): void {
    Route::view('/dashboard', 'pages.dean-dashboard', ['area' => 'College Dean'])->name('dashboard');
});
