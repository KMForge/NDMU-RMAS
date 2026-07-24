<?php

namespace App\Modules\Consultations\Actions;

use App\Models\ConsultationRequest;
use App\Models\User;
use App\Modules\Consultations\Exceptions\ConsultationReviewException;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecordCompletedConsultation
{
    /**
     * @param  array{
     *     consulted_at: string,
     *     location: ?string,
     *     meeting_url: ?string,
     *     discussion: string,
     *     recommendations: ?string,
     *     next_consultation_at: ?string
     * }  $data
     */
    public function handle(
        User $adviser,
        ConsultationRequest $consultationRequest,
        array $data,
    ): int {
        if (! Schema::hasTable('consultation_records')) {
            throw new ConsultationReviewException(
                'Consultation records are not available. Please contact the system administrator.',
            );
        }

        try {
            return DB::transaction(function () use (
                $adviser,
                $consultationRequest,
                $data,
            ): int {
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

                if ($lockedRequest->status !== 'approved') {
                    throw new ConsultationReviewException(
                        'Only an approved consultation can be marked as completed.',
                    );
                }

                $timezone = config('ndmu-rmas.timezone');
                $recordId = DB::table('consultation_records')->insertGetId([
                    'research_project_id' => $lockedRequest->research_project_id,
                    'adviser_assignment_id' => $lockedRequest->adviser_assignment_id,
                    'conducted_by' => $adviser->getKey(),
                    'consulted_at' => CarbonImmutable::createFromFormat(
                        'Y-m-d\TH:i',
                        $data['consulted_at'],
                        $timezone,
                    ),
                    'consultation_mode' => $lockedRequest->consultation_mode,
                    'location' => $data['location'],
                    'meeting_url' => $data['meeting_url'],
                    'agenda' => $lockedRequest->agenda,
                    'discussion' => $data['discussion'],
                    'recommendations' => $data['recommendations'],
                    'next_consultation_at' => $data['next_consultation_at'] === null
                        ? null
                        : CarbonImmutable::createFromFormat(
                            'Y-m-d\TH:i',
                            $data['next_consultation_at'],
                            $timezone,
                        ),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $lockedRequest->update(['status' => 'completed']);

                return $recordId;
            }, 3);
        } catch (ConsultationReviewException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            throw new ConsultationReviewException(
                'The consultation record could not be saved. Please try again.',
            );
        }
    }
}
