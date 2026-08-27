<?php

use App\Http\Controllers\OfficialFormWorkspaceController;
use App\Http\Controllers\ReportController;
use App\Modules\OfficialForms\Services\GetPendingAcademicActionsForUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::prefix('dean')->name('dean.')->middleware([
    'auth', 'verified', 'active', 'permission:dashboards.dean.view', 'workspace.context',
])->group(function (): void {
    Route::get('/dashboard', function (Request $request) {
        $pendingFormInstances = app(OfficialFormWorkspaceController::class)->pendingInstances($request);

        return view('pages.dean-dashboard', [
            'area' => 'College Dean',
            'pendingFormInstances' => $pendingFormInstances,
            'pendingAcademicActions' => app(GetPendingAcademicActionsForUser::class)->execute($request->user()),
            'sidebarBadges' => [
                'pending' => $pendingFormInstances->count(),
                'forms' => $pendingFormInstances->count(),
                'notifications' => Schema::hasTable('notifications')
                    ? $request->user()->unreadNotifications()->count()
                    : 0,
            ],
        ]);
    })->name('dashboard');

    Route::prefix('/reports')->middleware(['permission:reports.view', 'throttle:reports'])->group(function (): void {
        Route::get('/', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::get('/{report}/csv', [ReportController::class, 'csv'])->middleware(['permission:reports.export', 'throttle:report-exports'])->name('reports.csv');
        Route::get('/{report}/pdf', [ReportController::class, 'pdf'])->middleware(['permission:reports.export', 'throttle:report-exports'])->name('reports.pdf');
    });
});
