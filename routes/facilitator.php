<?php

use Illuminate\Support\Facades\Route;

Route::prefix('facilitator')->name('facilitator.')->middleware([
    'auth', 'verified', 'active', 'role:research-facilitator', 'permission:defenses.manage',
])->group(function (): void {
    Route::view('/dashboard', 'pages.dashboard', ['area' => 'Research Facilitator'])->name('dashboard');
});
