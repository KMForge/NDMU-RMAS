<?php

namespace App\Http\Controllers\Panelist;

use App\Http\Controllers\Controller;
use App\Modules\DefenseScheduling\Queries\GetDefenseScheduleCalendar;
use App\Modules\Documents\Queries\GetPanelistAssignedDocuments;
use App\Modules\Evaluations\Queries\GetEvaluationRoundData;
use App\Modules\Notifications\Queries\GetNotificationsForUser;
use App\Modules\Notifications\Services\UnreadNotificationCount;
use App\Modules\OfficialForms\Services\GetPendingAcademicActionsForUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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
        $pendingAcademicActions = $pendingActionsService->execute($request->user());
        $assignedPapers = $assignedDocumentsQuery->for($request->user());
        $proposalPapers = $assignedPapers
            ->whereIn('defenseType', ['Title Proposal', 'Proposal Defense'])
            ->values();
        $finalPapers = $assignedPapers
            ->whereIn('defenseType', ['Pre-Final Defense', 'Final Defense', 'Final Oral Defense'])
            ->values();
        $selectedReviewPaper = $assignedPapers->firstWhere(
            'id',
            $request->integer('document_id'),
        ) ?? $assignedPapers->first();
        $evaluationRounds = collect($evaluationData['rounds'] ?? []);
        $pendingEvaluations = $evaluationRounds->filter(
            fn (array $round): bool => in_array($round['status'] ?? null, ['open', 'in_progress'], true)
                && data_get($round, 'evaluation.status') !== 'submitted',
        );
        $tab = (string) $request->query('tab', 'dashboard');
        $unreadNotificationCount = app(UnreadNotificationCount::class)->for($request->user());
        $notificationsData = $tab === 'notifications'
            ? [
                'userNotifications' => $notificationQuery->execute($request->user(), (string) $request->query('notification_filter', 'all')),
                'userUnreadCount' => $unreadNotificationCount,
                'notificationFilter' => (string) $request->query('notification_filter', 'all'),
            ]
            : [
                'userNotifications' => collect(),
                'userUnreadCount' => $unreadNotificationCount,
                'notificationFilter' => 'all',
            ];

        return view('pages.panelist-dashboard', [
            'area' => 'Panelist',
            'panelist' => $request->user(),
            ...$notificationsData,
            'pendingAcademicActions' => $pendingAcademicActions,
            'officialFormPhases' => array_intersect_key(
                config('official-forms.phases', []),
                $assignedPhases,
            ),
            'officialForms' => $officialForms,
            'assignedDefenses' => $assignedDefenses,
            'evaluationRounds' => $evaluationRounds->all(),
            'assignedPapers' => $assignedPapers->all(),
            'proposalPapers' => $proposalPapers->all(),
            'finalPapers' => $finalPapers->all(),
            'selectedReviewPaper' => $selectedReviewPaper,
            'sidebarBadges' => [
                'assigned-papers' => $assignedPapers
                    ->whereIn('status', ['For Review', 'Under Review', 'Pending Defense'])
                    ->count(),
                'forms' => $pendingAcademicActions->count(),
                'proposal-eval' => $pendingEvaluations
                    ->whereIn('defense_type', ['title_presentation', 'proposal_defense'])
                    ->count(),
                'final-eval' => $pendingEvaluations
                    ->whereIn('defense_type', ['pre_final_defense', 'final_defense'])
                    ->count(),
                'notifications' => $unreadNotificationCount,
            ],
        ]);
    }
}
