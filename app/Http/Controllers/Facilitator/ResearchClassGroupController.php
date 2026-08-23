<?php

namespace App\Http\Controllers\Facilitator;

use App\Http\Controllers\Controller;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserRequest;
use App\Models\User;
use App\Modules\Classes\Actions\AssignResearchClassGroupLeader;
use App\Modules\Classes\Actions\AssignStudentToResearchClassGroup;
use App\Modules\Classes\Actions\BulkAssignStudentsToResearchClassGroup;
use App\Modules\Classes\Actions\CancelResearchClassGroupAdviserRequest;
use App\Modules\Classes\Actions\CreateResearchClassGroup;
use App\Modules\Classes\Actions\DisbandResearchClassGroup;
use App\Modules\Classes\Actions\RemoveResearchClassGroupAdviser;
use App\Modules\Classes\Actions\RenameResearchClassGroup;
use App\Modules\Classes\Actions\RequestAdviserForResearchClassGroup;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResearchClassGroupController extends Controller
{
    public function store(
        Request $request,
        ResearchClass $researchClass,
        CreateResearchClassGroup $action,
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'creation_token' => ['required', 'uuid'],
            'name' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        try {
            $group = $action->handle(
                $request->user(),
                $researchClass,
                $validated['creation_token'],
                $validated['name'],
            );
        } catch (DuplicateClassOperation $exception) {
            return $this->errorResponse($request, $researchClass, $exception->getMessage(), 409);
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $researchClass, $exception->getMessage(), 422);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Group created successfully.',
                'group' => [
                    'id' => $group->getKey(),
                    'name' => $group->name,
                    'research_class_id' => $group->research_class_id,
                ],
            ], 201);
        }

        return to_route('facilitator.classes.show', $researchClass)
            ->with('class_success', 'Research group created successfully.');
    }

    public function assignStudent(
        Request $request,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        ResearchClassEnrollment $enrollment,
        AssignStudentToResearchClassGroup $action,
    ): JsonResponse|RedirectResponse {
        $targetGroup = $group;

        if ($request->filled('group_id')) {
            $targetGroup = ResearchClassGroup::query()
                ->where('research_class_id', $researchClass->getKey())
                ->where('status', 'active')
                ->find($request->integer('group_id')) ?? $group;
        }

        try {
            $member = $action->handle($request->user(), $researchClass, $targetGroup, $enrollment);
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $researchClass, $exception->getMessage(), 422);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Student assigned to group successfully.',
                'membership' => [
                    'id' => $member->getKey(),
                    'group_id' => $member->research_class_group_id,
                    'student_id' => $member->student_id,
                ],
            ]);
        }

        return to_route('facilitator.classes.show', $researchClass)
            ->with('class_success', 'Student assigned to group successfully.');
    }

    public function bulkAssignStudents(
        Request $request,
        ResearchClass $researchClass,
        BulkAssignStudentsToResearchClassGroup $action,
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'group_id' => ['required', 'integer', 'exists:research_class_groups,id'],
            'enrollment_ids' => ['required', 'array', 'min:1'],
            'enrollment_ids.*' => ['required', 'integer', 'exists:research_class_enrollments,id'],
        ]);

        try {
            $members = $action->handle(
                $request->user(),
                $researchClass,
                (int) $validated['group_id'],
                $validated['enrollment_ids'],
            );
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $researchClass, $exception->getMessage(), 422);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => count($members).' student(s) assigned to group successfully.',
                'assigned_count' => count($members),
            ]);
        }

        return to_route('facilitator.classes.show', $researchClass)
            ->with('class_success', count($members).' student(s) assigned to group successfully.');
    }

    public function rename(
        Request $request,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        RenameResearchClassGroup $action,
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        try {
            $group = $action->handle($request->user(), $researchClass, $group, $validated['name']);
        } catch (DuplicateClassOperation $exception) {
            return $this->errorResponse($request, $researchClass, $exception->getMessage(), 409);
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $researchClass, $exception->getMessage(), 422);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Group renamed successfully.',
                'group' => ['id' => $group->getKey(), 'name' => $group->name],
            ]);
        }

        return to_route('facilitator.classes.show', $researchClass)
            ->with('class_success', 'Group renamed successfully.');
    }

    public function disband(
        Request $request,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        DisbandResearchClassGroup $action,
    ): JsonResponse|RedirectResponse {
        try {
            $action->handle($request->user(), $researchClass, $group);
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $researchClass, $exception->getMessage(), 422);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Group disbanded successfully.']);
        }

        return to_route('facilitator.classes.show', $researchClass)
            ->with('class_success', 'Group disbanded. Members returned to Unassigned Students list.');
    }

    public function requestAdviser(
        Request $request,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        RequestAdviserForResearchClassGroup $action,
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'adviser_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $adviser = User::query()->findOrFail($validated['adviser_id']);

        try {
            $adviserRequest = $action->handle($request->user(), $researchClass, $group, $adviser);
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $researchClass, $exception->getMessage(), 422);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Adviser request sent successfully.',
                'adviser_request' => [
                    'id' => $adviserRequest->getKey(),
                    'adviser_id' => $adviserRequest->adviser_id,
                    'status' => $adviserRequest->status,
                ],
                'group' => [
                    'id' => $group->getKey(),
                    'adviser' => null,
                ],
            ]);
        }

        return to_route('facilitator.classes.show', $researchClass)
            ->with('class_success', 'Adviser request sent successfully.');
    }

    public function cancelAdviserRequest(
        Request $request,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        ResearchClassGroupAdviserRequest $adviserRequest,
        CancelResearchClassGroupAdviserRequest $action,
    ): JsonResponse|RedirectResponse {
        try {
            $action->handle($request->user(), $researchClass, $group, $adviserRequest);
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $researchClass, $exception->getMessage(), 422);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Adviser request cancelled successfully.']);
        }

        return to_route('facilitator.classes.show', $researchClass)
            ->with('class_success', 'Adviser request cancelled successfully.');
    }

    public function removeAdviser(
        Request $request,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        RemoveResearchClassGroupAdviser $action,
    ): JsonResponse|RedirectResponse {
        try {
            $action->handle($request->user(), $researchClass, $group);
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $researchClass, $exception->getMessage(), 422);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Adviser removed successfully.']);
        }

        return to_route('facilitator.classes.show', $researchClass)
            ->with('class_success', 'Adviser removed from group successfully.');
    }

    public function assignLeader(
        Request $request,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        AssignResearchClassGroupLeader $action,
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $student = User::query()->findOrFail($validated['student_id']);

        try {
            $group = $action->handle($request->user(), $researchClass, $group, $student);
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $researchClass, $exception->getMessage(), 422);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Group Leader assigned successfully.',
                'group' => [
                    'id' => $group->getKey(),
                    'leader_student_id' => $group->leader_student_id,
                ],
            ]);
        }

        return to_route('facilitator.classes.show', $researchClass)
            ->with('class_success', 'Group Leader assigned successfully.');
    }

    private function errorResponse(Request $request, ResearchClass $researchClass, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return to_route('facilitator.classes.show', $researchClass)
            ->withErrors(['group' => $message]);
    }
}
