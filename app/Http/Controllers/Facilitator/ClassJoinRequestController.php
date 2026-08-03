<?php

namespace App\Http\Controllers\Facilitator;

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
        ReviewResearchClassJoinRequest $review,
    ): JsonResponse|RedirectResponse {
        return $this->review($request, $researchClass, $joinRequest, $review, true);
    }

    public function reject(
        Request $request,
        ResearchClass $researchClass,
        ResearchClassEnrollment $joinRequest,
        ReviewResearchClassJoinRequest $review,
    ): JsonResponse|RedirectResponse {
        return $this->review($request, $researchClass, $joinRequest, $review, false);
    }

    private function review(
        Request $request,
        ResearchClass $researchClass,
        ResearchClassEnrollment $joinRequest,
        ReviewResearchClassJoinRequest $review,
        bool $approve,
    ): JsonResponse|RedirectResponse {
        Gate::authorize('manageJoinRequests', $researchClass);

        try {
            $enrollment = $approve
                ? $review->approve($request->user(), $researchClass, $joinRequest)
                : $review->reject($request->user(), $researchClass, $joinRequest);
        } catch (DuplicateClassOperation $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 409);
        } catch (ClassOperationException $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 422);
        }

        $message = $approve
            ? 'Join request approved. The student is now enrolled.'
            : 'Join request rejected.';

        if (! $request->expectsJson()) {
            return to_route('facilitator.dashboard', ['tab' => 'join-requests'])
                ->with('join_request_success', $message);
        }

        return response()->json([
            'message' => $message,
            'join_request' => [
                'id' => $enrollment->getKey(),
                'status' => $enrollment->status,
                'reviewed_at' => $enrollment->reviewed_at?->toIso8601String(),
            ],
        ]);
    }

    private function errorResponse(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return to_route('facilitator.dashboard', ['tab' => 'join-requests'])
            ->withErrors(['join_request' => $message]);
    }
}
