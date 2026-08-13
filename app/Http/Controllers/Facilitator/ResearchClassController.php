<?php

namespace App\Http\Controllers\Facilitator;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Classes\CreateResearchClassRequest;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Modules\Classes\Actions\CreateResearchClass;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
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

        $activeGroups = ResearchClassGroup::query()
            ->where('research_class_id', $researchClass->getKey())
            ->where('status', 'active')
            ->with([
                'adviser:id,name,email,department',
                'members' => fn ($query) => $query->with('student:id,name,email,student_id,program,year_level'),
                'adviserRequests' => fn ($query) => $query->where('status', 'pending')->with('adviser:id,name,email'),
            ])
            ->latest()
            ->get();

        $groupedEnrollmentIds = ResearchClassGroupMember::query()
            ->where('research_class_id', $researchClass->getKey())
            ->whereHas('group', fn ($query) => $query->where('status', 'active'))
            ->pluck('research_class_enrollment_id');

        $unassignedStudents = ResearchClassEnrollment::query()
            ->where('research_class_id', $researchClass->getKey())
            ->where('status', 'active')
            ->whereNotIn('id', $groupedEnrollmentIds)
            ->with('student:id,name,email,student_id,program,year_level')
            ->latest('joined_at')
            ->get();

        $advisers = User::query()
            ->permission('classes.serve-as-adviser')
            ->where('status', AccountStatus::Active)
            ->whereNotNull('approved_at')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'department']);

        $researchClass->load([
            'officialFormActorAssignments' => fn ($query) => $query
                ->where('status', 'active')
                ->with(['user:id,name,email', 'assigner:id,name,email'])
                ->orderBy('actor_type'),
        ]);

        $classActorCandidates = collect([
            'research_instructor' => ['forms.res-041.fill', 'forms.res-041.endorse'],
            'program_coordinator' => ['forms.res-041.receive'],
            'dean' => ['forms.res-047.approve'],
        ])->map(fn (array $permissions) => User::query()
            ->where('user_type', 'faculty')
            ->where('status', AccountStatus::Active)
            ->whereNotNull('approved_at')
            ->permission($permissions)
            ->orderBy('name')
            ->get(['id', 'name', 'email']));

        if (! $request->expectsJson()) {
            $enrollmentQuery = $researchClass->enrollments()
                ->with(['student:id,name,email,student_id,program,year_level'])
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
                'groups' => $activeGroups,
                'unassignedStudents' => $unassignedStudents,
                'classAdviserOptions' => $advisers,
                'classActorCandidates' => $classActorCandidates,
                'search' => $search,
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
                'groups' => $activeGroups,
                'unassigned_students' => $unassignedStudents,
            ],
        ]);
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
