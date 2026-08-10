<?php

namespace App\Modules\Consultations\Actions;

use App\Enums\ConsultationMode;
use App\Enums\ConsultationStatus;
use App\Models\ConsultationAudit;
use App\Models\ConsultationRequest;
use App\Models\User;
use App\Modules\Consultations\Exceptions\ConsultationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class UpdateConsultationMeetingDetails
{
    /**
     * @param  array{consultation_mode: string, location: ?string, meeting_url: ?string}  $data
     */
    public function handle(User $adviser, ConsultationRequest $request, array $data): ConsultationRequest
    {
        if (! $adviser->can('consultations.manage-assigned')) {
            throw new ConsultationException('You do not have permission to manage consultations.');
        }

        try {
            return DB::transaction(function () use ($adviser, $request, $data): ConsultationRequest {
                $lockedRequest = ConsultationRequest::query()
                    ->with('researchClassGroup')
                    ->whereKey($request->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedRequest->status !== ConsultationStatus::Approved) {
                    throw new ConsultationException('Meeting details can only be changed for an approved consultation.');
                }

                if (! $lockedRequest->researchClassGroup || (int) $lockedRequest->researchClassGroup->adviser_id !== (int) $adviser->id) {
                    throw new ConsultationException('You are not the currently assigned adviser for this Research Group.');
                }

                $mode = ConsultationMode::from($data['consultation_mode']);
                $meetingUrl = $mode === ConsultationMode::Online ? $data['meeting_url'] : null;
                $location = $mode === ConsultationMode::InPerson ? $data['location'] : null;

                $lockedRequest->update([
                    'consultation_mode' => $mode,
                    'meeting_url' => $meetingUrl,
                    'location' => $location,
                ]);

                ConsultationAudit::query()->create([
                    'consultation_request_id' => $lockedRequest->id,
                    'research_class_group_id' => $lockedRequest->research_class_group_id,
                    'actor_id' => $adviser->id,
                    'action' => 'consultation_meeting_details_updated',
                    'status' => ConsultationStatus::Approved->value,
                    'occurred_at' => now(),
                    'metadata' => [
                        'mode' => $mode->value,
                        'has_meeting_url' => $meetingUrl !== null,
                        'has_location' => $location !== null,
                    ],
                ]);

                return $lockedRequest->fresh(['researchClassGroup', 'assignedAdviser', 'requester']);
            }, 3);
        } catch (ConsultationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            throw new ConsultationException('The meeting details could not be saved. Please try again.');
        }
    }
}
