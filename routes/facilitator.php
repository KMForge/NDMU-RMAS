<?php

use App\Http\Controllers\Facilitator\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('facilitator')->name('facilitator.')->middleware([
    'auth', 'verified', 'active', 'role:research-facilitator', 'permission:defenses.manage',
])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});
