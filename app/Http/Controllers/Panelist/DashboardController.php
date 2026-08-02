<?php

namespace App\Http\Controllers\Panelist;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $officialForms = config('official-forms.panelist', []);
        $assignedPhases = array_flip(array_unique(array_column($officialForms, 'phase')));

        return view('pages.panelist-dashboard', [
            'area' => 'Panelist',
            'panelist' => $request->user(),
            'officialFormPhases' => array_intersect_key(
                config('official-forms.phases', []),
                $assignedPhases,
            ),
            'officialForms' => $officialForms,
        ]);
    }
}
