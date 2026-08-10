<?php

namespace App\Modules\Consultations\Actions;

use App\Enums\ConsultationStatus;
use App\Models\ConsultationAudit;
use App\Models\ConsultationRequest;
use App\Models\ConsultationScheduleProposal;
use App\Models\User;
use App\Modules\Consultations\Exceptions\ConsultationException;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ProposeConsultationReschedule
{
    /**
     * @param  array{
     *     proposed_start_at: CarbonImmutable,
     *     duration_minutes?: ?int,
     *     reason?: ?string
     * }  $data
     */
    public function handle(User $adviser, ConsultationRequest $request, array $data): ConsultationScheduleProposal
    {
        if (! $adviser->can('consultations.manage-assigned')) {
            throw new ConsultationException('You do not have permission to manage consultations.');
        }

        try {
            return DB::transaction(function () use ($adviser, $request, $data): ConsultationScheduleProposal {
                $lockedRequest = ConsultationRequest::query()
                    ->with('researchClassGroup')
                    ->whereKey($request->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array($lockedRequest->status, [ConsultationStatus::Pending, ConsultationStatus::RescheduleProposed], true)) {
                    throw new ConsultationException('Only pending or active reschedule proposed requests can receive a new schedule proposal.');
                }

                if (! $lockedRequest->researchClassGroup || (int) $lockedRequest->researchClassGroup->adviser_id !== (int) $adviser->id) {
                    throw new ConsultationException('You are not the currently assigned adviser for this Research Group.');
                }

                $proposedStart = $data['proposed_start_at'];
                $minAdvanceMinutes = (int) config('consultations.minimum_advance_minutes', 360);
                if ($proposedStart->isBefore(now()->addMinutes($minAdvanceMinutes))) {
                    throw new ConsultationException("Proposed schedule must be at least {$minAdvanceMinutes} minutes in advance.");
                }

                $durationMinutes = (int) ($data['duration_minutes'] ?? $lockedRequest->duration_minutes);
                $allowedDurations = (array) config('consultations.allowed_durations', [30, 45, 60]);
                if (! in_array($durationMinutes, $allowedDurations, true)) {
                    throw new ConsultationException('Invalid duration selected for proposal.');
                }

                // If prior pending proposals exist for this request, mark them superseded
                ConsultationScheduleProposal::query()
                    ->where('consultation_request_id', $lockedRequest->id)
                    ->where('status', 'pending_response')
                    ->update(['status' => 'declined']);

                $proposal = ConsultationScheduleProposal::query()->create([
                    'consultation_request_id' => $lockedRequest->id,
                    'proposed_by' => $adviser->id,
                    'proposed_start_at' => $proposedStart,
                    'duration_minutes' => $durationMinutes,
                    'reason' => ! empty($data['reason']) ? trim($data['reason']) : null,
                    'status' => 'pending_response',
                ]);

                $lockedRequest->update([
                    'status' => ConsultationStatus::RescheduleProposed,
                    'reviewed_at' => now(),
                    'reviewed_by' => $adviser->id,
                ]);

                ConsultationAudit::query()->create([
                    'consultation_request_id' => $lockedRequest->id,
                    'research_class_group_id' => $lockedRequest->research_class_group_id,
                    'actor_id' => $adviser->id,
                    'action' => 'consultation_reschedule_proposed',
                    'status' => ConsultationStatus::RescheduleProposed->value,
                    'occurred_at' => now(),
                    'metadata' => [
                        'proposal_id' => $proposal->id,
                        'proposed_start_at' => $proposedStart->toIso8601String(),
                        'duration_minutes' => $durationMinutes,
                    ],
                ]);

                return $proposal;
            }, 3);
        } catch (ConsultationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            throw new ConsultationException('The schedule proposal could not be saved. Please try again.');
        }
    }
}
