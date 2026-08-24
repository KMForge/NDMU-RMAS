<?php

namespace App\Modules\Consultations\Actions;

use App\Enums\ConsultationStatus;
use App\Models\ConsultationAttendance;
use App\Models\ConsultationAudit;
use App\Models\ConsultationRecord;
use App\Models\ConsultationRequest;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Modules\Consultations\Exceptions\ConsultationException;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RecordCompletedConsultation
{
    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications,
    ) {}

    /**
     * @param  array{
     *     consulted_at?: ?CarbonImmutable,
     *     duration_minutes?: ?int,
     *     discussion: string,
     *     recommendations?: ?string,
     *     next_consultation_at?: ?CarbonImmutable,
     *     attendees?: array<int, int>
     * }  $data
     */
    public function handle(User $adviser, ConsultationRequest $request, array $data): ConsultationRecord
    {
        if (! $adviser->can('consultations.manage-assigned')) {
            throw new ConsultationException('You do not have permission to manage consultations.');
        }

        $discussion = trim($data['discussion'] ?? '');
        if ($discussion === '') {
            throw new ConsultationException('Consultation discussion notes are required.');
        }

        try {
            return DB::transaction(function () use ($adviser, $request, $data, $discussion): ConsultationRecord {
                $lockedRequest = ConsultationRequest::query()
                    ->with('researchClassGroup')
                    ->whereKey($request->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedRequest->status !== ConsultationStatus::Approved) {
                    throw new ConsultationException('Only approved consultations can be marked as completed.');
                }

                if (! $lockedRequest->researchClassGroup || (int) $lockedRequest->researchClassGroup->adviser_id !== (int) $adviser->id) {
                    throw new ConsultationException('You are not the currently assigned adviser for this Research Group.');
                }

                $consultedAt = $data['consulted_at'] ?? now();
                $durationMinutes = (int) ($data['duration_minutes'] ?? $lockedRequest->duration_minutes);
                $groupMemberUserIds = ResearchClassGroupMember::query()
                    ->where('research_class_group_id', $lockedRequest->research_class_group_id)
                    ->pluck('student_id')
                    ->all();

                $submittedAttendeeIds = (array) ($data['attendees'] ?? []);
                foreach ($submittedAttendeeIds as $attId) {
                    if (! in_array((int) $attId, $groupMemberUserIds, true)) {
                        throw new ConsultationException('One or more selected attendees do not belong to this Research Group.');
                    }
                }

                $record = ConsultationRecord::query()->create([
                    'consultation_request_id' => $lockedRequest->id,
                    'research_class_group_id' => $lockedRequest->research_class_group_id,
                    'conducted_by' => $adviser->id,
                    'consulted_at' => $consultedAt,
                    'duration_minutes' => $durationMinutes,
                    'consultation_mode' => $lockedRequest->consultation_mode->value,
                    'location' => $lockedRequest->location,
                    'meeting_url' => $lockedRequest->meeting_url,
                    'agenda' => $lockedRequest->agenda,
                    'discussion' => $discussion,
                    'recommendations' => ! empty($data['recommendations']) ? trim($data['recommendations']) : null,
                    'next_consultation_at' => $data['next_consultation_at'] ?? null,
                ]);

                foreach ($groupMemberUserIds as $studentUserId) {
                    ConsultationAttendance::query()->create([
                        'consultation_record_id' => $record->id,
                        'student_id' => $studentUserId,
                        'attended' => in_array((int) $studentUserId, array_map('intval', $submittedAttendeeIds), true),
                    ]);
                }

                $lockedRequest->update([
                    'status' => ConsultationStatus::Completed,
                ]);

                ConsultationAudit::query()->create([
                    'consultation_request_id' => $lockedRequest->id,
                    'research_class_group_id' => $lockedRequest->research_class_group_id,
                    'actor_id' => $adviser->id,
                    'action' => 'consultation_completed',
                    'status' => ConsultationStatus::Completed->value,
                    'occurred_at' => now(),
                    'metadata' => [
                        'record_id' => $record->id,
                        'attendees_count' => count($submittedAttendeeIds),
                    ],
                ]);

                $this->notifications->sendToMany(
                    recipients: User::query()->whereIn('id', $groupMemberUserIds)->get(),
                    eventKey: 'consultation.completed',
                    title: 'Consultation record completed',
                    message: 'Your adviser recorded the completed consultation and its recommendations.',
                    category: 'consultation',
                    routeName: 'student.dashboard',
                    routeParameters: ['tab' => 'consultation'],
                    sourceType: ConsultationRecord::class,
                    sourceId: $record->getKey(),
                    actor: $adviser,
                    contextLabel: $lockedRequest->researchClassGroup?->name,
                    actingAs: 'Student Researcher',
                );

                return $record->fresh(['request', 'researchClassGroup', 'conductedBy', 'attendances.student']);
            }, 3);
        } catch (ConsultationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            throw new ConsultationException('The consultation record could not be saved. Please try again.');
        }
    }
}
