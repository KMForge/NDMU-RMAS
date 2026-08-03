<?php

namespace App\Http\Controllers\Facilitator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Classes\AssignResearchClassGroupAdviserRequest;
use App\Http\Requests\Classes\CreateResearchClassGroupRequest;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\Classes\Actions\AssignAdviserToResearchClassGroup;
use App\Modules\Classes\Actions\AssignStudentToResearchClassGroup;
use App\Modules\Classes\Actions\CreateResearchClassGroup;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ResearchClassGroupController extends Controller
{
    public function store(
        CreateResearchClassGroupRequest $request,
        ResearchClass $researchClass,
        CreateResearchClassGroup $createGroup,
    ): JsonResponse|RedirectResponse {
        Gate::authorize('view', $researchClass);

        try {
            $group = $createGroup->handle(
                $request->user(),
                $researchClass,
                $request->string('creation_token')->toString(),
                $request->string('name')->toString(),
            );
        } catch (DuplicateClassOperation $exception) {
            return $this->errorResponse($request, $researchClass, $exception->getMessage(), 409);
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $researchClass, $exception->getMessage(), 422);
        }

        if (! $request->expectsJson()) {
            return to_route('facilitator.classes.show', $researchClass)
                ->with('group_success', 'Research group created successfully.');
        }

        return response()->json([
            'message' => 'Research group created successfully.',
            'group' => ['id' => $group->getKey(), 'name' => $group->name],
        ], 201);
    }

    public function assignStudent(
        Request $request,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        ResearchClassEnrollment $enrollment,
        AssignStudentToResearchClassGroup $assignStudent,
    ): JsonResponse|RedirectResponse {
        Gate::authorize('view', $researchClass);
        abort_unless($request->user()->can('classes.manage-groups'), 403);

        try {
            $member = $assignStudent->handle(
                $request->user(),
                $researchClass,
                $group,
                $enrollment,
            );
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $researchClass, $exception->getMessage(), 422);
        }

        if (! $request->expectsJson()) {
            return to_route('facilitator.classes.show', $researchClass)
                ->with('group_success', 'Student assigned to the research group successfully.');
        }

        return response()->json([
            'message' => 'Student assigned to the research group successfully.',
            'membership' => [
                'id' => $member->getKey(),
                'group_id' => $member->research_class_group_id,
                'student_id' => $member->student_id,
            ],
        ]);
    }

    public function assignAdviser(
        AssignResearchClassGroupAdviserRequest $request,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        AssignAdviserToResearchClassGroup $assignAdviser,
    ): JsonResponse|RedirectResponse {
        Gate::authorize('view', $researchClass);
        $adviser = User::query()->findOrFail($request->integer('adviser_id'));

        try {
            $updatedGroup = $assignAdviser->handle(
                $request->user(),
                $researchClass,
                $group,
                $adviser,
            );
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $researchClass, $exception->getMessage(), 422);
        }

        if (! $request->expectsJson()) {
            return to_route('facilitator.classes.show', $researchClass)
                ->with('group_success', 'Research adviser assigned successfully.');
        }

        return response()->json([
            'message' => 'Research adviser assigned successfully.',
            'group' => [
                'id' => $updatedGroup->getKey(),
                'adviser' => ['id' => $adviser->getKey(), 'name' => $adviser->name],
            ],
        ]);
    }

    private function errorResponse(
        Request $request,
        ResearchClass $researchClass,
        string $message,
        int $status,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return to_route('facilitator.classes.show', $researchClass)
            ->withErrors(['group' => $message]);
    }
}
