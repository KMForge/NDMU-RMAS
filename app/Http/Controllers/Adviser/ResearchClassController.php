<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Http\Requests\Classes\CreateResearchClassRequest;
use App\Models\ResearchClass;
use App\Modules\Classes\Actions\CreateResearchClass;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResearchClassController extends Controller
{
    public function show(Request $request, ResearchClass $researchClass): View
    {
        Gate::authorize('view', $researchClass);

        $search = Str::limit(trim((string) $request->query('q')), 100, '');
        $enrollmentQuery = $researchClass->enrollments()
            ->with('student:id,name,email')
            ->latest('joined_at');

        if ($search !== '') {
            $escapedSearch = addcslashes($search, '%_\\');

            $enrollmentQuery->whereHas('student', function ($query) use ($escapedSearch): void {
                $query->where(function ($studentQuery) use ($escapedSearch): void {
                    $studentQuery
                        ->where('name', 'like', "%{$escapedSearch}%")
                        ->orWhere('email', 'like', "%{$escapedSearch}%");
                });
            });
        }

        $enrollments = $enrollmentQuery->paginate(20)->withQueryString();
        $studentNumbers = collect();

        if (Schema::hasTable('student_profiles')) {
            $studentNumbers = DB::table('student_profiles')
                ->whereIn('user_id', $enrollments->getCollection()->pluck('student_id'))
                ->pluck('student_number', 'user_id');
        }

        return view('pages.adviser-class-details', [
            'adviser' => $request->user(),
            'researchClass' => $researchClass,
            'enrollments' => $enrollments,
            'studentNumbers' => $studentNumbers,
            'search' => $search,
            'activeStudents' => $researchClass->enrollments()->where('status', 'active')->count(),
        ]);
    }

    public function store(
        CreateResearchClassRequest $request,
        CreateResearchClass $createResearchClass,
    ): JsonResponse|RedirectResponse {
        try {
            $researchClass = $createResearchClass->handle(
                $request->user(),
                $request->string('creation_token')->toString(),
                $request->string('name')->toString(),
                $request->input('description'),
                $request->integer('max_students'),
            );
        } catch (DuplicateClassOperation $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 409);
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 422);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Class created successfully.',
                'class' => [
                    'id' => $researchClass->getKey(),
                    'name' => $researchClass->name,
                    'join_code' => $researchClass->revealJoinCode(),
                    'max_students' => $researchClass->max_students,
                    'is_active' => $researchClass->is_active,
                ],
            ], 201);
        }

        return to_route('adviser.dashboard', ['tab' => 'classes'])
            ->with('class_success', 'Class created successfully. Share its join code only with your students.');
    }

    private function errorResponse(
        CreateResearchClassRequest $request,
        string $message,
        int $status,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return to_route('adviser.dashboard', ['tab' => 'classes'])
            ->withErrors(['class' => $message]);
    }
}
