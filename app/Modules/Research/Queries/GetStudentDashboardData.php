<?php

namespace App\Modules\Research\Queries;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GetStudentDashboardData
{
    /**
     * @return array<string, mixed>
     */
    public function for(User $user): array
    {
        $empty = collect();
        $studentProfile = null;
        $project = null;
        $program = null;
        $team = $empty;
        $adviser = null;
        $proposals = $empty;
        $progress = $empty;
        $consultations = $empty;
        $consultationRequests = $empty;
        $revisions = $empty;
        $defenses = $empty;
        $evaluations = $empty;

        if ($this->tablesExist(['research_projects', 'student_profiles', 'research_group_members'])) {
            $studentProfile = DB::table('student_profiles')
                ->where('user_id', $user->getKey())
                ->first();

            $groupIds = $studentProfile === null
                ? $empty
                : DB::table('research_group_members')
                    ->where('student_profile_id', $studentProfile->id)
                    ->whereNull('left_at')
                    ->pluck('research_group_id');

            $project = DB::table('research_projects')
                ->whereNull('archived_at')
                ->where(function ($query) use ($groupIds, $user): void {
                    $query->where('created_by', $user->getKey());

                    if ($groupIds->isNotEmpty()) {
                        $query->orWhereIn('research_group_id', $groupIds);
                    }
                })
                ->latest('updated_at')
                ->first();

            if ($project !== null) {
                $program = $this->programFor((int) $project->research_group_id);
                $team = $this->teamFor((int) $project->research_group_id);
                $adviser = $this->adviserFor((int) $project->id);
                $proposals = $this->proposalsFor((int) $project->id);
                $progress = $this->progressFor((int) $project->id);
                $consultations = $this->consultationsFor((int) $project->id);
                $consultationRequests = $this->consultationRequestsFor((int) $project->id, $user);
                $revisions = $this->revisionsFor((int) $project->id);
                $defenses = $this->defensesFor((int) $project->id);
                $evaluations = $this->evaluationsFor((int) $project->id);
            }
        }

        $documents = Document::query()
            ->whereBelongsTo($user)
            ->latest('submitted_at')
            ->get();

        $notifications = Schema::hasTable('notifications')
            ? $user->notifications()->latest()->limit(25)->get()
            : $empty;

        return [
            'area' => 'Student Researcher',
            'student' => $user,
            'studentProfile' => $studentProfile,
            'researchProject' => $project,
            'program' => $program,
            'teamMembers' => $team,
            'adviser' => $adviser,
            'proposals' => $proposals,
            'progressUpdates' => $progress,
            'consultations' => $consultations,
            'consultationRequests' => $consultationRequests,
            'revisions' => $revisions,
            'defenses' => $defenses,
            'evaluations' => $evaluations,
            'documents' => $documents,
            'notifications' => $notifications,
            'classes' => $empty,
        ];
    }

    private function programFor(int $researchGroupId): ?object
    {
        if (! $this->tablesExist(['research_groups', 'programs'])) {
            return null;
        }

        return DB::table('research_groups as groups')
            ->join('programs', 'programs.id', '=', 'groups.program_id')
            ->where('groups.id', $researchGroupId)
            ->select(['programs.id', 'programs.code', 'programs.name', 'programs.degree_level'])
            ->first();
    }

    /**
     * @return Collection<int, object>
     */
    private function teamFor(int $researchGroupId): Collection
    {
        if (! $this->tablesExist(['research_group_members', 'student_profiles', 'users'])) {
            return collect();
        }

        return DB::table('research_group_members as members')
            ->join('student_profiles as profiles', 'profiles.id', '=', 'members.student_profile_id')
            ->join('users', 'users.id', '=', 'profiles.user_id')
            ->where('members.research_group_id', $researchGroupId)
            ->whereNull('members.left_at')
            ->orderBy('members.joined_at')
            ->select([
                'users.id as user_id',
                'users.name',
                'profiles.student_number',
                'members.member_role',
            ])
            ->get();
    }

    private function adviserFor(int $researchProjectId): ?object
    {
        if (! $this->tablesExist(['adviser_assignments', 'faculty_profiles', 'users'])) {
            return null;
        }

        return DB::table('adviser_assignments as assignments')
            ->join('faculty_profiles as faculty', 'faculty.id', '=', 'assignments.adviser_id')
            ->join('users', 'users.id', '=', 'faculty.user_id')
            ->where('assignments.research_project_id', $researchProjectId)
            ->where('assignments.status', 'active')
            ->whereNull('assignments.ended_at')
            ->latest('assignments.assigned_at')
            ->select(['users.id as user_id', 'users.name', 'faculty.academic_rank'])
            ->first();
    }

