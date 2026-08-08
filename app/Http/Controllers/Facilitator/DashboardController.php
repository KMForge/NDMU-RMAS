<?php

namespace App\Http\Controllers\Facilitator;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('pages.facilitator-dashboard', [
            'area' => 'Research Facilitator',
            'facilitator' => $request->user(),
            'officialFormPhases' => config('official-forms.phases', []),
            'officialForms' => config('official-forms.facilitator', []),
            'researchClasses' => new Collection,
            'classJoinRequests' => new Collection,
            'classRequestStats' => [
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'total' => 0,
            ],
            'requestStats' => [
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'total' => 0,
            ],
            'requestSearch' => '',
            'requestStatus' => 'all',
            'classAdviserOptions' => new Collection,
        ]);
    }
}
