<?php

namespace App\Http\Controllers\Facilitator;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
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
use App\Modules\Notifications\Services\UnreadNotificationCount;
use App\Modules\OfficialForms\Services\GetPendingAcademicActionsForUser;
use App\Modules\ResearchProgress\Queries\GetFacilitatorProgressData;
use App\Modules\ResearchStatistics\Queries\GetFacilitatorStatisticsData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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
        $activeTab = (string) $request->query('tab', 'dashboard');
        $unreadNotificationCount = app(UnreadNotificationCount::class)->for($request->user());
        $repository = $activeTab === 'repository'
            ? $repositoryData->for($request->user(), $request->query())
            : [];
        $progress = $activeTab === 'monitoring'
            ? $progressData->for(
                $request->user(),
                $request->query('progress_search'),
                $request->query('progress_group_status'),
                $request->query('progress_page'),
                $request->query('progress_group_id'),
            )
            : [];
        $notificationsData = $activeTab === 'notifications'
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
        $statistics = $activeTab === 'statistics'
            ? $statisticsData->for($request->user(), $request->query())
            : ['statistics' => null];

        $defenses = in_array($activeTab, ['dashboard', 'defenses'], true)
            ? $defenseCalendar->execute($request->user())
            : collect();
        $allDefenseRooms = $activeTab === 'defenses'
            ? DefenseRoom::query()->orderByDesc('is_active')->orderBy('code')->get()
            : collect();
        $defenseRooms = $allDefenseRooms->where('is_active', true)->values();
        $evalData = $activeTab === 'defenses'
            ? app(GetEvaluationRoundData::class)->forFacilitator($request->user())
            : ['rounds' => []];
        $defenseSchedulingGroups = $activeTab === 'defenses'
            ? ResearchClassGroup::query()
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
                })
            : collect();
        $facilitatorClasses = $activeTab === 'defenses'
            ? ResearchClass::query()
                ->where('facilitator_id', $request->user()->id)
                ->with(['groups' => fn ($q) => $q->where('status', 'active')])
                ->orderBy('name')
                ->get()
            : collect();

        $defensePanelCandidates = $activeTab === 'defenses'
            ? User::query()
                ->where('user_type', 'faculty')
                ->where(function ($query) {
                    $query->permission(['forms.res-036.evaluate', 'evaluations.create', 'classes.serve-as-adviser']);
                })
                ->where('status', AccountStatus::Active)
                ->whereNotNull('approved_at')
                ->whereNotNull('email_verified_at')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'department'])
            : collect();
        $screening = $screeningData->for($request->user());
        $titleProposalScreeningQueue = $screening['titleProposalScreeningQueue'];
        $adviserApprovedDefenseDocuments = $screening['adviserApprovedDefenseDocuments'];
        $pendingAcademicActions = $pendingActionsService->execute($request->user());
        $classDashboardData = $classData->for(
            $request->user(),
            $request->query('request_q'),
            $request->query('request_status'),
            $activeTab,
        );

        return view('pages.facilitator-dashboard', [
            'area' => 'Research Facilitator',
            'facilitator' => $request->user(),
            'facilitatorClasses' => $facilitatorClasses,
            'pendingAcademicActions' => $pendingAcademicActions,
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
                'forms' => $pendingAcademicActions->count(),
                'classes' => count($classDashboardData['classes'] ?? []),
                'join-requests' => (int) ($classDashboardData['classRequestStats']['pending'] ?? 0),
                'join_requests' => (int) ($classDashboardData['classRequestStats']['pending'] ?? 0),
                'screening' => $titleProposalScreeningQueue->count() + $adviserApprovedDefenseDocuments->count(),
                'title-proposal-screening' => $titleProposalScreeningQueue->count(),
                'defense-scheduling-ready' => $adviserApprovedDefenseDocuments->count(),
                'defenses' => $defenses->filter(fn ($d) => ! in_array(data_get($d, 'defense_status') ?? data_get($d, 'schedule_status') ?? data_get($d, 'status'), ['completed', 'cancelled'], true))->count(),
                'notifications' => $unreadNotificationCount,
            ],
            ...$classDashboardData,
            ...$repository,
            ...$progress,
            ...$notificationsData,
            ...$statistics,
        ]);
    }
}
