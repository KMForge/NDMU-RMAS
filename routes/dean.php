<?php

use Illuminate\Support\Facades\Route;

Route::prefix('dean')->name('dean.')->middleware([
    'auth', 'verified', 'active', 'permission:dashboards.dean.view', 'workspace.context',
])->group(function (): void {
    Route::view('/dashboard', 'pages.dean-dashboard', ['area' => 'College Dean'])->name('dashboard');
});
