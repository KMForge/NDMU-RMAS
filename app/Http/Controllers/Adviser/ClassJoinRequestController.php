<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Modules\Classes\Actions\ReviewResearchClassJoinRequest;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClassJoinRequestController extends Controller
{
    public function approve(
        Request $request,
        ResearchClass $researchClass,
        ResearchClassEnrollment $joinRequest,
        ReviewResearchClassJoinRequest $reviewJoinRequest,
    ): JsonResponse|RedirectResponse {
        Gate::authorize('manageJoinRequests', $researchClass);

        try {
            $enrollment = $reviewJoinRequest->approve(
                $request->user(),
                $researchClass,
                $joinRequest,
            );
        } catch (DuplicateClassOperation $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 409);
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 422);
        }

        return $this->successResponse(
            $request,
            $enrollment,
            'Join request approved. The student is now enrolled.',
        );
    }

    public function reject(
        Request $request,
        ResearchClass $researchClass,
        ResearchClassEnrollment $joinRequest,
        ReviewResearchClassJoinRequest $reviewJoinRequest,
    ): JsonResponse|RedirectResponse {
        Gate::authorize('manageJoinRequests', $researchClass);

        try {
            $enrollment = $reviewJoinRequest->reject(
                $request->user(),
                $researchClass,
                $joinRequest,
            );
        } catch (DuplicateClassOperation $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 409);
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 422);
        }

        return $this->successResponse(
            $request,
            $enrollment,
            'Join request rejected.',
        );
    }

    private function successResponse(
        Request $request,
        ResearchClassEnrollment $enrollment,
        string $message,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'join_request' => [
                    'id' => $enrollment->getKey(),
                    'status' => $enrollment->status,
                    'reviewed_at' => $enrollment->reviewed_at?->toIso8601String(),
                ],
            ]);
        }

        return to_route('adviser.dashboard', ['tab' => 'requests'])
            ->with('class_success', $message);
    }

    private function errorResponse(
        Request $request,
        string $message,
        int $status,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return to_route('adviser.dashboard', ['tab' => 'requests'])
            ->withErrors(['class' => $message]);
    }
}
