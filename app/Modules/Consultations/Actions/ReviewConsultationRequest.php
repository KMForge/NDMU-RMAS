<?php

namespace App\Modules\Consultations\Actions;

use App\Models\ConsultationRequest;
use App\Models\User;
use App\Modules\Consultations\Exceptions\ConsultationReviewException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ReviewConsultationRequest
{
    public function approve(
        User $adviser,
        ConsultationRequest $consultationRequest,
        ?string $notes,
    ): ConsultationRequest {
        return $this->review($adviser, $consultationRequest, 'approved', $notes);
    }

    public function reject(
        User $adviser,
        ConsultationRequest $consultationRequest,
        ?string $notes,
    ): ConsultationRequest {
        return $this->review($adviser, $consultationRequest, 'rejected', $notes);
    }

    private function review(
        User $adviser,
        ConsultationRequest $consultationRequest,
        string $decision,
        ?string $notes,
    ): ConsultationRequest {
        try {
            return DB::transaction(function () use (
                $adviser,
                $consultationRequest,
                $decision,
                $notes,
            ): ConsultationRequest {
                $lockedRequest = ConsultationRequest::query()
                    ->whereKey($consultationRequest->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $isAssignedAdviser = DB::table('adviser_assignments as assignments')
                    ->join('faculty_profiles as faculty', 'faculty.id', '=', 'assignments.adviser_id')
                    ->where('assignments.id', $lockedRequest->adviser_assignment_id)
                    ->where('faculty.user_id', $adviser->getKey())
                    ->where('assignments.status', 'active')
                    ->whereNull('assignments.ended_at')
                    ->exists();

                if (! $isAssignedAdviser) {
                    throw new ConsultationReviewException(
                        'This consultation request is not assigned to you.',
                    );
                }

                if ($lockedRequest->status !== 'pending') {
                    throw new ConsultationReviewException(
                        'This consultation request has already been reviewed.',
                    );
                }

                $lockedRequest->update([
                    'status' => $decision,
                    'reviewed_by' => $adviser->getKey(),
                    'reviewed_at' => now(),
                    'review_notes' => $notes,
                ]);

                return $lockedRequest->refresh();
            }, 3);
        } catch (ConsultationReviewException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            throw new ConsultationReviewException(
                'The consultation request could not be reviewed. Please try again.',
            );
        }
    }
}
