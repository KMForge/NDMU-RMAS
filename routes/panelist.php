<?php

use Illuminate\Support\Facades\Route;

Route::prefix('panelist')->name('panelist.')->middleware([
    'auth', 'verified', 'active', 'role:panelist', 'permission:evaluations.view-assigned',
])->group(function (): void {
    Route::view('/dashboard', 'pages.dashboard', ['area' => 'Panelist'])->name('dashboard');
});
