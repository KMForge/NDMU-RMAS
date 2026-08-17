<?php

use App\Http\Controllers\OfficialFormWorkspaceController;
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
});
