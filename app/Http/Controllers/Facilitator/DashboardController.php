<?php

namespace App\Http\Controllers\Facilitator;

use App\Http\Controllers\Controller;
use App\Modules\Classes\Queries\GetFacilitatorClassData;
use App\Modules\Documents\Queries\GetDocumentRepositoryData;
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

        return view('pages.facilitator-dashboard', [
            'area' => 'Research Facilitator',
            'facilitator' => $request->user(),
            'officialFormPhases' => config('official-forms.phases', []),
            'officialForms' => config('official-forms.facilitator', []),
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
