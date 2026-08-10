<?php

namespace App\Modules\Consultations\Actions;

use App\Enums\ConsultationStatus;
use App\Models\ConsultationAudit;
use App\Models\ConsultationRequest;
use App\Models\Document;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Modules\Consultations\Exceptions\ConsultationException;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookConsultation
{
    /**
     * @param  array{
     *     preferred_at: CarbonImmutable,
     *     consultation_mode: string,
     *     duration_minutes?: int,
     *     agenda: string,
     *     document_stage?: ?string,
     *     document_id?: ?int,
     *     request_token?: ?string
     * }  $data
     */
    public function handle(User $requester, array $data): ConsultationRequest
    {
        if (! $requester->can('consultations.request')) {
            throw new ConsultationException('You do not have permission to request consultations.');
        }

        $groupMember = ResearchClassGroupMember::query()
            ->with('researchClassGroup')
            ->where('student_id', $requester->id)
            ->whereHas('researchClassGroup', fn ($g) => $g->where('status', 'active')->whereNull('disbanded_at'))
            ->latest()
            ->first();

        if (! $groupMember || ! $groupMember->researchClassGroup) {
            throw new ConsultationException('You must be an active member of an active Research Group to book a consultation.');
        }

        /** @var ResearchClassGroup $group */
        $group = $groupMember->researchClassGroup;

        if (! $group->adviser_id) {
            throw new ConsultationException('No adviser is currently assigned to your Research Group.');
        }

        $preferredAt = $data['preferred_at'];
        $minAdvanceMinutes = (int) config('consultations.minimum_advance_minutes', 360);
        $maxAdvanceDays = (int) config('consultations.maximum_advance_days', 90);

        if ($preferredAt->isBefore(now()->addMinutes($minAdvanceMinutes))) {
            throw new ConsultationException("Consultations must be booked at least {$minAdvanceMinutes} minutes in advance.");
        }

        if ($preferredAt->isAfter(now()->addDays($maxAdvanceDays))) {
            throw new ConsultationException("Consultations cannot be booked more than {$maxAdvanceDays} days in advance.");
        }

        $durationMinutes = (int) ($data['duration_minutes'] ?? config('consultations.default_duration', 60));
        $allowedDurations = (array) config('consultations.allowed_durations', [30, 45, 60]);
        if (! in_array($durationMinutes, $allowedDurations, true)) {
            throw new ConsultationException('Invalid consultation duration selected.');
        }

        $hasUnresolvedRequest = ConsultationRequest::query()
            ->where('research_class_group_id', $group->id)
            ->whereIn('status', [ConsultationStatus::Pending->value, ConsultationStatus::RescheduleProposed->value])
            ->exists();

        if ($hasUnresolvedRequest) {
            throw new ConsultationException('Your Research Group already has an active unresolved consultation request.');
        }

        if (! empty($data['document_id'])) {
            $document = Document::query()->find($data['document_id']);
            if (! $document || (int) $document->research_class_group_id !== (int) $group->id) {
                throw new ConsultationException('The selected related document does not belong to your Research Group.');
            }
        }

        $requestToken = ! empty($data['request_token']) && Str::isUuid($data['request_token'])
            ? $data['request_token']
            : (string) Str::uuid();

        try {
            return DB::transaction(function () use ($requester, $group, $preferredAt, $durationMinutes, $data, $requestToken): ConsultationRequest {
                $request = ConsultationRequest::query()->create([
                    'research_class_group_id' => $group->id,
                    'assigned_adviser_id' => $group->adviser_id,
                    'requested_by' => $requester->id,
                    'request_token' => $requestToken,
                    'preferred_at' => $preferredAt,
                    'duration_minutes' => $durationMinutes,
                    'consultation_mode' => $data['consultation_mode'],
                    'agenda' => trim($data['agenda']),
                    'document_stage' => $data['document_stage'] ?? null,
                    'document_id' => $data['document_id'] ?? null,
                    'status' => ConsultationStatus::Pending,
                ]);

                ConsultationAudit::query()->create([
                    'consultation_request_id' => $request->id,
                    'research_class_group_id' => $group->id,
                    'actor_id' => $requester->id,
                    'action' => 'consultation_requested',
                    'status' => ConsultationStatus::Pending->value,
                    'occurred_at' => now(),
                    'metadata' => [
                        'preferred_at' => $preferredAt->toIso8601String(),
                        'duration_minutes' => $durationMinutes,
                        'mode' => $data['consultation_mode'],
                    ],
                ]);

                return $request->load(['researchClassGroup', 'assignedAdviser', 'requester']);
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ConsultationException('The consultation request could not be saved. Please try again.');
        }
    }
}
