<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\ResearchClass;
use App\Modules\Consultations\Queries\GetAdviserConsultationData;
use App\Modules\Documents\Queries\GetAdviserDocumentReviewData;
use App\Modules\Documents\Queries\GetAdviserRepositoryData;
use App\Modules\Research\Queries\GetAdviserDashboardOverview;
use App\Modules\Revisions\Queries\GetAdviserRevisionData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        GetAdviserConsultationData $getConsultationData,
        GetAdviserDocumentReviewData $getDocumentReviewData,
        GetAdviserRepositoryData $getRepositoryData,
        GetAdviserRevisionData $getRevisionData,
        GetAdviserDashboardOverview $getDashboardOverview,
    ): View {
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
        $viewData = $this->emptyViewData();
        $viewData = [
            ...$viewData,
            ...$getDashboardOverview->for(
                $request->user(),
                $activeTab === 'dashboard',
            ),
        ];

        if ($activeTab === 'classes') {
            $viewData['researchClasses'] = ResearchClass::query()
                ->whereHas('groups', fn ($query) => $query->where('adviser_id', $request->user()->getKey()))
                ->withCount([
                    'enrollments as active_students_count' => fn ($query) => $query->where('status', 'active'),
                    'enrollments as pending_join_requests_count' => fn ($query) => $query->where('status', 'pending'),
                ])
                ->latest()
                ->get();
        }

        if ($activeTab === 'consultation') {
            $viewData = [
                ...$viewData,
                ...$getConsultationData->for(
                    $request->user(),
                    (string) $request->query('consultation_q', ''),
                    (string) $request->query('consultation_status', 'pending'),
                ),
            ];
        }

        if ($activeTab === 'docreview') {
            $viewData = [
                ...$viewData,
                ...$getDocumentReviewData->for(
                    $request->user(),
                    (string) $request->query('document_q', ''),
                    (string) $request->query('document_status', 'pending'),
                    $request->integer('document_id') ?: null,
                ),
            ];
        }

        if ($activeTab === 'revisions') {
            $viewData = [
                ...$viewData,
                ...$getRevisionData->for(
                    $request->user(),
                    $request->query('revision_q'),
                    $request->query('revision_status'),
                ),
            ];
        }

        if ($activeTab === 'repository') {
            $viewData = [
                ...$viewData,
                ...$getRepositoryData->for(
                    $request->user(),
                    (string) $request->query('repository_q', ''),
                    (string) $request->query('repository_status', 'all'),
                ),
            ];
        }

        $viewData['officialFormPhases'] = config('official-forms.phases', []);
        $viewData['officialForms'] = config('official-forms.adviser', []);

        return view('pages.adviser-dashboard', [
            'area' => 'Research Adviser',
            'adviser' => $request->user(),
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
        ];
    }
}
