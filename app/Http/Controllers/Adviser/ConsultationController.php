<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consultations\CompleteConsultationRequest;
use App\Http\Requests\Consultations\ReviewConsultationRequest as ReviewRequest;
use App\Models\ConsultationRequest;
use App\Modules\Consultations\Actions\RecordCompletedConsultation;
use App\Modules\Consultations\Actions\ReviewConsultationRequest;
use App\Modules\Consultations\Exceptions\ConsultationReviewException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ConsultationController extends Controller
{
    public function complete(
        CompleteConsultationRequest $request,
        ConsultationRequest $consultationRequest,
        RecordCompletedConsultation $recordConsultation,
    ): JsonResponse|RedirectResponse {
        Gate::authorize('manage', $consultationRequest);

        try {
            $recordId = $recordConsultation->handle(
                $request->user(),
                $consultationRequest,
                $request->validated(),
            );
        } catch (ConsultationReviewException $exception) {
            return $this->errorResponse($request, $exception->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Consultation completed and recorded successfully.',
                'consultation_record' => [
                    'id' => $recordId,
                    'status' => 'completed',
                ],
            ]);
        }

        return to_route('adviser.dashboard', [
            'tab' => 'consultation',
            'consultation_status' => 'completed',
        ])->with('consultation_success', 'Consultation completed and recorded successfully.');
    }

    public function approve(
        ReviewRequest $request,
        ConsultationRequest $consultationRequest,
        ReviewConsultationRequest $reviewConsultation,
    ): JsonResponse|RedirectResponse {
        Gate::authorize('manage', $consultationRequest);

        try {
            $consultation = $reviewConsultation->approve(
                $request->user(),
                $consultationRequest,
                $request->validated('review_notes'),
            );
        } catch (ConsultationReviewException $exception) {
            return $this->errorResponse($request, $exception->getMessage());
        }

        return $this->successResponse(
            $request,
            $consultation,
            'Consultation request approved and scheduled.',
        );
    }

    public function reject(
        ReviewRequest $request,
        ConsultationRequest $consultationRequest,
        ReviewConsultationRequest $reviewConsultation,
    ): JsonResponse|RedirectResponse {
        Gate::authorize('manage', $consultationRequest);

        try {
            $consultation = $reviewConsultation->reject(
                $request->user(),
                $consultationRequest,
                $request->validated('review_notes'),
            );
        } catch (ConsultationReviewException $exception) {
            return $this->errorResponse($request, $exception->getMessage());
        }

        return $this->successResponse(
            $request,
            $consultation,
            'Consultation request rejected.',
        );
    }

    private function successResponse(
        ReviewRequest $request,
        ConsultationRequest $consultation,
        string $message,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'consultation' => [
                    'id' => $consultation->getKey(),
                    'status' => $consultation->status,
                    'preferred_at' => $consultation->preferred_at->toIso8601String(),
                    'reviewed_at' => $consultation->reviewed_at?->toIso8601String(),
                ],
            ]);
        }

        return to_route('adviser.dashboard', ['tab' => 'consultation'])
            ->with('consultation_success', $message);
    }

    private function errorResponse(
        ReviewRequest|CompleteConsultationRequest $request,
        string $message,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 409);
        }

        return to_route('adviser.dashboard', ['tab' => 'consultation'])
            ->withErrors(['consultation' => $message]);
    }
}
