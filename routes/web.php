<?php

use App\Http\Controllers\DashboardController;
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
            ->name('view');
        Route::get('/{document}/download', [DocumentAccessController::class, 'download'])
            ->whereNumber('document')
            ->name('download');
    });

require __DIR__.'/auth.php';
