<?php

use App\Http\Controllers\ReportController;
use App\Modules\Dashboard\Queries\GetDeanDashboardData;
use App\Modules\Notifications\Queries\GetNotificationsForUser;
use App\Modules\Notifications\Services\UnreadNotificationCount;
use App\Modules\OfficialForms\Services\GetPendingAcademicActionsForUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::prefix('dean')->name('dean.')->middleware([
    'auth', 'verified', 'active', 'permission:dashboards.dean.view', 'workspace.context',
])->group(function (): void {
    Route::get('/dashboard', function (Request $request) {
        $pendingAcademicActions = app(GetPendingAcademicActionsForUser::class)->execute($request->user());
        $tab = (string) $request->query('tab', 'dashboard');
        $unreadNotificationCount = app(UnreadNotificationCount::class)->for($request->user());
        $notificationsData = $tab === 'notifications'
            ? [
                'userNotifications' => app(GetNotificationsForUser::class)->execute($request->user(), (string) $request->query('notification_filter', 'all')),
                'userUnreadCount' => $unreadNotificationCount,
                'notificationFilter' => (string) $request->query('notification_filter', 'all'),
            ]
            : [
                'userNotifications' => collect(),
                'userUnreadCount' => $unreadNotificationCount,
                'notificationFilter' => 'all',
            ];

        return view('pages.dean-dashboard-live', [
            'area' => 'College Dean',
            'dean' => $request->user(),
            'pendingAcademicActions' => $pendingAcademicActions,
            'sidebarBadges' => [
                'pending' => $pendingAcademicActions->count(),
                'forms' => $pendingAcademicActions->count(),
                'notifications' => Schema::hasTable('notifications')
                    ? $unreadNotificationCount
                    : 0,
            ],
            ...app(GetDeanDashboardData::class)->for($request->user(), $request->query()),
            ...$notificationsData,
        ]);
    })->name('dashboard');

    Route::prefix('/reports')->middleware(['permission:reports.view', 'throttle:reports'])->group(function (): void {
        Route::get('/', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::get('/{report}/csv', [ReportController::class, 'csv'])->middleware(['permission:reports.export', 'throttle:report-exports'])->name('reports.csv');
        Route::get('/{report}/pdf', [ReportController::class, 'pdf'])->middleware(['permission:reports.export', 'throttle:report-exports'])->name('reports.pdf');
    });
});
