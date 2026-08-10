<?php

namespace App\Http\Controllers\Facilitator;

use App\Http\Controllers\Controller;
use App\Modules\Classes\Queries\GetFacilitatorClassData;
use App\Modules\Documents\Queries\GetDocumentRepositoryData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GetFacilitatorClassData $classData, GetDocumentRepositoryData $repositoryData): View
    {
        $repository = $request->query('tab') === 'repository'
            ? $repositoryData->for($request->user(), $request->query())
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
        ]);
    }
}
