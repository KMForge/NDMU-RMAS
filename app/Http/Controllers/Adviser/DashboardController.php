<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Modules\Consultations\Queries\GetAdviserConsultationData;
use App\Modules\Documents\Queries\GetAdviserDocumentReviewData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        GetAdviserConsultationData $getConsultationData,
        GetAdviserDocumentReviewData $getDocumentReviewData,
    ): View {
        $classes = ResearchClass::query()
            ->where('adviser_id', $request->user()->getKey())
            ->withCount([
                'enrollments as active_students_count' => fn ($query) => $query->where('status', 'active'),
                'enrollments as pending_join_requests_count' => fn ($query) => $query->where('status', 'pending'),
            ])
            ->latest()
            ->get();

        $joinRequestScope = ResearchClassEnrollment::query()
            ->whereHas(
                'researchClass',
                fn ($query) => $query->where('adviser_id', $request->user()->getKey()),
            );

        $statusCounts = (clone $joinRequestScope)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $requestStats = [
            'pending' => (int) $statusCounts->get('pending', 0),
            'approved' => (int) $statusCounts->get('active', 0),
            'rejected' => (int) $statusCounts->get('rejected', 0),
            'total' => (int) $statusCounts->sum(),
        ];

        $requestSearch = Str::limit(trim((string) $request->query('request_q')), 100, '');
        $allowedStatuses = ['pending', 'active', 'rejected', 'all'];
        $requestStatus = in_array($request->query('request_status'), $allowedStatuses, true)
            ? $request->query('request_status')
            : 'pending';

        $joinRequests = (clone $joinRequestScope)
            ->with([
                'researchClass:id,adviser_id,name',
                'student:id,name,email,student_id,program,year_level',
            ])
            ->when(
                $requestStatus !== 'all',
                fn ($query) => $query->where('status', $requestStatus),
            )
            ->when($requestSearch !== '', function ($query) use ($requestSearch): void {
                $searchPattern = '%'.Str::lower($requestSearch).'%';

                $query->where(function ($requestQuery) use ($searchPattern): void {
                    $requestQuery
                        ->whereHas('student', function ($studentQuery) use ($searchPattern): void {
                            $studentQuery->where(function ($identityQuery) use ($searchPattern): void {
                                $identityQuery
                                    ->whereRaw('LOWER(name) LIKE ?', [$searchPattern])
                                    ->orWhereRaw('LOWER(email) LIKE ?', [$searchPattern])
                                    ->orWhereRaw('LOWER(student_id) LIKE ?', [$searchPattern]);
                            });
                        })
                        ->orWhereHas(
                            'researchClass',
                            fn ($classQuery) => $classQuery->whereRaw('LOWER(name) LIKE ?', [$searchPattern]),
                        );
                });
            })
            ->latest('requested_at')
            ->paginate(10, ['*'], 'requests_page')
            ->withQueryString();

        $consultationData = $getConsultationData->for(
            $request->user(),
            (string) $request->query('consultation_q', ''),
            (string) $request->query('consultation_status', 'pending'),
        );

        $documentReviewData = $getDocumentReviewData->for(
            $request->user(),
            (string) $request->query('document_q', ''),
            (string) $request->query('document_status', 'pending'),
            $request->integer('document_id') ?: null,
        );

        return view('pages.adviser-dashboard', [
            'area' => 'Research Adviser',
            'adviser' => $request->user(),
            'researchClasses' => $classes,
            'classJoinRequests' => $joinRequests,
            'requestStats' => $requestStats,
            'requestSearch' => $requestSearch,
            'requestStatus' => $requestStatus,
            ...$consultationData,
            ...$documentReviewData,
        ]);
    }
}
