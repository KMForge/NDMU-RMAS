<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Classes\JoinResearchClassRequest;
use App\Modules\Classes\Actions\JoinResearchClass;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ResearchClassController extends Controller
{
    public function store(
        JoinResearchClassRequest $request,
        JoinResearchClass $joinResearchClass,
    ): JsonResponse|RedirectResponse {
        try {
            $enrollment = $joinResearchClass->handle(
                $request->user(),
                $request->string('join_code')->toString(),
            );
        } catch (DuplicateClassOperation $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 409);
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 422);
        }

        $researchClass = $enrollment->researchClass()->with('adviser:id,name')->firstOrFail();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'You joined the class successfully.',
                'class' => [
                    'id' => $researchClass->getKey(),
                    'name' => $researchClass->name,
                    'adviser' => $researchClass->adviser?->name,
                    'joined_at' => $enrollment->joined_at->toIso8601String(),
                ],
            ], 201);
        }

        return to_route('student.dashboard', ['tab' => 'classes'])
            ->with('class_success', 'You joined the class successfully.');
    }

    private function errorResponse(
        JoinResearchClassRequest $request,
        string $message,
        int $status,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return to_route('student.dashboard', ['tab' => 'classes'])
            ->withErrors(['class' => $message]);
    }
}
