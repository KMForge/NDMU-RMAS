<?php

namespace App\Http\Controllers\Facilitator;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OfficialFormWorkspaceController;
use App\Models\DefenseRoom;
use App\Modules\Classes\Queries\GetFacilitatorClassData;
use App\Modules\DefenseScheduling\Queries\GetDefenseScheduleCalendar;
use App\Modules\Documents\Queries\GetDocumentRepositoryData;
use App\Modules\Evaluations\Queries\GetEvaluationRoundData;
use App\Modules\ResearchProgress\Queries\GetFacilitatorProgressData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        GetFacilitatorClassData $classData,
        GetDocumentRepositoryData $repositoryData,
        GetFacilitatorProgressData $progressData,
        GetDefenseScheduleCalendar $defenseCalendar,
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
        $defenseRooms = DefenseRoom::where('is_active', true)->get();
        $evalQuery = app(GetEvaluationRoundData::class);
        $evalData = $evalQuery->forFacilitator($request->user());

        return view('pages.facilitator-dashboard', [
            'area' => 'Research Facilitator',
            'facilitator' => $request->user(),
            'pendingFormInstances' => app(OfficialFormWorkspaceController::class)->pendingInstances($request),
            'officialFormPhases' => config('official-forms.phases', []),
            'officialForms' => collect(config('official-forms.facilitator', []))
                ->filter(fn (array $form, string $code) => $request->user()->getAllPermissions()
                    ->contains(fn ($permission) => str_starts_with($permission->name, 'forms.'.strtolower($code).'.')))
                ->all(),
            'defenses' => $defenses,
            'defenseRooms' => $defenseRooms,
            'evaluationRounds' => $evalData['rounds'] ?? [],
            ...$classData->for(
                $request->user(),
                $request->query('request_q'),
                $request->query('request_status'),
            ),
            ...$repository,
            ...$progress,
        ]);
    }
}
