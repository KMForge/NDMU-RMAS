<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\ResearchClass;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $classes = ResearchClass::query()
            ->where('adviser_id', $request->user()->getKey())
            ->withCount([
                'enrollments as active_students_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->latest()
            ->get();

        return view('pages.adviser-dashboard', [
            'area' => 'Research Adviser',
            'adviser' => $request->user(),
            'researchClasses' => $classes,
        ]);
    }
}
