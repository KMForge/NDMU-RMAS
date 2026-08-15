<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ConsultationRequest;
use App\Modules\Consultations\Queries\GetStudentConsultationData;
use App\Modules\DefenseScheduling\Queries\GetDefenseScheduleCalendar;
use App\Modules\Documents\Queries\GetDocumentRepositoryData;
use App\Modules\Evaluations\Queries\GetEvaluationRoundData;
use App\Modules\Research\Queries\GetStudentDashboardData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        GetStudentDashboardData $dashboardData,
        GetDocumentRepositoryData $repositoryData,
        GetStudentConsultationData $consultationData,
        GetDefenseScheduleCalendar $defenseCalendar,
        GetEvaluationRoundData $evaluationQuery,
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

        $pendingConsultationsCount = Schema::hasTable('consultation_requests')
            ? ConsultationRequest::query()
                ->where('requested_by', $request->user()->getKey())
                ->whereIn('status', ['pending', 'reschedule_proposed'])
                ->count()
            : 0;

        $data['pendingConsultationsCount'] = $pendingConsultationsCount;
        $data['defenses'] = $defenseCalendar->execute($request->user());
        $evaluationData = $evaluationQuery->forStudent($request->user());
        $data['releasedEvaluations'] = $evaluationData['rounds'] ?? [];
        $data['officialFormPhases'] = config('official-forms.phases', []);
        $data['officialForms'] = collect(config('official-forms.student', []))
            ->filter(fn (array $form, string $code) => $request->user()->getAllPermissions()
                ->contains(fn ($permission) => str_starts_with($permission->name, 'forms.'.strtolower($code).'.')))
            ->map(function (array $form, string $code): array {
                unset($form['file']);

                return [
                    ...$form,
                    'source_url' => route('student.official-forms.source', ['form' => $code]),
                ];
            })
            ->all();

        return view('pages.student-dashboard', [
            'area' => 'Student Portal',
            'student' => $request->user(),
            'activeDashboardTab' => $activeTab,
            ...$data,
        ]);
    }
}
