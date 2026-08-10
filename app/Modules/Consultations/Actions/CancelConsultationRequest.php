<?php

namespace App\Modules\Consultations\Actions;

use App\Enums\ConsultationStatus;
use App\Models\ConsultationAudit;
use App\Models\ConsultationRequest;
use App\Models\User;
use App\Modules\Consultations\Exceptions\ConsultationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CancelConsultationRequest
{
    public function handle(User $requester, ConsultationRequest $request, ?string $reason = null): ConsultationRequest
    {
        try {
            return DB::transaction(function () use ($requester, $request, $reason): ConsultationRequest {
                $lockedRequest = ConsultationRequest::query()
                    ->whereKey($request->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $lockedRequest->requested_by !== (int) $requester->id) {
                    throw new ConsultationException('Only the student who originally created the consultation request can cancel it.');
                }

                if (in_array($lockedRequest->status, [ConsultationStatus::Completed, ConsultationStatus::Rejected, ConsultationStatus::Cancelled], true)) {
                    throw new ConsultationException('This consultation request can no longer be cancelled.');
                }

                $lockedRequest->update([
                    'status' => ConsultationStatus::Cancelled,
                    'cancelled_by' => $requester->id,
                    'cancelled_at' => now(),
                    'cancellation_reason' => ! empty($reason) ? trim($reason) : null,
                ]);

                ConsultationAudit::query()->create([
                    'consultation_request_id' => $lockedRequest->id,
                    'research_class_group_id' => $lockedRequest->research_class_group_id,
                    'actor_id' => $requester->id,
                    'action' => 'consultation_cancelled',
                    'status' => ConsultationStatus::Cancelled->value,
                    'occurred_at' => now(),
                    'metadata' => [
                        'cancellation_reason' => ! empty($reason) ? trim($reason) : null,
                    ],
                ]);

                return $lockedRequest->fresh(['researchClassGroup', 'assignedAdviser', 'requester', 'canceller']);
            }, 3);
        } catch (ConsultationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            throw new ConsultationException('The consultation request could not be cancelled. Please try again.');
        }
    }
}
