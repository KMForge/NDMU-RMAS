<?php

use App\Http\Controllers\Admin\DefenseRoomController;
use App\Http\Controllers\Admin\SystemBackupDownloadController;
use App\Http\Controllers\Admin\SystemBackupUploadController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware([
    'auth', 'verified', 'active', 'permission:dashboards.admin.view', 'workspace.context',
])->group(function (): void {
    Route::view('/dashboard', 'pages.admin-dashboard', ['area' => 'System Administrator'])->name('dashboard');

    Route::get('/backups/{backup}/download', SystemBackupDownloadController::class)
        ->middleware(['permission:settings.manage', 'throttle:10,1'])
        ->whereNumber('backup')
        ->name('backups.download');

    Route::prefix('/backup-uploads')
        ->middleware(['permission:settings.manage'])
        ->name('backup-uploads.')
        ->group(function (): void {
            Route::post('/', [SystemBackupUploadController::class, 'store'])
                ->middleware('throttle:30,1')
                ->name('store');
            Route::post('/{upload}/chunks/{index}', [SystemBackupUploadController::class, 'chunk'])
                ->middleware('throttle:240,1')
                ->whereUuid('upload')
                ->whereNumber('index')
                ->name('chunks.store');
            Route::post('/{upload}/complete', [SystemBackupUploadController::class, 'complete'])
                ->middleware('throttle:10,1')
                ->whereUuid('upload')
                ->name('complete');
            Route::delete('/{upload}', [SystemBackupUploadController::class, 'destroy'])
                ->middleware('throttle:30,1')
                ->whereUuid('upload')
                ->name('destroy');
        });

    Route::prefix('/reports')->middleware(['permission:reports.view', 'throttle:reports'])->group(function (): void {
        Route::get('/', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::get('/{report}/csv', [ReportController::class, 'csv'])->middleware(['permission:reports.export', 'throttle:report-exports'])->name('reports.csv');
        Route::get('/{report}/pdf', [ReportController::class, 'pdf'])->middleware(['permission:reports.export', 'throttle:report-exports'])->name('reports.pdf');
    });

    Route::prefix('/defense-rooms')
        ->middleware(['permission:settings.manage', 'throttle:30,1'])
        ->group(function (): void {
            Route::post('/', [DefenseRoomController::class, 'store'])->name('defense-rooms.store');
            Route::patch('/{room}', [DefenseRoomController::class, 'update'])->whereNumber('room')->name('defense-rooms.update');
            Route::patch('/{room}/activate', [DefenseRoomController::class, 'activate'])->whereNumber('room')->name('defense-rooms.activate');
            Route::patch('/{room}/deactivate', [DefenseRoomController::class, 'deactivate'])->whereNumber('room')->name('defense-rooms.deactivate');
        });
});
