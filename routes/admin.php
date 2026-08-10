<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware([
    'auth', 'verified', 'active', 'permission:dashboards.admin.view', 'workspace.context',
])->group(function (): void {
    Route::view('/dashboard', 'pages.admin-dashboard', ['area' => 'System Administrator'])->name('dashboard');
});
