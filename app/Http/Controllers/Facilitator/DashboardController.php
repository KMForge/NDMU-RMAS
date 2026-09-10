<?php

namespace App\Http\Controllers\Facilitator;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OfficialFormWorkspaceController;
use App\Models\DefenseRoom;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\Classes\Queries\GetFacilitatorClassData;
use App\Modules\DefenseScheduling\Queries\GetDefenseScheduleCalendar;
use App\Modules\DefenseScheduling\Services\DefenseEndorsementEligibility;
use App\Modules\Documents\Queries\GetDocumentRepositoryData;
use App\Modules\Documents\Queries\GetFacilitatorScreeningData;
use App\Modules\Evaluations\Queries\GetEvaluationRoundData;
use App\Modules\Notifications\Queries\GetNotificationsForUser;
use App\Modules\OfficialForms\Services\GetPendingAcademicActionsForUser;
use App\Modules\ResearchProgress\Queries\GetFacilitatorProgressData;
use App\Modules\ResearchStatistics\Queries\GetFacilitatorStatisticsData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        GetFacilitatorClassData $classData,
        GetDocumentRepositoryData $repositoryData,
        GetFacilitatorScreeningData $screeningData,
        GetFacilitatorProgressData $progressData,
        GetDefenseScheduleCalendar $defenseCalendar,
        DefenseEndorsementEligibility $endorsementEligibility,
        GetPendingAcademicActionsForUser $pendingActionsService,
        GetNotificationsForUser $notificationQuery,
        GetFacilitatorStatisticsData $statisticsData,
    ): View {
        $repository = $request->query('tab') === 'repository'
            ? $repositoryData->for($request->user(), $request->query())
            : [];
        $progress = $request->query('tab') === 'monitoring'
            ? $progressData->for(
                $request->user(),
                $request->query('progress_search'),
                $request->query('progress_group_status'),
                $request->query('progress_page'),
                $request->query('progress_group_id'),
            )
            : [];
        $notificationsData = $request->query('tab') === 'notifications'
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
        $statistics = $request->query('tab') === 'statistics'
            ? $statisticsData->for($request->user(), $request->query())
            : ['statistics' => null];

        $defenses = $defenseCalendar->execute($request->user());
        $allDefenseRooms = DefenseRoom::query()
            ->orderByDesc('is_active')
            ->orderBy('code')
            ->get();
        $defenseRooms = $allDefenseRooms->where('is_active', true)->values();
        $evalQuery = app(GetEvaluationRoundData::class);
        $evalData = $evalQuery->forFacilitator($request->user());
        $defenseSchedulingGroups = ResearchClassGroup::query()
            ->where('status', 'active')
            ->whereHas('researchClass', fn ($query) => $query->where('facilitator_id', $request->user()->id))
            ->with([
                'researchClass:id,name,facilitator_id',
                'researchClass.panelCommittees.chairperson:id,name',
                'researchClass.panelCommittees.members.user:id,name',
                'researchGroup.currentProject' => fn ($query) => $query->select([
                    'research_projects.id',
                    'research_projects.research_group_id',
                    'research_projects.title',
                ]),
                'leader:id,name,department',
                'adviser:id,name,department',
                'panelCommittees.chairperson:id,name',
                'panelCommittees.members.user:id,name',
                'defenses' => fn ($query) => $query->with([
                    'activePanelAssignments.user:id,name',
                ])->orderByDesc('id'),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (ResearchClassGroup $group) use ($endorsementEligibility): array {
                $defenseTypes = [
                    'title_presentation',
                    'proposal_defense',
                    'pre_final_defense',
                    'final_defense',
                ];
                $titleDefense = $group->defenses->firstWhere('defense_type', 'title_presentation');
                $titleChairperson = $titleDefense?->activePanelAssignments->firstWhere('panel_position', 'chairperson');
                $latestChairperson = $titleChairperson ?? $group->defenses->flatMap->activePanelAssignments->firstWhere('panel_position', 'chairperson');

                $titleMember1 = $titleDefense?->activePanelAssignments->firstWhere('panel_position', 'member_1')
                    ?? $group->defenses->flatMap->activePanelAssignments->firstWhere('panel_position', 'member_1');

                $titleMember2 = $titleDefense?->activePanelAssignments->firstWhere('panel_position', 'member_2')
                    ?? $group->defenses->flatMap->activePanelAssignments->firstWhere('panel_position', 'member_2');

                $groupDept = $group->leader?->department
                    ?: ($group->adviser?->department
                    ?: ($group->researchClass?->facilitator?->department ?: 'Computer Studies Department'));

                $committeeAssignments = collect($defenseTypes)->mapWithKeys(function (string $defenseType) use ($group): array {
                    $groupCommittee = $group->panelCommittees->firstWhere('defense_type', $defenseType);
                    $classCommittee = $group->researchClass?->panelCommittees->firstWhere('defense_type', $defenseType);
                    $committee = $groupCommittee ?? $classCommittee;
                    $members = $committee?->members?->sortBy('panel_position')->values() ?? collect();
                    $memberOne = $members->firstWhere('panel_position', 'member_1')?->user ?? $members->get(0)?->user;
                    $memberTwo = $members->firstWhere('panel_position', 'member_2')?->user ?? $members->get(1)?->user;

                    return [$defenseType => [
                        'chairperson_id' => $committee?->chairperson_id,
                        'chairperson_name' => $committee?->chairperson?->name,
                        'member_1_id' => $memberOne?->id,
                        'member_1_name' => $memberOne?->name,
                        'member_2_id' => $memberTwo?->id,
                        'member_2_name' => $memberTwo?->name,
                        'source_label' => $groupCommittee !== null
                            ? ($groupCommittee->is_custom ? 'Customized group committee' : 'Class defense committee')
                            : ($classCommittee !== null ? 'Class defense committee' : null),
                    ]];
                })->all();

                return [
                    'id' => $group->id,
                    'class_id' => $group->research_class_id,
                    'name' => $group->name,
                    'department' => $groupDept,
                    'class_name' => $group->researchClass?->name,
                    'research_title' => $group->researchGroup?->currentProject?->title,
                    'leader_name' => $group->leader?->name,
                    'adviser_id' => $group->adviser_id,
                    'adviser_name' => $group->adviser?->name,
                    'adviser_department' => $group->adviser?->department,
                    'chairperson_id' => $latestChairperson?->user_id,
                    'chairperson_name' => $latestChairperson?->user?->name,
                    'has_title_chairperson' => $titleChairperson !== null,
                    'member_1_id' => $titleMember1?->user_id,
                    'member_1_name' => $titleMember1?->user?->name,
                    'member_2_id' => $titleMember2?->user_id,
                    'member_2_name' => $titleMember2?->user?->name,
                    'committee_assignments' => $committeeAssignments,
                    'res033_eligibility' => collect($defenseTypes)->mapWithKeys(fn (string $defenseType): array => [
                        $defenseType => $endorsementEligibility->isComplete($group, $defenseType),
                    ])->all(),
                ];
            });
        $facilitatorClasses = ResearchClass::query()
            ->where('facilitator_id', $request->user()->id)
            ->with(['groups' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->get();

        $defensePanelCandidates = User::query()
            ->where('user_type', 'faculty')
            ->where(function ($query) {
                $query->permission(['forms.res-036.evaluate', 'evaluations.create', 'classes.serve-as-adviser']);
            })
            ->where('status', AccountStatus::Active)
            ->whereNotNull('approved_at')
            ->whereNotNull('email_verified_at')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'department']);
        $screening = $screeningData->for($request->user());
        $titleProposalScreeningQueue = $screening['titleProposalScreeningQueue'];
        $adviserApprovedDefenseDocuments = $screening['adviserApprovedDefenseDocuments'];
        $pendingFormInstances = app(OfficialFormWorkspaceController::class)->pendingInstances($request);
        $classDashboardData = $classData->for(
            $request->user(),
            $request->query('request_q'),
            $request->query('request_status'),
        );

        return view('pages.facilitator-dashboard', [
            'area' => 'Research Facilitator',
            'facilitator' => $request->user(),
            'facilitatorClasses' => $facilitatorClasses,
            'pendingFormInstances' => $pendingFormInstances,
            'pendingAcademicActions' => $pendingActionsService->execute($request->user()),
            'officialFormPhases' => config('official-forms.phases', []),
            'officialForms' => collect(config('official-forms.facilitator', []))
                ->filter(fn (array $form, string $code) => $request->user()->getAllPermissions()
                    ->contains(fn ($permission) => str_starts_with($permission->name, 'forms.'.strtolower($code).'.')))
                ->all(),
            'defenses' => $defenses,
            'defenseRooms' => $defenseRooms,
            'allDefenseRooms' => $allDefenseRooms,
            'evaluationRounds' => $evalData['rounds'] ?? [],
            'defenseSchedulingGroups' => $defenseSchedulingGroups,
            'defensePanelCandidates' => $defensePanelCandidates,
            'titleProposalScreeningQueue' => $titleProposalScreeningQueue,
            'adviserApprovedDefenseDocuments' => $adviserApprovedDefenseDocuments,
            'facilitatorScreeningHistory' => $screening['facilitatorScreeningHistory'],
            'facilitatorScreeningStats' => $screening['facilitatorScreeningStats'],
            'sidebarBadges' => [
                'forms' => $pendingFormInstances->count(),
                'classes' => count($classDashboardData['classes'] ?? []),
                'join-requests' => (int) ($classDashboardData['classRequestStats']['pending'] ?? 0),
                'join_requests' => (int) ($classDashboardData['classRequestStats']['pending'] ?? 0),
                'screening' => $titleProposalScreeningQueue->count() + $adviserApprovedDefenseDocuments->count(),
                'title-proposal-screening' => $titleProposalScreeningQueue->count(),
                'defense-scheduling-ready' => $adviserApprovedDefenseDocuments->count(),
                'defenses' => $defenses->filter(fn ($d) => ! in_array(data_get($d, 'defense_status') ?? data_get($d, 'schedule_status') ?? data_get($d, 'status'), ['completed', 'cancelled'], true))->count(),
                'notifications' => Schema::hasTable('notifications')
                    ? $request->user()->unreadNotifications()->count()
                    : 0,
            ],
            ...$classDashboardData,
            ...$repository,
            ...$progress,
            ...$notificationsData,
            ...$statistics,
        ]);
    }
}
