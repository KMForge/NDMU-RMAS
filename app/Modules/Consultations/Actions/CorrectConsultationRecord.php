<?php

namespace App\Modules\Consultations\Actions;

use App\Models\ConsultationAttendance;
use App\Models\ConsultationAudit;
use App\Models\ConsultationRecord;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Modules\Consultations\Exceptions\ConsultationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CorrectConsultationRecord
{
    /**
     * @param  array{
     *     discussion: string,
     *     recommendations?: ?string,
     *     correction_reason: string,
     *     attendees?: array<int, int>
     * }  $data
     */
    public function handle(User $adviser, ConsultationRecord $record, array $data): ConsultationRecord
    {
        if (! $adviser->can('consultations.manage-assigned')) {
            throw new ConsultationException('You do not have permission to manage consultations.');
        }

        $discussion = trim($data['discussion'] ?? '');
        $reason = trim($data['correction_reason'] ?? '');

        if ($discussion === '') {
            throw new ConsultationException('Consultation discussion notes are required.');
        }

        if ($reason === '') {
            throw new ConsultationException('A valid correction reason is required.');
        }

        try {
            return DB::transaction(function () use ($adviser, $record, $data, $discussion, $reason): ConsultationRecord {
                $originalRecord = ConsultationRecord::query()
                    ->with('researchClassGroup')
                    ->whereKey($record->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($originalRecord->is_superseded) {
                    throw new ConsultationException('Cannot correct a record that has already been superseded.');
                }

                if (! $originalRecord->researchClassGroup || (int) $originalRecord->researchClassGroup->adviser_id !== (int) $adviser->id) {
                    throw new ConsultationException('You are not the currently assigned adviser for this Research Group.');
                }

                $groupMemberUserIds = ResearchClassGroupMember::query()
                    ->where('research_class_group_id', $originalRecord->research_class_group_id)
                    ->pluck('student_id')
                    ->all();

                $submittedAttendeeIds = (array) ($data['attendees'] ?? []);
                foreach ($submittedAttendeeIds as $attId) {
                    if (! in_array((int) $attId, $groupMemberUserIds, true)) {
                        throw new ConsultationException('One or more selected attendees do not belong to this Research Group.');
                    }
                }

                $originalRecord->update(['is_superseded' => true]);

                $correctedRecord = ConsultationRecord::query()->create([
                    'consultation_request_id' => $originalRecord->consultation_request_id,
                    'research_class_group_id' => $originalRecord->research_class_group_id,
                    'conducted_by' => $originalRecord->conducted_by,
                    'consulted_at' => $originalRecord->consulted_at,
                    'duration_minutes' => $originalRecord->duration_minutes,
                    'consultation_mode' => $originalRecord->consultation_mode->value,
                    'location' => $originalRecord->location,
                    'meeting_url' => $originalRecord->meeting_url,
                    'agenda' => $originalRecord->agenda,
                    'discussion' => $discussion,
                    'recommendations' => ! empty($data['recommendations']) ? trim($data['recommendations']) : null,
                    'next_consultation_at' => $originalRecord->next_consultation_at,
                    'supersedes_record_id' => $originalRecord->id,
                    'is_superseded' => false,
                    'correction_reason' => $reason,
                ]);

                foreach ($groupMemberUserIds as $studentUserId) {
                    ConsultationAttendance::query()->create([
                        'consultation_record_id' => $correctedRecord->id,
                        'student_id' => $studentUserId,
                        'attended' => in_array((int) $studentUserId, array_map('intval', $submittedAttendeeIds), true),
                    ]);
                }

                ConsultationAudit::query()->create([
                    'consultation_request_id' => $originalRecord->consultation_request_id,
                    'research_class_group_id' => $originalRecord->research_class_group_id,
                    'actor_id' => $adviser->id,
                    'action' => 'consultation_record_corrected',
                    'status' => 'completed',
                    'occurred_at' => now(),
                    'metadata' => [
                        'original_record_id' => $originalRecord->id,
                        'corrected_record_id' => $correctedRecord->id,
                        'correction_reason' => $reason,
                    ],
                ]);

                return $correctedRecord->fresh(['request', 'researchClassGroup', 'conductedBy', 'supersedes', 'attendances.student']);
            }, 3);
        } catch (ConsultationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            throw new ConsultationException('The consultation record correction could not be saved. Please try again.');
        }
    }
}
