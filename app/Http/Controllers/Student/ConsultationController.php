<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consultations\BookConsultationRequest;
use App\Http\Requests\Consultations\CancelConsultationRequest as CancelFormRequest;
use App\Http\Requests\Consultations\RespondToConsultationRescheduleRequest;
use App\Models\ConsultationRequest;
use App\Modules\Consultations\Actions\BookConsultation;
use App\Modules\Consultations\Actions\CancelConsultationRequest;
use App\Modules\Consultations\Actions\RespondToConsultationReschedule;
use App\Modules\Consultations\Exceptions\ConsultationException;
use Illuminate\Http\RedirectResponse;

class ConsultationController extends Controller
{
    public function store(BookConsultationRequest $request, BookConsultation $action): RedirectResponse
    {
        try {
            $action->handle($request->user(), $request->validatedData());

            return to_route('student.dashboard', ['tab' => 'consultation'])
                ->with('consultation_success', 'Consultation request booked successfully.');
        } catch (ConsultationException $exception) {
            return to_route('student.dashboard', ['tab' => 'consultation'])
                ->withErrors(['consultation' => $exception->getMessage()]);
        }
    }

    public function cancel(
        CancelFormRequest $request,
        ConsultationRequest $consultationRequest,
        CancelConsultationRequest $action,
    ): RedirectResponse {
        try {
            $action->handle($request->user(), $consultationRequest, $request->reason());

            return to_route('student.dashboard', ['tab' => 'consultation'])
                ->with('consultation_success', 'Consultation request cancelled successfully.');
        } catch (ConsultationException $exception) {
            return to_route('student.dashboard', ['tab' => 'consultation'])
                ->withErrors(['consultation' => $exception->getMessage()]);
        }
    }

    public function respondToReschedule(
        RespondToConsultationRescheduleRequest $request,
        ConsultationRequest $consultationRequest,
        RespondToConsultationReschedule $action,
    ): RedirectResponse {
        try {
            $action->handle($request->user(), $consultationRequest, (string) $request->input('action'));

            $msg = strtolower((string) $request->input('action')) === 'accept'
                ? 'Proposed schedule accepted. Consultation is now approved.'
                : 'Proposed schedule declined. Request returned to pending.';

            return to_route('student.dashboard', ['tab' => 'consultation'])
                ->with('consultation_success', $msg);
        } catch (ConsultationException $exception) {
            return to_route('student.dashboard', ['tab' => 'consultation'])
                ->withErrors(['consultation' => $exception->getMessage()]);
        }
    }
}
