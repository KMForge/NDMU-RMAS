<?php

use App\Http\Controllers\AccessPendingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentAccessController;
use App\Http\Controllers\Facilitator\ResearchClassFormActorController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfficialFormController;
use App\Http\Controllers\OfficialFormSignatureController;
use App\Http\Controllers\OfficialFormVerificationController;
use App\Http\Controllers\OfficialFormWorkspaceController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\UserSignatureController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'active'])
    ->name('dashboard');

Route::get('/access-pending', AccessPendingController::class)
    ->middleware(['auth', 'verified', 'active'])
    ->name('access.pending');

Route::post('/workspace/{workspace}', WorkspaceController::class)
    ->middleware(['auth', 'verified', 'active', 'throttle:30,1'])
    ->whereIn('workspace', ['admin', 'facilitator', 'dean', 'adviser', 'panelist', 'student'])
    ->name('workspace.switch');

Route::middleware(['auth', 'verified', 'active', 'throttle:120,1'])
    ->prefix('notifications')
    ->name('notifications.')
    ->group(function (): void {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::get('/{notification}/open', [NotificationController::class, 'open'])->whereUuid('notification')->name('open');
        Route::patch('/{notification}/read', [NotificationController::class, 'read'])->whereUuid('notification')->name('read');
        Route::patch('/read-all', [NotificationController::class, 'readAll'])->name('read-all');
    });

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
    ->prefix('official-form-class-actors')
    ->name('official-form-class-actors.')
    ->group(function (): void {
        Route::get('/{researchClass}', [ResearchClassFormActorController::class, 'index'])->whereNumber('researchClass')->name('index');
        Route::post('/{researchClass}', [ResearchClassFormActorController::class, 'store'])->whereNumber('researchClass')->middleware('throttle:class-creation')->name('store');
        Route::delete('/{researchClass}/{assignment}', [ResearchClassFormActorController::class, 'destroy'])->whereNumber(['researchClass', 'assignment'])->middleware('throttle:class-creation')->name('destroy');
    });

Route::middleware(['auth', 'verified', 'active'])
    ->prefix('settings/profile-photo')
    ->name('profile-photo.')
    ->group(function (): void {
        Route::get('/', [ProfilePhotoController::class, 'show'])
            ->middleware('throttle:120,1')
            ->name('show');
        Route::put('/', [ProfilePhotoController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('store');
        Route::delete('/', [ProfilePhotoController::class, 'destroy'])
            ->middleware('throttle:10,1')
            ->name('destroy');
    });

Route::middleware(['auth', 'verified', 'active'])
    ->prefix('settings/signature')
    ->name('signature.')
    ->group(function (): void {
        Route::get('/', [UserSignatureController::class, 'show'])
            ->middleware('throttle:60,1')
            ->name('show');
        Route::get('/preview', [UserSignatureController::class, 'preview'])
            ->middleware('throttle:60,1')
            ->name('preview');
        Route::put('/', [UserSignatureController::class, 'store'])
            ->middleware('throttle:signature-enrollment')
            ->name('store');
        Route::delete('/', [UserSignatureController::class, 'destroy'])
            ->middleware('throttle:signature-enrollment')
            ->name('destroy');
    });

Route::get('/official-forms/{instance}/print', [OfficialFormController::class, 'print'])
    ->middleware(['auth', 'verified', 'active', 'throttle:60,1'])
    ->whereNumber('instance')
    ->name('official-forms.print');

Route::middleware(['auth', 'verified', 'active'])
    ->prefix('official-forms')
    ->name('official-forms.workspace.')
    ->group(function (): void {
        Route::get('/', [OfficialFormWorkspaceController::class, 'index'])->name('index');
        Route::post('/definitions/{definition}', [OfficialFormWorkspaceController::class, 'store'])
            ->middleware('throttle:30,1')->name('store');
        Route::match(['GET', 'POST'], '/definitions/{definition}/sources/{sourceKind}/{source}', [OfficialFormWorkspaceController::class, 'storeFromSource'])
            ->whereNumber('source')
            ->whereIn('sourceKind', ['consultation-record', 'document-review', 'revision-request', 'res-042', 'defense-schedule'])
            ->middleware('throttle:30,1')->name('store-from-source');
        Route::get('/instances/{instance}', [OfficialFormWorkspaceController::class, 'show'])
            ->whereNumber('instance')->name('show');
        Route::post('/instances/{instance}/draft', [OfficialFormWorkspaceController::class, 'save'])
            ->whereNumber('instance')->middleware('throttle:60,1')->name('save');
        Route::post('/instances/{instance}/submit', [OfficialFormWorkspaceController::class, 'submit'])
            ->whereNumber('instance')->middleware('throttle:30,1')->name('submit');
        Route::post('/instances/{instance}/actions/{action}', [OfficialFormWorkspaceController::class, 'action'])
            ->whereNumber('instance')->whereIn('action', ['endorse', 'receive', 'approve', 'reject', 'certify', 'validate'])
            ->middleware('throttle:30,1')->name('action');
        Route::post('/instances/{instance}/actions/{action}/sign', [OfficialFormWorkspaceController::class, 'signAction'])
            ->whereNumber('instance')->whereIn('action', [
                'sign',
                'endorse',
                'receive',
                'approve',
                'reject',
                'certify',
                'validate',
                'sign_authorship',
                'sign_chairperson',
                'sign_member_1',
                'sign_member_2',
            ])
            ->middleware('throttle:30,1')->name('sign-action');
        Route::get('/instances/{instance}/adviser-change-supporting-document', [OfficialFormWorkspaceController::class, 'adviserChangeSupportingDocument'])
            ->name('adviser-change-supporting-document');
        Route::get('/signatures/{signature}/image', [OfficialFormSignatureController::class, 'image'])
            ->whereNumber('signature')->middleware('throttle:120,1')->name('signature-image');
        Route::post('/instances/{instance}/actors', [OfficialFormWorkspaceController::class, 'assignActor'])
            ->whereNumber('instance')->middleware('throttle:30,1')->name('actors.store');
        Route::delete('/instances/{instance}/actors/{assignment}', [OfficialFormWorkspaceController::class, 'deactivateActor'])
            ->whereNumber(['instance', 'assignment'])->middleware('throttle:30,1')->name('actors.destroy');
    });

Route::get('/verify/official-form/{reference}', [OfficialFormVerificationController::class, 'verify'])
    ->middleware('throttle:60,1')
    ->name('official-forms.verify');

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/student.php';
require __DIR__.'/adviser.php';
require __DIR__.'/facilitator.php';
