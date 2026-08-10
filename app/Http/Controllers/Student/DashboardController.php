<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Modules\Consultations\Queries\GetStudentConsultationData;
use App\Modules\Documents\Queries\GetDocumentRepositoryData;
use App\Modules\Research\Queries\GetStudentDashboardData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        GetStudentDashboardData $dashboardData,
        GetDocumentRepositoryData $repositoryData,
        GetStudentConsultationData $consultationData,
    ): View {
        $allowedTabs = [
            'dashboard',
            'classes',
            'research',
            'proposal',
            'progress',
            'consultation',
            'revisions',
            'defense',
            'evaluations',
            'repository',
            'forms',
            'notifications',
            'settings',
        ];
        $activeTab = in_array($request->query('tab'), $allowedTabs, true)
            ? (string) $request->query('tab')
            : 'dashboard';
        $data = $dashboardData->for(
            $request->user(),
            $request->query('dashboard_q'),
            $activeTab,
        );

        if ($activeTab === 'repository') {
            $data = [...$data, ...$repositoryData->for($request->user(), $request->query())];
            $data['documents'] = $data['repositoryDocuments'];
        }

        if ($activeTab === 'consultation') {
            $data = [...$data, ...$consultationData->for($request->user())];
        }

        $data['officialFormPhases'] = config('official-forms.phases', []);
        $data['officialForms'] = collect(config('official-forms.student', []))
            ->map(function (array $form, string $code): array {
                unset($form['file']);

                return [
                    ...$form,
                    'source_url' => route('student.official-forms.source', ['form' => $code]),
                ];
            })
            ->all();

        return view('pages.student-dashboard', [
            ...$data,
            'activeDashboardTab' => $activeTab,
        ]);
    }
}
