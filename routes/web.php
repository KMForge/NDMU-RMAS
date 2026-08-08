<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisabledFeatureController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'active'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'active'])
    ->prefix('documents')
    ->name('documents.')
    ->group(function (): void {
        Route::get('/{document}/view', DisabledFeatureController::class)
            ->whereNumber('document')
            ->name('view');
        Route::get('/{document}/download', DisabledFeatureController::class)
            ->whereNumber('document')
            ->name('download');
    });

Route::middleware(['auth', 'verified', 'active'])
    ->prefix('settings/signature')
    ->name('signature.')
    ->group(function (): void {
        Route::get('/', DisabledFeatureController::class)
            ->middleware('throttle:60,1')
            ->name('show');
        Route::put('/', DisabledFeatureController::class)
            ->middleware('throttle:signature-enrollment')
            ->name('store');
        Route::delete('/', DisabledFeatureController::class)
            ->middleware('throttle:signature-enrollment')
            ->name('destroy');
    });

require __DIR__.'/auth.php';