    /**
     * @return Collection<int, object>
     */
    private function proposalsFor(int $researchProjectId): Collection
    {
        if (! Schema::hasTable('research_proposals')) {
            return collect();
        }

        return DB::table('research_proposals')
            ->where('research_project_id', $researchProjectId)
            ->latest('version')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function progressFor(int $researchProjectId): Collection
    {
        if (! $this->tablesExist(['research_progress_updates', 'research_milestones'])) {
            return collect();
        }

        return DB::table('research_progress_updates as updates')
            ->join('research_milestones as milestones', 'milestones.id', '=', 'updates.milestone_id')
            ->where('updates.research_project_id', $researchProjectId)
            ->orderBy('milestones.sequence')
            ->select([
                'updates.*',
                'milestones.name as milestone_name',
                'milestones.description as milestone_description',
                'milestones.due_at',
                'milestones.sequence',
            ])
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function consultationsFor(int $researchProjectId): Collection
    {
        if (! $this->tablesExist(['consultation_records', 'users'])) {
            return collect();
        }

        return DB::table('consultation_records as consultations')
            ->leftJoin('users as facilitators', 'facilitators.id', '=', 'consultations.conducted_by')
            ->where('consultations.research_project_id', $researchProjectId)
            ->latest('consultations.consulted_at')
            ->select(['consultations.*', 'facilitators.name as facilitator_name'])
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function consultationRequestsFor(int $researchProjectId, User $user): Collection
    {
        if (! $this->tablesExist([
            'consultation_requests',
            'adviser_assignments',
            'faculty_profiles',
            'users',
        ])) {
            return collect();
        }

        return DB::table('consultation_requests as requests')
            ->join('adviser_assignments as assignments', 'assignments.id', '=', 'requests.adviser_assignment_id')
            ->join('faculty_profiles as faculty', 'faculty.id', '=', 'assignments.adviser_id')
            ->join('users as advisers', 'advisers.id', '=', 'faculty.user_id')
            ->where('requests.research_project_id', $researchProjectId)
            ->where('requests.requested_by', $user->getKey())
            ->latest('requests.preferred_at')
            ->select([
                'requests.id',
                'requests.preferred_at',
                'requests.consultation_mode',
                'requests.agenda',
                'requests.status',
                'requests.review_notes',
                'advisers.name as adviser_name',
            ])
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function revisionsFor(int $researchProjectId): Collection
    {
        if (! Schema::hasTable('revision_requests')) {
            return collect();
        }

        return DB::table('revision_requests')
            ->where('research_project_id', $researchProjectId)
            ->latest('created_at')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function defensesFor(int $researchProjectId): Collection
    {
        if (! $this->tablesExist(['defense_requests', 'defense_schedules', 'defense_rooms'])) {
            return collect();
        }

        return DB::table('defense_requests as requests')
            ->leftJoin('defense_schedules as schedules', 'schedules.defense_request_id', '=', 'requests.id')
            ->leftJoin('defense_rooms as rooms', 'rooms.id', '=', 'schedules.room_id')
            ->where('requests.research_project_id', $researchProjectId)
            ->orderByDesc('schedules.starts_at')
            ->select([
                'requests.id as request_id',
                'requests.defense_type',
                'requests.status as request_status',
                'requests.preferred_date',
                'schedules.id as schedule_id',
                'schedules.starts_at',
                'schedules.ends_at',
                'schedules.status as schedule_status',
                'schedules.meeting_url',
                'rooms.name as room_name',
                'rooms.building',
                'rooms.location',
            ])
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function evaluationsFor(int $researchProjectId): Collection
    {
        if (! $this->tablesExist(['evaluations', 'defense_schedules', 'defense_requests'])) {
            return collect();
        }

        return DB::table('evaluations')
            ->join('defense_schedules', 'defense_schedules.id', '=', 'evaluations.defense_schedule_id')
            ->join('defense_requests', 'defense_requests.id', '=', 'defense_schedules.defense_request_id')
            ->where('defense_requests.research_project_id', $researchProjectId)
            ->latest('evaluations.submitted_at')
            ->select([
                'evaluations.*',
                'defense_requests.defense_type',
                'defense_schedules.starts_at',
            ])
            ->get();
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
