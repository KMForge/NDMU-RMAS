<?php

use App\Http\Controllers\OfficialFormWorkspaceController;
<<<<<<< HEAD
=======
use App\Http\Controllers\ReportController;
use App\Modules\OfficialForms\Services\GetPendingAcademicActionsForUser;
>>>>>>> 8b15011507c76d76c221e8be36e9a204fbd67a03
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('dean')->name('dean.')->middleware([
    'auth', 'verified', 'active', 'permission:dashboards.dean.view', 'workspace.context',
])->group(function (): void {
    Route::get('/dashboard', function (Request $request) {
        return view('pages.dean-dashboard', [
            'area' => 'College Dean',
            'pendingFormInstances' => app(OfficialFormWorkspaceController::class)->pendingInstances($request),
        ]);
    })->name('dashboard');

    Route::prefix('/reports')->middleware(['permission:reports.view', 'throttle:reports'])->group(function (): void {
        Route::get('/', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::get('/{report}/csv', [ReportController::class, 'csv'])->middleware(['permission:reports.export', 'throttle:report-exports'])->name('reports.csv');
        Route::get('/{report}/pdf', [ReportController::class, 'pdf'])->middleware(['permission:reports.export', 'throttle:report-exports'])->name('reports.pdf');
    });
});
