<?php

namespace App\Http\Controllers\Facilitator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Classes\CreateResearchClassRequest;
use App\Models\ResearchClass;
use App\Models\User;
use App\Modules\Classes\Actions\CreateResearchClass;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResearchClassController extends Controller
{
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

        return to_route('facilitator.dashboard', ['tab' => 'classes'])
            ->with('class_success', 'Class created successfully. Share its join code only with your students.');
    }

    public function show(Request $request, ResearchClass $researchClass): JsonResponse|View
    {
        Gate::authorize('view', $researchClass);
        $search = Str::limit(trim((string) $request->query('q', '')), 100, '');

        if (! $request->expectsJson()) {
            $enrollmentQuery = $researchClass->enrollments()
                ->with(['student:id,name,email,student_id,program,year_level', 'groupMembership.group:id,name'])
                ->where('status', 'active')
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

            $enrollments = $enrollmentQuery->paginate(30)->withQueryString();

            return view('pages.facilitator-class-details', [
                'facilitator' => $request->user(),
                'researchClass' => $researchClass,
                'enrollments' => $enrollments,
                'activeStudents' => $researchClass->enrollments()->where('status', 'active')->count(),
                'search' => $search,
                'groups' => $researchClass->groups()
                    ->with(['adviser:id,name,email', 'members.student:id,name,email,student_id'])
                    ->withCount('members')
                    ->orderBy('name')
                    ->get(),
                'availableAdvisers' => $this->availableAdvisers(),
            ]);
        }

        $researchClass->load([
            'facilitator:id,name,email',
            'enrollments' => fn ($query) => $query
                ->where('status', 'active')
                ->with('student:id,name,email,student_id,program,year_level')
                ->when($search !== '', fn ($enrollmentQuery) => $enrollmentQuery
                    ->whereHas('student', fn ($studentQuery) => $studentQuery
                        ->where('name', 'like', '%'.addcslashes($search, '%_\\').'%')
                        ->orWhere('email', 'like', '%'.addcslashes($search, '%_\\').'%')))
                ->orderBy('joined_at'),
            'groups.adviser:id,name,email',
            'groups.members.student:id,name,email,student_id,program,year_level',
        ]);

        return response()->json([
            'class' => [
                'id' => $researchClass->getKey(),
                'name' => $researchClass->name,
                'description' => $researchClass->description,
                'join_code' => $researchClass->revealJoinCode(),
                'max_students' => $researchClass->max_students,
                'facilitator' => $researchClass->facilitator,
                'students' => $researchClass->enrollments->map(fn ($enrollment): array => [
                    'enrollment_id' => $enrollment->getKey(),
                    'joined_at' => $enrollment->joined_at?->toIso8601String(),
                    'student' => $enrollment->student,
                ]),
                'groups' => $researchClass->groups->map(fn ($group): array => [
                    'id' => $group->getKey(),
                    'name' => $group->name,
                    'adviser' => $group->adviser,
                    'members' => $group->members->map(fn ($member) => $member->student),
                ]),
            ],
            'available_advisers' => $this->availableAdvisers(),
        ]);
    }

    /** @return Collection<int, User> */
    private function availableAdvisers(): Collection
    {
        return User::query()
            ->role('research-adviser')
            ->where('status', 'active')
            ->whereNotNull('approved_at')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function errorResponse(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return to_route('facilitator.dashboard', ['tab' => 'classes'])
            ->withErrors(['class' => $message]);
    }
}
