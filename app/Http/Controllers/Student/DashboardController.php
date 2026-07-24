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
        return view('pages.student-dashboard', $dashboardData->for(
            $request->user(),
            $request->query('dashboard_q'),
        ));
    }
}
