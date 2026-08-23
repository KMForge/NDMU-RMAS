<?php

namespace App\Http\Controllers\Facilitator;

use App\Enums\AccountStatus;
use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OfficialFormWorkspaceController;
use App\Models\DefenseRoom;
use App\Models\Document;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\Classes\Queries\GetFacilitatorClassData;
use App\Modules\DefenseScheduling\Queries\GetDefenseScheduleCalendar;
use App\Modules\Documents\Queries\GetDocumentRepositoryData;
use App\Modules\Evaluations\Queries\GetEvaluationRoundData;
use App\Modules\OfficialForms\Services\GetPendingAcademicActionsForUser;
use App\Modules\ResearchProgress\Queries\GetFacilitatorProgressData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        GetFacilitatorClassData $classData,
        GetDocumentRepositoryData $repositoryData,
        GetFacilitatorProgressData $progressData,
        GetDefenseScheduleCalendar $defenseCalendar,
        GetPendingAcademicActionsForUser $pendingActionsService,
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
            )
            : [];

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
                'researchGroup.currentProject' => fn ($query) => $query->select([
                    'research_projects.id',
                    'research_projects.research_group_id',
                    'research_projects.title',
                ]),
                'leader:id,name',
                'adviser:id,name',
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (ResearchClassGroup $group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'class_name' => $group->researchClass?->name,
                'research_title' => $group->researchGroup?->currentProject?->title,
                'leader_name' => $group->leader?->name,
                'adviser_id' => $group->adviser_id,
                'adviser_name' => $group->adviser?->name,
            ]);
        $defensePanelCandidates = User::query()
            ->permission('evaluations.create')
            ->where('status', AccountStatus::Active)
            ->whereNotNull('approved_at')
            ->whereNotNull('email_verified_at')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        $titleProposalScreeningQueue = Document::query()
            ->with(['user:id,name,email', 'researchClassGroup:id,research_class_id,name'])
            ->where('document_stage', DocumentStage::TitleProposal->value)
            ->where('status', DocumentStatus::Submitted->value)
            ->where('is_current', true)
            ->whereHas('researchClassGroup.researchClass', fn ($query) => $query->where('facilitator_id', $request->user()->id))
            ->latest('submitted_at')
            ->get();
        $pendingFormInstances = app(OfficialFormWorkspaceController::class)->pendingInstances($request);
        $classDashboardData = $classData->for(
            $request->user(),
            $request->query('request_q'),
            $request->query('request_status'),
        );

        return view('pages.facilitator-dashboard', [
            'area' => 'Research Facilitator',
            'facilitator' => $request->user(),
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
            'sidebarBadges' => [
                'forms' => $pendingFormInstances->count(),
                'join-requests' => (int) ($classDashboardData['classRequestStats']['pending'] ?? 0),
                'screening' => $titleProposalScreeningQueue->count(),
                'notifications' => Schema::hasTable('notifications')
                    ? $request->user()->unreadNotifications()->count()
                    : 0,
            ],
            ...$classDashboardData,
            ...$repository,
            ...$progress,
        ]);
    }
}
