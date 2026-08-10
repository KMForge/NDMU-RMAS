<?php

namespace App\Modules\Consultations\Actions;

use App\Enums\ConsultationMode;
use App\Enums\ConsultationStatus;
use App\Models\ConsultationAudit;
use App\Models\ConsultationRequest;
use App\Models\User;
use App\Modules\Consultations\Exceptions\ConsultationException;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ApproveConsultation
{
    /**
     * @param  array{
     *     confirmed_start_at?: ?CarbonImmutable,
     *     duration_minutes?: ?int,
     *     consultation_mode?: ?string,
     *     location?: ?string,
     *     meeting_url?: ?string,
     *     review_notes?: ?string
     * }  $data
     */
    public function handle(User $adviser, ConsultationRequest $request, array $data = []): ConsultationRequest
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

                if (! in_array($lockedRequest->status, [ConsultationStatus::Pending, ConsultationStatus::RescheduleProposed], true)) {
                    throw new ConsultationException('Only pending or reschedule proposed requests can be approved.');
                }

                if (! $lockedRequest->researchClassGroup || (int) $lockedRequest->researchClassGroup->adviser_id !== (int) $adviser->id) {
                    throw new ConsultationException('You are not the currently assigned adviser for this Research Group.');
                }

                $startAt = $data['confirmed_start_at'] ?? $lockedRequest->preferred_at;
                $durationMinutes = (int) ($data['duration_minutes'] ?? $lockedRequest->duration_minutes);
                $endAt = $startAt->addMinutes($durationMinutes);
                $mode = $data['consultation_mode'] ?? $lockedRequest->consultation_mode->value;

                if ($mode === ConsultationMode::Online->value && ! empty($data['meeting_url'])) {
                    $url = filter_var($data['meeting_url'], FILTER_VALIDATE_URL);
                    if ($url === false || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                        throw new ConsultationException('Invalid meeting URL. Please provide a valid HTTP or HTTPS link.');
                    }
                }

                // Double booking check: lock existing approved requests for this adviser
                $hasOverlap = ConsultationRequest::query()
                    ->where('assigned_adviser_id', $adviser->id)
                    ->where('status', ConsultationStatus::Approved->value)
                    ->where('id', '!=', $lockedRequest->id)
                    ->where(function (Builder $query) use ($startAt, $endAt): void {
                        $query->where('confirmed_start_at', '<', $endAt)
                            ->where('confirmed_end_at', '>', $startAt);
                    })
                    ->lockForUpdate()
                    ->exists();

                if ($hasOverlap) {
                    throw new ConsultationException('Adviser has an overlapping approved consultation schedule.');
                }

                $lockedRequest->update([
                    'status' => ConsultationStatus::Approved,
                    'confirmed_start_at' => $startAt,
                    'confirmed_end_at' => $endAt,
                    'duration_minutes' => $durationMinutes,
                    'consultation_mode' => $mode,
                    'location' => $data['location'] ?? $lockedRequest->location,
                    'meeting_url' => $data['meeting_url'] ?? $lockedRequest->meeting_url,
                    'reviewed_at' => now(),
                    'reviewed_by' => $adviser->id,
                    'review_notes' => $data['review_notes'] ?? null,
                ]);

                ConsultationAudit::query()->create([
                    'consultation_request_id' => $lockedRequest->id,
                    'research_class_group_id' => $lockedRequest->research_class_group_id,
                    'actor_id' => $adviser->id,
                    'action' => 'consultation_approved',
                    'status' => ConsultationStatus::Approved->value,
                    'occurred_at' => now(),
                    'metadata' => [
                        'confirmed_start_at' => $startAt->toIso8601String(),
                        'confirmed_end_at' => $endAt->toIso8601String(),
                        'duration_minutes' => $durationMinutes,
                        'mode' => $mode,
                    ],
                ]);

                return $lockedRequest->fresh(['researchClassGroup', 'assignedAdviser', 'requester', 'reviewer']);
            }, 3);
        } catch (ConsultationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            throw new ConsultationException('The consultation could not be approved. Please try again.');
        }
    }
}
