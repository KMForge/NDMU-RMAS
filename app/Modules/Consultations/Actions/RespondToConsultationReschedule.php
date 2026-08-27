<?php

namespace App\Modules\Consultations\Actions;

use App\Enums\ConsultationStatus;
use App\Models\ConsultationAudit;
use App\Models\ConsultationRequest;
use App\Models\ConsultationScheduleProposal;
use App\Models\User;
use App\Modules\Consultations\Exceptions\ConsultationException;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RespondToConsultationReschedule
{
    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications,
    ) {}

    public function handle(User $requester, ConsultationRequest $request, string $responseAction): ConsultationRequest
    {
        $responseAction = strtolower(trim($responseAction));
        if (! in_array($responseAction, ['accept', 'decline'], true)) {
            throw new ConsultationException('Invalid response action. Must be accept or decline.');
        }

        try {
            return DB::transaction(function () use ($requester, $request, $responseAction): ConsultationRequest {
                $lockedRequest = ConsultationRequest::query()
                    ->with('researchClassGroup')
                    ->whereKey($request->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedRequest->status !== ConsultationStatus::RescheduleProposed) {
                    throw new ConsultationException('No active reschedule proposal exists for this request.');
                }

                if ((int) $lockedRequest->requested_by !== (int) $requester->id) {
                    throw new ConsultationException('Only the student who originally created the consultation request can respond to reschedule proposals.');
                }

                $proposal = ConsultationScheduleProposal::query()
                    ->where('consultation_request_id', $lockedRequest->id)
                    ->where('status', 'pending_response')
                    ->latest()
                    ->lockForUpdate()
                    ->first();

                if (! $proposal) {
                    throw new ConsultationException('No pending schedule proposal found.');
                }

                if ($responseAction === 'accept') {
                    $startAt = $proposal->proposed_start_at;
                    $durationMinutes = $proposal->duration_minutes;
                    $endAt = $startAt->addMinutes($durationMinutes);

                    // Overlap check for assigned adviser
                    $hasOverlap = ConsultationRequest::query()
                        ->where('assigned_adviser_id', $lockedRequest->assigned_adviser_id)
                        ->where('status', ConsultationStatus::Approved->value)
                        ->where('id', '!=', $lockedRequest->id)
                        ->where(function (Builder $query) use ($startAt, $endAt): void {
                            $query->where('confirmed_start_at', '<', $endAt)
                                ->where('confirmed_end_at', '>', $startAt);
                        })
                        ->lockForUpdate()
                        ->exists();

                    if ($hasOverlap) {
                        throw new ConsultationException('Adviser has an overlapping approved consultation schedule for the proposed time.');
                    }

                    $proposal->update([
                        'status' => 'accepted',
                        'responded_by' => $requester->id,
                        'responded_at' => now(),
                    ]);

                    $lockedRequest->update([
                        'status' => ConsultationStatus::Approved,
                        'confirmed_start_at' => $startAt,
                        'confirmed_end_at' => $endAt,
                        'duration_minutes' => $durationMinutes,
                    ]);

                    ConsultationAudit::query()->create([
                        'consultation_request_id' => $lockedRequest->id,
                        'research_class_group_id' => $lockedRequest->research_class_group_id,
                        'actor_id' => $requester->id,
                        'action' => 'consultation_reschedule_accepted',
                        'status' => ConsultationStatus::Approved->value,
                        'occurred_at' => now(),
                        'metadata' => [
                            'proposal_id' => $proposal->id,
                            'confirmed_start_at' => $startAt->toIso8601String(),
                        ],
                    ]);
                } else {
                    $proposal->update([
                        'status' => 'declined',
                        'responded_by' => $requester->id,
                        'responded_at' => now(),
                    ]);

                    $lockedRequest->update([
                        'status' => ConsultationStatus::Pending,
                    ]);

                    ConsultationAudit::query()->create([
                        'consultation_request_id' => $lockedRequest->id,
                        'research_class_group_id' => $lockedRequest->research_class_group_id,
                        'actor_id' => $requester->id,
                        'action' => 'consultation_reschedule_declined',
                        'status' => ConsultationStatus::Pending->value,
                        'occurred_at' => now(),
                        'metadata' => [
                            'proposal_id' => $proposal->id,
                        ],
                    ]);
                }

                $adviser = User::query()->find($lockedRequest->assigned_adviser_id);

                if ($adviser !== null) {
                    $responseLabel = $responseAction === 'accept' ? 'accepted' : 'declined';
                    $this->notifications->send(
                        recipient: $adviser,
                        eventKey: "consultation.reschedule-{$responseLabel}",
                        title: "Consultation reschedule {$responseLabel}",
                        message: "{$requester->name} {$responseLabel} your consultation reschedule proposal.",
                        category: 'consultation',
                        routeName: 'adviser.dashboard',
                        routeParameters: ['tab' => 'consultation'],
                        sourceType: ConsultationScheduleProposal::class,
                        sourceId: $proposal->getKey(),
                        actor: $requester,
                        contextLabel: $lockedRequest->researchClassGroup?->name,
                        actingAs: 'Thesis Adviser',
                        occurrence: $responseAction,
                    );
                }

                return $lockedRequest->fresh(['researchClassGroup', 'assignedAdviser', 'requester', 'proposals']);
            }, 3);
        } catch (ConsultationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            throw new ConsultationException('Could not process proposal response. Please try again.');
        }
    }
}
