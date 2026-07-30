<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Modules\Consultations\Queries\GetAdviserConsultationData;
use App\Modules\Documents\Queries\GetAdviserDocumentReviewData;
use App\Modules\Research\Queries\GetAdviserDashboardOverview;
use App\Modules\Revisions\Queries\GetAdviserRevisionData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        GetAdviserConsultationData $getConsultationData,
        GetAdviserDocumentReviewData $getDocumentReviewData,
        GetAdviserRevisionData $getRevisionData,
        GetAdviserDashboardOverview $getDashboardOverview,
    ): View {
        $allowedTabs = [
            'dashboard',
            'classes',
            'requests',
            'consultation',
            'docreview',
            'revisions',
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
                ->where('adviser_id', $request->user()->getKey())
                ->withCount([
                    'enrollments as active_students_count' => fn ($query) => $query->where('status', 'active'),
                    'enrollments as pending_join_requests_count' => fn ($query) => $query->where('status', 'pending'),
                ])
                ->latest()
                ->get();
        }

        if ($activeTab === 'requests') {
            $viewData = [
                ...$viewData,
                ...$this->joinRequestData($request),
            ];
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
    private function joinRequestData(Request $request): array
    {
        $scope = ResearchClassEnrollment::query()
            ->whereHas(
                'researchClass',
                fn ($query) => $query->where('adviser_id', $request->user()->getKey()),
            );
        $statusCounts = (clone $scope)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $search = Str::limit(trim((string) $request->query('request_q')), 100, '');
        $allowedStatuses = ['pending', 'active', 'rejected', 'all'];
        $status = in_array($request->query('request_status'), $allowedStatuses, true)
            ? (string) $request->query('request_status')
            : 'pending';

        $requests = (clone $scope)
            ->with([
                'researchClass:id,adviser_id,name',
                'student:id,name,email,student_id,program,year_level',
            ])
            ->when(
                $status !== 'all',
                fn ($query) => $query->where('status', $status),
            )
            ->when($search !== '', function ($query) use ($search): void {
                $pattern = '%'.Str::lower($search).'%';

                $query->where(function ($requestQuery) use ($pattern): void {
                    $requestQuery
                        ->whereHas('student', function ($studentQuery) use ($pattern): void {
                            $studentQuery->where(function ($identityQuery) use ($pattern): void {
                                $identityQuery
                                    ->whereRaw('LOWER(name) LIKE ?', [$pattern])
                                    ->orWhereRaw('LOWER(email) LIKE ?', [$pattern])
                                    ->orWhereRaw('LOWER(student_id) LIKE ?', [$pattern]);
                            });
                        })
                        ->orWhereHas(
                            'researchClass',
                            fn ($classQuery) => $classQuery->whereRaw('LOWER(name) LIKE ?', [$pattern]),
                        );
                });
            })
            ->latest('requested_at')
            ->paginate(10, ['*'], 'requests_page')
            ->withQueryString();

        return [
            'classJoinRequests' => $requests,
            'requestStats' => [
                'pending' => (int) $statusCounts->get('pending', 0),
                'approved' => (int) $statusCounts->get('active', 0),
                'rejected' => (int) $statusCounts->get('rejected', 0),
                'total' => (int) $statusCounts->sum(),
            ],
            'requestSearch' => $search,
            'requestStatus' => $status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyViewData(): array
    {
        return [
            'researchClasses' => new Collection,
            'classJoinRequests' => new LengthAwarePaginator([], 0, 10),
            'requestStats' => ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0],
            'requestSearch' => '',
            'requestStatus' => 'pending',
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
        ];
    }
}
