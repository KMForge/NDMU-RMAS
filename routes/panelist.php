<?php

use App\Http\Controllers\Panelist\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('panelist')->name('panelist.')->middleware([
    'auth', 'verified', 'active', 'permission:dashboards.panelist.view', 'workspace.context',
])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});
