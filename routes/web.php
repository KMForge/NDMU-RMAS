<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentAccessController;
use App\Http\Controllers\UserSignatureController;
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
            ->name('view');
        Route::get('/{document}/download', [DocumentAccessController::class, 'download'])
            ->whereNumber('document')
            ->name('download');
    });

Route::middleware(['auth', 'verified', 'active'])
    ->prefix('settings/signature')
    ->name('signature.')
    ->group(function (): void {
        Route::get('/', [UserSignatureController::class, 'show'])
            ->middleware('throttle:60,1')
            ->name('show');
        Route::put('/', [UserSignatureController::class, 'store'])
            ->middleware('throttle:signature-enrollment')
            ->name('store');
        Route::delete('/', [UserSignatureController::class, 'destroy'])
            ->middleware('throttle:signature-enrollment')
            ->name('destroy');
    });

require __DIR__.'/auth.php';
