<?php

namespace App\Http\Controllers\Adviser;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OfficialFormWorkspaceController;
use App\Models\ConsultationRequest;
use App\Models\Document;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserRequest;
use App\Models\RevisionRequest;
use App\Modules\Consultations\Queries\GetAdviserConsultationData;
use App\Modules\DefenseScheduling\Queries\GetDefenseScheduleCalendar;
use App\Modules\Documents\Queries\GetAdviserDocumentReviewData;
use App\Modules\Documents\Queries\GetDocumentRepositoryData;
use App\Modules\Evaluations\Queries\GetEvaluationRoundData;
use App\Modules\Notifications\Queries\GetNotificationsForUser;
use App\Modules\OfficialForms\Services\GetPendingAcademicActionsForUser;
use App\Modules\Research\Queries\GetAdviserDashboardOverview;
use App\Modules\ResearchProgress\Queries\GetAdviserProgressData;
use App\Modules\ResearchProgress\Queries\GetResearchGroupProgress;
use App\Modules\Revisions\Queries\GetAdviserRevisionData;
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
        GetAdviserProgressData $adviserProgressData,
        GetAdviserDashboardOverview $overviewData,
        GetDefenseScheduleCalendar $defenseCalendar,
        GetNotificationsForUser $notificationQuery,
        GetAdviserRevisionData $revisionData,
    ): View {
        $allowedTabs = [
            'dashboard',
            'classes',
            'researchers',
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
        $tabParam = (string) $request->query('tab');
        if ($tabParam === 'proposal') {
            $tabParam = 'docreview';
        }
        $activeTab = in_array($tabParam, $allowedTabs, true)
            ? $tabParam
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
            ->where('is_current', true)
            ->whereIn('status', [
                DocumentStatus::Pending->value,
                DocumentStatus::Submitted->value,
                DocumentStatus::UnderReview->value,
            ])
            ->count();
        $pendingRevisionsCount = Schema::hasTable('revision_requests')
            ? RevisionRequest::query()
                ->whereHas('researchClassGroup', fn ($group) => $group
                    ->where('adviser_id', $user->getKey())
                    ->where('status', 'active')
                    ->whereNull('disbanded_at'))
                ->where('status', 'submitted')
                ->count()
            : 0;

        $viewData['pendingAdviserRequests'] = $pendingAdviserRequests;
        $viewData['pendingAdviserRequestsCount'] = $pendingAdviserRequests->count();
        $viewData['pendingConsultationsCount'] = $pendingConsultationsCount;
        $viewData['pendingDocReviewsCount'] = $pendingDocReviewsCount;
        $viewData['pendingFormInstances'] = app(OfficialFormWorkspaceController::class)->pendingInstances($request);
        $viewData['pendingAcademicActions'] = app(GetPendingAcademicActionsForUser::class)->execute($user);
        $viewData['sidebarBadges'] = [
            'classes' => $pendingAdviserRequests->count(),
            'docreview' => $pendingDocReviewsCount,
            'consultation' => $pendingConsultationsCount,
            'revisions' => $pendingRevisionsCount,
            'forms' => $viewData['pendingFormInstances']->count(),
            'notifications' => Schema::hasTable('notifications')
                ? $user->unreadNotifications()->count()
                : 0,
        ];
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
                (string) $request->query('document_status', 'all'),
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
                (string) $request->query('consultation_status', 'all'),
            )];
        }

        if ($activeTab === 'monitoring') {
            $viewData = [...$viewData, ...$adviserProgressData->for(
                $user,
                $request->query('progress_search'),
                $request->query('progress_group_status'),
                $request->query('progress_page'),
                $request->query('progress_group_id'),
            )];
        }

        if ($activeTab === 'revisions') {
            $viewData = [...$viewData, ...$revisionData->for(
                $user,
                $request->query('revision_search'),
                $request->query('revision_status'),
            )];
        }

        if ($activeTab === 'notifications') {
            $viewData['userNotifications'] = $notificationQuery->execute($user, (string) $request->query('notification_filter', 'all'));
            $viewData['userUnreadCount'] = $user->unreadNotifications()->count();
            $viewData['notificationFilter'] = (string) $request->query('notification_filter', 'all');
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
            'consultationStatus' => 'all',
            'reviewDocuments' => new LengthAwarePaginator([], 0, 8),
            'selectedReviewDocument' => null,
            'documentReviewComments' => new Collection,
            'documentReviewStats' => ['approved' => 0, 'revisions' => 0, 'comments' => 0, 'critical' => 0],
            'documentReviewSearch' => '',
            'documentReviewStatus' => 'all',
            'documentReviewStage' => null,
            'documentReviewGroup' => null,
            'documentReviewFileType' => null,
            'documentReviewSort' => 'newest',
            'assignedGroupOptions' => new Collection,
            'adviserNotifications' => new Collection,
            'userNotifications' => new LengthAwarePaginator([], 0, 20),
            'userUnreadCount' => 0,
            'notificationFilter' => 'all',
            'adviserOverviewAdvisees' => new Collection,
            'pendingConsultationsCount' => 0,
            'pendingDocReviewsCount' => 0,
            'pendingAdviserRequestsCount' => 0,
            'adviserProgressGroups' => new Collection,
            'revisionRequests' => new LengthAwarePaginator([], 0, 10),
            'revisionStats' => [
                'open' => 0,
                'in_progress' => 0,
                'submitted' => 0,
                'resolved' => 0,
                'total' => 0,
            ],
            'revisionSearch' => '',
            'revisionStatus' => 'all',
        ];
    }
}
