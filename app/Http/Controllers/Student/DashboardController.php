<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Modules\Research\Queries\GetStudentDashboardData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GetStudentDashboardData $dashboardData): View
    {
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
