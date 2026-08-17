<?php

use App\Http\Controllers\Admin\DefenseRoomController;
use App\Http\Controllers\Facilitator\ClassJoinRequestController;
use App\Http\Controllers\Facilitator\DashboardController;
use App\Http\Controllers\Facilitator\DefenseController;
use App\Http\Controllers\Facilitator\EvaluationRoundController;
use App\Http\Controllers\Facilitator\ResearchClassController;
use App\Http\Controllers\Facilitator\ResearchClassFormActorController;
use App\Http\Controllers\Facilitator\ResearchClassGroupController;
use App\Http\Controllers\Facilitator\ResearchProgressController;
use Illuminate\Support\Facades\Route;

Route::prefix('facilitator')->name('facilitator.')->middleware([
    'auth', 'verified', 'active', 'permission:dashboards.facilitator.view', 'workspace.context',
])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('/defense-rooms')
        ->middleware(['permission:defenses.manage', 'throttle:30,1'])
        ->group(function (): void {
            Route::post('/', [DefenseRoomController::class, 'store'])->name('defense-rooms.store');
            Route::patch('/{room}', [DefenseRoomController::class, 'update'])->whereNumber('room')->name('defense-rooms.update');
            Route::patch('/{room}/activate', [DefenseRoomController::class, 'activate'])->whereNumber('room')->name('defense-rooms.activate');
            Route::patch('/{room}/deactivate', [DefenseRoomController::class, 'deactivate'])->whereNumber('room')->name('defense-rooms.deactivate');
        });

    Route::prefix('/defenses')
        ->middleware(['permission:defenses.manage', 'throttle:defense-actions'])
        ->group(function (): void {
            Route::post('/', [DefenseController::class, 'store'])->name('defenses.store');
            Route::patch('/{defense}/reschedule', [DefenseController::class, 'reschedule'])->whereNumber('defense')->name('defenses.reschedule');
            Route::patch('/{defense}/cancel', [DefenseController::class, 'cancel'])->whereNumber('defense')->name('defenses.cancel');
            Route::post('/{defense}/panel', [DefenseController::class, 'assignPanel'])->whereNumber('defense')->name('defenses.panel');
            Route::post('/{defense}/evaluation-round', [EvaluationRoundController::class, 'open'])->whereNumber('defense')->name('defenses.evaluation-round.open');
            Route::post('/{defense}/complete', [EvaluationRoundController::class, 'complete'])->whereNumber('defense')->name('defenses.complete');
        });

    Route::prefix('/evaluation-rounds')
        ->middleware(['permission:defenses.manage', 'throttle:defense-actions'])
        ->group(function (): void {
            Route::patch('/{round}/summary-signer', [EvaluationRoundController::class, 'designateSigner'])->whereNumber('round')->name('evaluation-rounds.summary-signer');
            Route::post('/{round}/release', [EvaluationRoundController::class, 'release'])->middleware('permission:evaluations.release')->whereNumber('round')->name('evaluation-rounds.release');
        });

    Route::post('/classes', [ResearchClassController::class, 'store'])
        ->middleware(['permission:classes.create', 'throttle:class-creation'])
        ->name('classes.store');
    Route::get('/classes/{researchClass}', [ResearchClassController::class, 'show'])
        ->middleware('permission:classes.view-own')
        ->whereNumber('researchClass')
        ->name('classes.show');

    Route::get('/groups/{group}/progress', [ResearchProgressController::class, 'show'])
        ->middleware('permission:progress.view-owned-classes')
        ->whereNumber('group')
        ->name('progress.show');

    Route::prefix('/progress/{milestone}')
        ->whereNumber('milestone')
        ->middleware(['permission:progress.manage-owned-classes', 'throttle:progress-actions'])
        ->group(function (): void {
            Route::patch('/start', [ResearchProgressController::class, 'start'])->name('progress.start');
            Route::patch('/complete', [ResearchProgressController::class, 'complete'])->name('progress.complete');
            Route::patch('/correct', [ResearchProgressController::class, 'correct'])->name('progress.correct');
            Route::patch('/not-applicable', [ResearchProgressController::class, 'notApplicable'])->name('progress.not-applicable');
            Route::patch('/due-date', [ResearchProgressController::class, 'dueDate'])->name('progress.due-date');
            Route::post('/evidence', [ResearchProgressController::class, 'evidence'])->name('progress.evidence');
        });

    Route::prefix('/classes/{researchClass}')
        ->whereNumber('researchClass')
        ->group(function (): void {
            Route::post('/official-form-actors', [ResearchClassFormActorController::class, 'store'])
                ->middleware(['permission:classes.view-own', 'throttle:class-creation'])
                ->name('classes.form-actors.store');
            Route::delete('/official-form-actors/{assignment}', [ResearchClassFormActorController::class, 'destroy'])
                ->middleware(['permission:classes.view-own', 'throttle:class-creation'])
                ->whereNumber('assignment')
                ->name('classes.form-actors.destroy');
            Route::post('/groups', [ResearchClassGroupController::class, 'store'])
                ->middleware(['permission:classes.manage-groups', 'throttle:class-creation'])
                ->name('classes.groups.store');
            Route::patch('/groups/{group}/rename', [ResearchClassGroupController::class, 'rename'])
                ->middleware(['permission:classes.manage-groups', 'throttle:class-creation'])
                ->whereNumber('group')
                ->name('classes.groups.rename');
            Route::delete('/groups/{group}', [ResearchClassGroupController::class, 'disband'])
                ->middleware(['permission:classes.manage-groups', 'throttle:class-creation'])
                ->whereNumber('group')
                ->name('classes.groups.disband');

            Route::put('/groups/{group}/students/{enrollment}', [ResearchClassGroupController::class, 'assignStudent'])
                ->middleware(['permission:classes.manage-groups', 'throttle:class-join-decisions'])
                ->whereNumber(['group', 'enrollment'])
                ->name('classes.groups.students.assign');

            Route::put('/groups/{group}/leader', [ResearchClassGroupController::class, 'assignLeader'])
                ->middleware(['permission:classes.manage-groups', 'throttle:class-creation'])
                ->whereNumber('group')
                ->name('classes.groups.leader.assign');

            Route::post('/groups/{group}/adviser-requests', [ResearchClassGroupController::class, 'requestAdviser'])
                ->middleware(['permission:classes.assign-advisers', 'throttle:class-join-decisions'])
                ->whereNumber('group')
                ->name('classes.groups.adviser-requests.store');
            Route::put('/groups/{group}/adviser', [ResearchClassGroupController::class, 'requestAdviser'])
                ->middleware(['permission:classes.assign-advisers', 'throttle:class-join-decisions'])
                ->whereNumber('group')
                ->name('classes.groups.adviser.assign');
            Route::delete('/groups/{group}/adviser-requests/{adviserRequest}', [ResearchClassGroupController::class, 'cancelAdviserRequest'])
                ->middleware(['permission:classes.assign-advisers', 'throttle:class-join-decisions'])
                ->whereNumber(['group', 'adviserRequest'])
                ->name('classes.groups.adviser-requests.cancel');
            Route::delete('/groups/{group}/adviser', [ResearchClassGroupController::class, 'removeAdviser'])
                ->middleware(['permission:classes.assign-advisers', 'throttle:class-join-decisions'])
                ->whereNumber('group')
                ->name('classes.groups.adviser.remove');

            Route::prefix('/join-requests/{joinRequest}')
                ->whereNumber('joinRequest')
                ->middleware(['permission:classes.manage-join-requests', 'throttle:class-join-decisions'])
                ->group(function (): void {
                    Route::patch('/approve', [ClassJoinRequestController::class, 'approve'])
                        ->name('classes.join-requests.approve');
                    Route::patch('/reject', [ClassJoinRequestController::class, 'reject'])
                        ->name('classes.join-requests.reject');
                });
        });
});
