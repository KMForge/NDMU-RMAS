<?php

namespace App\Http\Controllers\Panelist;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OfficialFormWorkspaceController;
use App\Modules\DefenseScheduling\Queries\GetDefenseScheduleCalendar;
use App\Modules\Documents\Queries\GetPanelistAssignedDocuments;
use App\Modules\Evaluations\Queries\GetEvaluationRoundData;
use App\Modules\Notifications\Queries\GetNotificationsForUser;
use App\Modules\OfficialForms\Services\GetPendingAcademicActionsForUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        GetDefenseScheduleCalendar $defenseCalendar,
        GetEvaluationRoundData $evaluationQuery,
        GetPendingAcademicActionsForUser $pendingActionsService,
        GetPanelistAssignedDocuments $assignedDocumentsQuery,
        GetNotificationsForUser $notificationQuery,
    ): View {
        $officialForms = config('official-forms.panelist', []);
        $assignedPhases = array_flip(array_unique(array_column($officialForms, 'phase')));
        $assignedDefenses = $defenseCalendar->execute($request->user());
        $evaluationData = $evaluationQuery->forPanelist($request->user());
        $pendingFormInstances = app(OfficialFormWorkspaceController::class)->pendingInstances($request);
        $assignedPapers = $assignedDocumentsQuery->for($request->user());
        $proposalPapers = $assignedPapers
            ->whereIn('defenseType', ['Title Proposal', 'Proposal Defense'])
            ->values();
        $selectedReviewPaper = $assignedPapers->firstWhere(
            'id',
            $request->integer('document_id'),
        );
        $evaluationRounds = collect($evaluationData['rounds'] ?? []);
        $pendingEvaluations = $evaluationRounds->filter(
            fn (array $round): bool => in_array($round['status'] ?? null, ['open', 'in_progress'], true)
                && data_get($round, 'evaluation.status') !== 'submitted',
        );
        $tab = (string) $request->query('tab', 'dashboard');
        $notificationsData = $tab === 'notifications'
            ? [
                'userNotifications' => $notificationQuery->execute($request->user(), (string) $request->query('notification_filter', 'all')),
                'userUnreadCount' => $request->user()->unreadNotifications()->count(),
                'notificationFilter' => (string) $request->query('notification_filter', 'all'),
            ]
            : [
                'userNotifications' => collect(),
                'userUnreadCount' => $request->user()->unreadNotifications()->count(),
                'notificationFilter' => 'all',
            ];

        return view('pages.panelist-dashboard', [
            'area' => 'Panelist',
            'panelist' => $request->user(),
            ...$notificationsData,
            'pendingFormInstances' => $pendingFormInstances,
            'pendingAcademicActions' => $pendingActionsService->execute($request->user()),
            'officialFormPhases' => array_intersect_key(
                config('official-forms.phases', []),
                $assignedPhases,
            ),
            'officialForms' => $officialForms,
            'assignedDefenses' => $assignedDefenses,
            'evaluationRounds' => $evaluationRounds->all(),
            'assignedPapers' => $assignedPapers->all(),
            'proposalPapers' => $proposalPapers->all(),
            'selectedReviewPaper' => $selectedReviewPaper,
            'sidebarBadges' => [
                'assigned-papers' => $assignedPapers
                    ->whereIn('status', ['For Review', 'Under Review', 'Pending Defense'])
                    ->count(),
                'forms' => $pendingFormInstances->count(),
                'proposal-eval' => $pendingEvaluations
                    ->whereIn('defense_type', ['title_presentation', 'proposal_defense'])
                    ->count(),
                'final-eval' => $pendingEvaluations
                    ->whereIn('defense_type', ['pre_final_defense', 'final_defense'])
                    ->count(),
                'notifications' => Schema::hasTable('notifications')
                    ? $request->user()->unreadNotifications()->count()
                    : 0,
            ],
        ]);
    }
}
