<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $allowedTabs = [
            'dashboard',
            'classes',
            'consultation',
            'docreview',
            'revisions',
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
                'group' => fn ($query) => $query->where('status', 'active')->with('researchClass:id,name'),
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

        $viewData['pendingAdviserRequests'] = $pendingAdviserRequests;
        $viewData['assignedGroups'] = $assignedGroups;
        $viewData['officialFormPhases'] = config('official-forms.phases', []);
        $viewData['officialForms'] = config('official-forms.adviser', []);

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
            'documentReviewStatus' => 'pending',
            'revisionRequests' => new LengthAwarePaginator([], 0, 10),
            'revisionStats' => [
                'open' => 0,
                'in_progress' => 0,
                'submitted' => 0,
                'resolved' => 0,
                'total' => 0,
            ],
            'revisionSearch' => '',
            'revisionStatus' => 'submitted',
            'repositoryDocuments' => new LengthAwarePaginator([], 0, 9),
            'repositoryStats' => ['total' => 0, 'approved' => 0, 'pending' => 0, 'evaluation' => 0],
            'repositorySearch' => '',
            'repositoryStatus' => 'all',
            'adviserOverviewStats' => [
                'active_advisees' => 0,
                'nearing_defense' => 0,
                'urgent_reviews' => 0,
                'overdue_revisions' => 0,
                'today_consultations' => 0,
                'next_consultation_at' => null,
                'completed_research' => 0,
            ],
            'adviserOverviewAdvisees' => new Collection,
            'adviserPendingDocuments' => new Collection,
            'adviserTodayConsultations' => new Collection,
            'adviserRecentActivity' => new Collection,
            'adviserNotifications' => new Collection,
            'adviserNotificationStats' => [
                'total' => 0,
                'unread' => 0,
                'approvals' => 0,
                'defense' => 0,
                'documents' => 0,
                'system' => 0,
            ],
        ];
    }
}
