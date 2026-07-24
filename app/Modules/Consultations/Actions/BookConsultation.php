<?php

namespace App\Modules\Consultations\Actions;

use App\Models\ConsultationRequest;
use App\Models\User;
use App\Modules\Consultations\Exceptions\ConsultationBookingUnavailable;
use App\Modules\Consultations\Exceptions\DuplicateConsultationRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BookConsultation
{
    public function handle(
        User $student,
        string $requestToken,
        CarbonImmutable $preferredAt,
        string $mode,
        string $agenda,
    ): ConsultationRequest {
        $lock = Cache::lock("consultation-booking:{$student->getKey()}:{$requestToken}", 30);

        if (! $lock->get()) {
            throw new DuplicateConsultationRequest;
        }

        try {
            $existing = ConsultationRequest::query()
                ->where('requested_by', $student->getKey())
                ->where('request_token', $requestToken)
                ->exists();

            if ($existing) {
                throw new DuplicateConsultationRequest;
            }

            $project = $this->studentProject($student);

            if ($project === null) {
                throw new ConsultationBookingUnavailable(
                    'You need an active research project before booking a consultation.',
                );
            }

            $adviserAssignment = DB::table('adviser_assignments')
                ->where('research_project_id', $project->id)
                ->where('status', 'active')
                ->whereNull('ended_at')
                ->latest('assigned_at')
                ->first();

            if ($adviserAssignment === null) {
                throw new ConsultationBookingUnavailable(
                    'No active adviser is assigned to your research project.',
                );
            }

            return DB::transaction(fn (): ConsultationRequest => ConsultationRequest::query()->create([
                'research_project_id' => $project->id,
                'adviser_assignment_id' => $adviserAssignment->id,
                'requested_by' => $student->getKey(),
                'request_token' => $requestToken,
                'preferred_at' => $preferredAt,
                'consultation_mode' => $mode,
                'agenda' => $agenda,
                'status' => 'pending',
            ]), 3);
        } finally {
            $lock->release();
        }
    }

    private function studentProject(User $student): ?object
    {
        if (! $this->tablesExist([
            'student_profiles',
            'research_group_members',
            'research_projects',
            'adviser_assignments',
        ])) {
            return null;
        }

        $studentProfile = DB::table('student_profiles')
            ->where('user_id', $student->getKey())
            ->first();

        $groupIds = $studentProfile === null
            ? collect()
            : DB::table('research_group_members')
                ->where('student_profile_id', $studentProfile->id)
                ->whereNull('left_at')
                ->pluck('research_group_id');

        return DB::table('research_projects')
            ->whereNull('archived_at')
            ->where(function ($query) use ($groupIds, $student): void {
                $query->where('created_by', $student->getKey());

                if ($groupIds->isNotEmpty()) {
                    $query->orWhereIn('research_group_id', $groupIds);
                }
            })
            ->latest('updated_at')
            ->first();
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function tablesExist(array $tables): bool
    {
        return collect($tables)->every(
            fn (string $table): bool => Schema::hasTable($table),
        );
    }
}
