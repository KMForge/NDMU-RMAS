<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consultations\ApproveConsultationRequest;
use App\Http\Requests\Consultations\CorrectConsultationRecordRequest;
use App\Http\Requests\Consultations\ProposeConsultationRescheduleRequest;
use App\Http\Requests\Consultations\RecordCompletedConsultationRequest;
use App\Http\Requests\Consultations\RejectConsultationRequest as RejectFormRequest;
use App\Http\Requests\Consultations\UpdateConsultationMeetingDetailsRequest;
use App\Models\ConsultationRecord;
use App\Models\ConsultationRequest;
use App\Modules\Consultations\Actions\ApproveConsultation;
use App\Modules\Consultations\Actions\CorrectConsultationRecord;
use App\Modules\Consultations\Actions\ProposeConsultationReschedule;
use App\Modules\Consultations\Actions\RecordCompletedConsultation;
use App\Modules\Consultations\Actions\RejectConsultationRequest;
use App\Modules\Consultations\Actions\UpdateConsultationMeetingDetails;
use App\Modules\Consultations\Exceptions\ConsultationException;
use Illuminate\Http\RedirectResponse;

class ConsultationController extends Controller
{
    public function approve(
        ApproveConsultationRequest $request,
        ConsultationRequest $consultationRequest,
        ApproveConsultation $action,
    ): RedirectResponse {
        try {
            $action->handle($request->user(), $consultationRequest, $request->validatedData());

            return to_route('adviser.dashboard', ['tab' => 'consultation'])
                ->with('consultation_success', 'Consultation request approved successfully.');
        } catch (ConsultationException $exception) {
            return to_route('adviser.dashboard', ['tab' => 'consultation'])
                ->withErrors(['consultation' => $exception->getMessage()]);
        }
    }

    public function proposeReschedule(
        ProposeConsultationRescheduleRequest $request,
        ConsultationRequest $consultationRequest,
        ProposeConsultationReschedule $action,
    ): RedirectResponse {
        try {
            $action->handle($request->user(), $consultationRequest, $request->validatedData());

            return to_route('adviser.dashboard', ['tab' => 'consultation'])
                ->with('consultation_success', 'Schedule proposal sent to student successfully.');
        } catch (ConsultationException $exception) {
            return to_route('adviser.dashboard', ['tab' => 'consultation'])
                ->withErrors(['consultation' => $exception->getMessage()]);
        }
    }

    public function reject(
        RejectFormRequest $request,
        ConsultationRequest $consultationRequest,
        RejectConsultationRequest $action,
    ): RedirectResponse {
        try {
            $action->handle($request->user(), $consultationRequest, $request->reason());

            return to_route('adviser.dashboard', ['tab' => 'consultation'])
                ->with('consultation_success', 'Consultation request rejected.');
        } catch (ConsultationException $exception) {
            return to_route('adviser.dashboard', ['tab' => 'consultation'])
                ->withErrors(['consultation' => $exception->getMessage()]);
        }
    }

    public function complete(
        RecordCompletedConsultationRequest $request,
        ConsultationRequest $consultationRequest,
        RecordCompletedConsultation $action,
    ): RedirectResponse {
        try {
            $action->handle($request->user(), $consultationRequest, $request->validatedData());

            return to_route('adviser.dashboard', ['tab' => 'consultation'])
                ->with('consultation_success', 'Official consultation record saved successfully.');
        } catch (ConsultationException $exception) {
            return to_route('adviser.dashboard', ['tab' => 'consultation'])
                ->withErrors(['consultation' => $exception->getMessage()]);
        }
    }

    public function updateMeetingDetails(
        UpdateConsultationMeetingDetailsRequest $request,
        ConsultationRequest $consultationRequest,
        UpdateConsultationMeetingDetails $action,
    ): RedirectResponse {
        try {
            $action->handle($request->user(), $consultationRequest, $request->validatedData());

            return to_route('adviser.dashboard', ['tab' => 'consultation', 'consultation_status' => 'approved'])
                ->with('consultation_success', 'Meeting details updated successfully.');
        } catch (ConsultationException $exception) {
            return to_route('adviser.dashboard', ['tab' => 'consultation', 'consultation_status' => 'approved'])
                ->withErrors(['consultation' => $exception->getMessage()]);
        }
    }

    public function correctRecord(
        CorrectConsultationRecordRequest $request,
        ConsultationRecord $record,
        CorrectConsultationRecord $action,
    ): RedirectResponse {
        try {
            $action->handle($request->user(), $record, $request->validatedData());

            return to_route('adviser.dashboard', ['tab' => 'consultation'])
                ->with('consultation_success', 'Consultation record corrected successfully.');
        } catch (ConsultationException $exception) {
            return to_route('adviser.dashboard', ['tab' => 'consultation'])
                ->withErrors(['consultation' => $exception->getMessage()]);
        }
    }
}
