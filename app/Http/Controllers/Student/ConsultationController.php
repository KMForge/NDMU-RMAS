<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consultations\BookConsultationRequest;
use App\Modules\Consultations\Actions\BookConsultation;
use App\Modules\Consultations\Exceptions\ConsultationBookingUnavailable;
use App\Modules\Consultations\Exceptions\DuplicateConsultationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ConsultationController extends Controller
{
    public function store(
        BookConsultationRequest $request,
        BookConsultation $bookConsultation,
    ): JsonResponse|RedirectResponse {
        try {
            $consultation = $bookConsultation->handle(
                $request->user(),
                $request->string('request_token')->toString(),
                $request->preferredAt(),
                $request->string('consultation_mode')->toString(),
                $request->string('agenda')->toString(),
            );
        } catch (DuplicateConsultationRequest $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 409);
        } catch (ConsultationBookingUnavailable $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 422);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Consultation request submitted successfully.',
                'consultation' => [
                    'id' => $consultation->getKey(),
                    'preferred_at' => $consultation->preferred_at->toIso8601String(),
                    'consultation_mode' => $consultation->consultation_mode,
                    'status' => $consultation->status,
                ],
            ], 201);
        }

        return to_route('student.dashboard', ['tab' => 'consultation'])
            ->with('consultation_success', 'Consultation request submitted and is pending adviser approval.');
    }

    private function errorResponse(
        BookConsultationRequest $request,
        string $message,
        int $status,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return to_route('student.dashboard', ['tab' => 'consultation'])
            ->withErrors(['consultation' => $message]);
    }
}
