<?php

namespace App\Modules\Consultations\Actions;

use App\Enums\ConsultationStatus;
use App\Models\ConsultationAudit;
use App\Models\ConsultationRequest;
use App\Models\ConsultationScheduleProposal;
use App\Models\User;
use App\Modules\Consultations\Exceptions\ConsultationException;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ProposeConsultationReschedule
{
    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications,
    ) {}

    /**
     * @param  array{
     *     proposed_start_at: CarbonImmutable
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
                $minAdvanceMinutes = max(0, (int) config('consultations.minimum_advance_minutes', 0));
                $earliestProposedStart = $minAdvanceMinutes > 0
                    ? now()->addMinutes($minAdvanceMinutes)
                    : now()->startOfMinute();

                if ($proposedStart->isBefore($earliestProposedStart)) {
                    $message = $minAdvanceMinutes > 0
                        ? "Proposed schedule must be at least {$minAdvanceMinutes} minutes in advance."
                        : 'Proposed schedule must use the current time or a future date and time.';

                    throw new ConsultationException($message);
                }

                $durationMinutes = (int) $lockedRequest->duration_minutes;
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
                    'reason' => null,
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

                $student = User::query()->find($lockedRequest->requested_by);

                if ($student !== null) {
                    $this->notifications->send(
                        recipient: $student,
                        eventKey: 'consultation.reschedule-proposed',
                        title: 'Consultation reschedule proposed',
                        message: "{$adviser->name} proposed {$proposedStart->format('M j, Y g:i A')} for your consultation.",
                        category: 'consultation',
                        routeName: 'student.dashboard',
                        routeParameters: ['tab' => 'consultation'],
                        sourceType: ConsultationScheduleProposal::class,
                        sourceId: $proposal->getKey(),
                        actor: $adviser,
                        contextLabel: $lockedRequest->researchClassGroup?->name,
                        actingAs: 'Student Researcher',
                    );
                }

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
