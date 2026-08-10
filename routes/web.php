<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisabledFeatureController;
use App\Http\Controllers\DocumentAccessController;
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
        Route::get('/{document}/view', [DocumentAccessController::class, 'view'])
            ->whereNumber('document')
            ->middleware('throttle:120,1')
            ->name('view');
        Route::get('/{document}/download', [DocumentAccessController::class, 'download'])
            ->whereNumber('document')
            ->middleware('throttle:60,1')
            ->name('download');
        Route::get('/{document}/history', [DocumentAccessController::class, 'history'])
            ->whereNumber('document')
            ->middleware('throttle:120,1')
            ->name('history');
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
