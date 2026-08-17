<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OfficialFormWorkspaceController;
use App\Models\ConsultationRequest;
use App\Models\Document;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserRequest;
use App\Modules\Consultations\Queries\GetAdviserConsultationData;
use App\Modules\DefenseScheduling\Queries\GetDefenseScheduleCalendar;
use App\Modules\Documents\Queries\GetAdviserDocumentReviewData;
use App\Modules\Documents\Queries\GetDocumentRepositoryData;
use App\Modules\Evaluations\Queries\GetEvaluationRoundData;
use App\Modules\Research\Queries\GetAdviserDashboardOverview;
use App\Modules\ResearchProgress\Queries\GetResearchGroupProgress;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        GetDocumentRepositoryData $repositoryData,
        GetAdviserDocumentReviewData $reviewData,
        GetAdviserConsultationData $consultationData,
        GetResearchGroupProgress $groupProgress,
        GetAdviserDashboardOverview $overviewData,
        GetDefenseScheduleCalendar $defenseCalendar,
    ): View {
        $allowedTabs = [
            'dashboard',
            'classes',
            'researchers',
            'proposal',
            'monitoring',
            'consultation',
            'docreview',
            'revisions',
            'endorsement',
            'evaluations',
            'repository',
            'forms',
            'notifications',
            'settings',
        ];
        $activeTab = in_array($request->query('tab'), $allowedTabs, true)
            ? (string) $request->query('tab')
            : 'dashboard';

        $user = $request->user();
        $viewData = $this->emptyViewData();

        $pendingAdviserRequests = ResearchClassGroupAdviserRequest::query()
            ->where('adviser_id', $user->getKey())
            ->where('status', 'pending')
            ->with([
                'group' => fn ($query) => $query->where('status', 'active')->with(['researchClass:id,name', 'members']),
                'requester:id,name,email',
            ])
            ->latest()
            ->get();

        $assignedGroups = ResearchClassGroup::query()
            ->where('adviser_id', $user->getKey())
            ->where('status', 'active')
            ->with([
                'researchClass:id,name,facilitator_id',
                'members' => fn ($query) => $query->with('student:id,name,email,student_id,program,year_level'),
            ])
            ->latest()
            ->get();

        $pendingConsultationsCount = Schema::hasTable('consultation_requests')
            ? ConsultationRequest::query()
                ->whereHas('researchClassGroup', fn ($g) => $g->where('adviser_id', $user->getKey())->where('status', 'active')->whereNull('disbanded_at'))
                ->whereIn('status', ['pending', 'reschedule_proposed'])
                ->count()
            : 0;

        $pendingDocReviewsCount = Document::query()
            ->whereHas('researchClassGroup', fn ($g) => $g->where('adviser_id', $user->getKey())->where('status', 'active')->whereNull('disbanded_at'))
            ->where('status', 'needs_attention')
            ->count();

        $viewData['pendingAdviserRequests'] = $pendingAdviserRequests;
        $viewData['pendingAdviserRequestsCount'] = $pendingAdviserRequests->count();
        $viewData['pendingConsultationsCount'] = $pendingConsultationsCount;
        $viewData['pendingDocReviewsCount'] = $pendingDocReviewsCount;
        $viewData['pendingFormInstances'] = app(OfficialFormWorkspaceController::class)->pendingInstances($request);
        $viewData['assignedGroups'] = $assignedGroups;
        $viewData['adviserDefenses'] = $defenseCalendar->execute($user);
        $evalQuery = app(GetEvaluationRoundData::class);
        $evalData = $evalQuery->forAdviser($user);
        $viewData['adviserEvaluations'] = $evalData['rounds'] ?? [];
        $viewData['officialFormPhases'] = config('official-forms.phases', []);
        $viewData['officialForms'] = collect(config('official-forms.adviser', []))
            ->filter(fn (array $form, string $code) => $user->getAllPermissions()
                ->contains(fn ($permission) => str_starts_with($permission->name, 'forms.'.strtolower($code).'.')))
            ->all();

        if (in_array($activeTab, ['dashboard', 'notifications'], true)) {
            $viewData = [...$viewData, ...$overviewData->for($user, $activeTab === 'dashboard')];
        }

        if ($activeTab === 'repository') {
            $viewData = [...$viewData, ...$repositoryData->for($user, $request->query())];
        }

        if ($activeTab === 'docreview') {
            $viewData = [...$viewData, ...$reviewData->for(
                $user,
                (string) $request->query('document_search', ''),
                (string) $request->query('document_status', 'needs_attention'),
                $request->query('document_stage') ? (string) $request->query('document_stage') : null,
                $request->query('document_group_id') ? (int) $request->query('document_group_id') : null,
                $request->query('document_file_type') ? (string) $request->query('document_file_type') : null,
                (string) $request->query('document_sort', 'newest'),
                $request->query('document_id') ? (int) $request->query('document_id') : null,
            )];
        }

        if ($activeTab === 'consultation') {
            $viewData = [...$viewData, ...$consultationData->for(
                $user,
                (string) $request->query('consultation_search', ''),
                (string) $request->query('consultation_status', 'pending'),
            )];
        }

        if ($activeTab === 'monitoring') {
            $viewData['adviserProgressGroups'] = $assignedGroups->map(function (ResearchClassGroup $group) use ($groupProgress): ResearchClassGroup {
                $group->setAttribute('progress_summary', $groupProgress->for($group));

                return $group;
            });
        }

        return view('pages.adviser-dashboard', [
            'area' => 'Research Adviser',
            'adviser' => $user,
            'activeDashboardTab' => $activeTab,
            ...$viewData,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyViewData(): array
    {
        return [
            'researchClasses' => new Collection,
            'consultationRequests' => new LengthAwarePaginator([], 0, 10),
            'consultationRecords' => new Collection,
            'consultationStats' => [
                'pending' => 0,
                'approved' => 0,
                'completed' => 0,
                'rejected' => 0,
                'total' => 0,
            ],
            'consultationSearch' => '',
            'consultationStatus' => 'pending',
            'reviewDocuments' => new LengthAwarePaginator([], 0, 8),
            'selectedReviewDocument' => null,
            'documentReviewComments' => new Collection,
            'documentReviewStats' => ['approved' => 0, 'revisions' => 0, 'comments' => 0, 'critical' => 0],
            'documentReviewSearch' => '',
            'documentReviewStatus' => 'needs_attention',
            'documentReviewStage' => null,
            'documentReviewGroup' => null,
            'documentReviewFileType' => null,
            'documentReviewSort' => 'newest',
            'assignedGroupOptions' => new Collection,
            'adviserNotifications' => new Collection,
            'adviserOverviewAdvisees' => new Collection,
            'pendingConsultationsCount' => 0,
            'pendingDocReviewsCount' => 0,
            'pendingAdviserRequestsCount' => 0,
            'adviserProgressGroups' => new Collection,
        ];
    }
}
