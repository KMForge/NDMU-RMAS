<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Classes\JoinResearchClassRequest;
use App\Modules\Classes\Actions\RequestToJoinResearchClass;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ResearchClassController extends Controller
{
    public function store(
        JoinResearchClassRequest $request,
        RequestToJoinResearchClass $requestToJoinResearchClass,
    ): JsonResponse|RedirectResponse {
        try {
            $joinRequest = $requestToJoinResearchClass->handle(
                $request->user(),
                $request->string('join_code')->toString(),
            );
        } catch (DuplicateClassOperation $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 409);
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 422);
        }

        $researchClass = $joinRequest->researchClass()->with('adviser:id,name')->firstOrFail();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your join request was submitted for adviser review.',
                'join_request' => [
                    'id' => $joinRequest->getKey(),
                    'status' => $joinRequest->status,
                    'requested_at' => $joinRequest->requested_at->toIso8601String(),
                    'class_id' => $researchClass->getKey(),
                    'class_name' => $researchClass->name,
                    'adviser_name' => $researchClass->adviser?->name,
                ],
            ], 201);
        }

        return to_route('student.dashboard', ['tab' => 'classes'])
            ->with('class_success', 'Your join request was submitted for adviser review.');
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
