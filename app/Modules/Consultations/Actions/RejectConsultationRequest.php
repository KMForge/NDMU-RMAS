<?php

namespace App\Modules\Consultations\Actions;

use App\Enums\ConsultationStatus;
use App\Models\ConsultationAudit;
use App\Models\ConsultationRequest;
use App\Models\User;
use App\Modules\Consultations\Exceptions\ConsultationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RejectConsultationRequest
{
    public function handle(User $adviser, ConsultationRequest $request, string $reason): ConsultationRequest
    {
        if (! $adviser->can('consultations.manage-assigned')) {
            throw new ConsultationException('You do not have permission to manage consultations.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new ConsultationException('A valid rejection reason is required.');
        }

        try {
            return DB::transaction(function () use ($adviser, $request, $reason): ConsultationRequest {
                $lockedRequest = ConsultationRequest::query()
                    ->with('researchClassGroup')
                    ->whereKey($request->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (in_array($lockedRequest->status, [ConsultationStatus::Completed, ConsultationStatus::Cancelled, ConsultationStatus::Rejected], true)) {
                    throw new ConsultationException('This consultation request can no longer be rejected.');
                }

                if (! $lockedRequest->researchClassGroup || (int) $lockedRequest->researchClassGroup->adviser_id !== (int) $adviser->id) {
                    throw new ConsultationException('You are not the currently assigned adviser for this Research Group.');
                }

                $lockedRequest->update([
                    'status' => ConsultationStatus::Rejected,
                    'reviewed_at' => now(),
                    'reviewed_by' => $adviser->id,
                    'review_notes' => $reason,
                ]);

                ConsultationAudit::query()->create([
                    'consultation_request_id' => $lockedRequest->id,
                    'research_class_group_id' => $lockedRequest->research_class_group_id,
                    'actor_id' => $adviser->id,
                    'action' => 'consultation_rejected',
                    'status' => ConsultationStatus::Rejected->value,
                    'occurred_at' => now(),
                    'metadata' => [
                        'rejection_reason' => $reason,
                    ],
                ]);

                return $lockedRequest->fresh(['researchClassGroup', 'assignedAdviser', 'requester', 'reviewer']);
            }, 3);
        } catch (ConsultationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            throw new ConsultationException('The consultation request could not be rejected. Please try again.');
        }
    }
}
