<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware([
    'auth', 'verified', 'active', 'role:system-administrator', 'permission:settings.manage',
])->group(function (): void {
    Route::view('/dashboard', 'pages.dashboard', ['area' => 'System Administrator'])->name('dashboard');
});
