<?php

namespace App\Http\Controllers\Facilitator;

use App\Http\Controllers\Controller;
use App\Modules\Classes\Queries\GetFacilitatorClassData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GetFacilitatorClassData $classData): View
    {
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
        ]);
    }
}
