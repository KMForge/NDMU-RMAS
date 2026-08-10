<?php

namespace App\Modules\Research\Queries;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Support\DocumentGroupAccess;
use App\Support\CachesDatabaseSchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GetStudentDashboardData
{
    use CachesDatabaseSchema;

    public function __construct(
        private readonly DocumentGroupAccess $documentGroupAccess,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(User $user, mixed $dashboardSearch = null, string $activeTab = 'dashboard'): array
    {
        $empty = collect();
        $searchQuery = $activeTab === 'dashboard'
            ? Str::limit(trim(is_string($dashboardSearch) ? $dashboardSearch : ''), 100, '')
            : '';

        $studentProfile = null;
        $project = null;
        $program = null;
        $team = $empty;
        $adviser = null;
        $proposals = $empty;
        $progress = $empty;
        $milestones = $empty;
        $consultations = $empty;
        $consultationRequests = $empty;
        $revisions = $empty;
        $defenses = $empty;
        $evaluations = $empty;

        $isDashboard = $activeTab === 'dashboard';
        $searchQuery = $isDashboard
            ? Str::limit(trim(is_string($dashboardSearch) ? $dashboardSearch : ''), 100, '')
            : '';
        $needsProject = $isDashboard || in_array($activeTab, [
            'research',
            'proposal',
            'progress',
            'consultation',
            'revisions',
            'defense',
            'evaluations',
        ], true);

        if ($needsProject && $this->tablesExist(['research_projects', 'student_profiles', 'research_group_members'])) {
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
                if ($activeTab === 'research') {
                    $program = $this->programFor((int) $project->research_group_id);
                    $team = $this->teamFor((int) $project->research_group_id);
                }

                if ($isDashboard || in_array($activeTab, ['research', 'consultation'], true)) {
                    $adviser = $this->adviserFor((int) $project->id);
                }

                if ($activeTab === 'proposal') {
                    $proposals = $this->proposalsFor((int) $project->id);
                }

                if ($isDashboard || $activeTab === 'progress') {
                    $progress = $this->progressFor((int) $project->id);
                    $milestones = $this->milestonesFor(
                        (int) $project->id,
                        (int) $project->research_group_id,
                    );
                }

                if ($isDashboard || $activeTab === 'consultation') {
                    $consultations = $this->consultationsFor((int) $project->id);
                    $consultationRequests = $this->consultationRequestsFor((int) $project->id, $user);
                }

                if ($isDashboard || $activeTab === 'defense') {
                    $defenses = $this->defensesFor((int) $project->id);
                }

                if ($activeTab === 'evaluations') {
                    $evaluations = $this->evaluationsFor((int) $project->id);
                }
            }
        }

        if ($isDashboard || $activeTab === 'revisions') {
            $revisions = $this->revisionsFor(
                (int) $user->getKey(),
                $project === null ? null : (int) $project->id,
            );
        }

        $documentCount = 0;
        $pendingDocumentCount = 0;
        $documents = $empty;
        $documentsForSearch = $empty;
        $activeGroupMember = $this->documentGroupAccess->activeMembershipFor($user);
        $activeGroup = $activeGroupMember?->researchClassGroup;
        $groupDocumentQuery = $activeGroup === null
            ? null
            : Document::query()->where('research_class_group_id', $activeGroup->getKey());

        if ($isDashboard && $groupDocumentQuery !== null) {
            $documentQuery = clone $groupDocumentQuery;
            $documentCount = (clone $documentQuery)->count();
            $pendingDocumentCount = (clone $documentQuery)
                ->whereIn('status', ['pending', 'submitted', 'under_review'])
                ->count();
            $documents = $documentQuery
                ->latest('submitted_at')
                ->limit(5)
                ->get();

            if ($searchQuery !== '') {
                $documentsForSearch = (clone $groupDocumentQuery)
                    ->whereRaw('LOWER(original_filename) LIKE ?', ['%'.Str::lower($searchQuery).'%'])
                    ->latest('submitted_at')
                    ->limit(25)
                    ->get();
            }
        } elseif ($activeTab === 'repository' && $groupDocumentQuery !== null) {
            $documents = (clone $groupDocumentQuery)
                ->latest('submitted_at')
                ->get();
            $documentCount = $documents->count();
            $pendingDocumentCount = $documents
                ->filter(fn (Document $document): bool => in_array(
                    $document->status->value,
                    ['pending', 'submitted', 'under_review'],
                    true,
                ))
                ->count();
        }

        $notifications = $this->tableExists('notifications')
            ? $user->notifications()->latest()->limit(25)->get()
            : $empty;

        $classes = ($isDashboard || $activeTab === 'classes')
            ? $this->classesFor($user)
            : $empty;
        $classJoinRequests = $activeTab === 'classes'
            ? $this->classJoinRequestsFor($user)
            : $empty;
        $dashboard = $this->buildDashboardOverview(
            $project,
            $adviser,
            $milestones,
            $progress,
            $consultations,
            $consultationRequests,
            $revisions,
            $defenses,
            $documents,
            $documentCount,
            $pendingDocumentCount,
        );
        $dashboardSearchResults = $this->searchDashboardRecords(
            $searchQuery,
            $project,
            $milestones,
            $revisions,
            $documentsForSearch,
            $classes,
        );

        $groupDocuments = $groupDocumentQuery !== null
            ? (clone $groupDocumentQuery)
                ->orderByDesc('version_number')
                ->get()
            : collect();

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
            'researchMilestones' => $milestones,
            'consultations' => $consultations,
            'consultationRequests' => $consultationRequests,
            'revisions' => $revisions,
            'defenses' => $defenses,
            'evaluations' => $evaluations,
            'documents' => $documents,
            'activeGroup' => $activeGroup,
            'groupDocuments' => $groupDocuments,
            'notifications' => $notifications,
            'classes' => $classes,
            'classJoinRequests' => $classJoinRequests,
            'dashboardOverview' => $dashboard,
            'dashboardSearchQuery' => $searchQuery,
            'dashboardSearchResults' => $dashboardSearchResults,
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
        if (! $this->tableExists('research_proposals')) {
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
    private function milestonesFor(int $researchProjectId, int $researchGroupId): Collection
    {
        if (! $this->tablesExist([
            'research_groups',
            'research_milestones',
            'research_progress_updates',
        ])) {
            return collect();
        }

        $group = DB::table('research_groups')
            ->select(['program_id', 'academic_term_id'])
            ->find($researchGroupId);

        if ($group === null) {
            return collect();
        }

        $latestVersions = DB::table('research_progress_updates')
            ->where('research_project_id', $researchProjectId)
            ->selectRaw('milestone_id, MAX(version) as latest_version')
            ->groupBy('milestone_id');

        return DB::table('research_milestones as milestones')
            ->leftJoinSub($latestVersions, 'latest_updates', function ($join): void {
                $join->on('latest_updates.milestone_id', '=', 'milestones.id');
            })
            ->leftJoin('research_progress_updates as updates', function ($join) use ($researchProjectId): void {
                $join->on('updates.milestone_id', '=', 'milestones.id')
                    ->on('updates.version', '=', 'latest_updates.latest_version')
                    ->where('updates.research_project_id', $researchProjectId);
            })
            ->where('milestones.program_id', $group->program_id)
            ->where('milestones.academic_term_id', $group->academic_term_id)
            ->orderBy('milestones.sequence')
            ->select([
                'milestones.id',
                'milestones.name',
                'milestones.description',
                'milestones.due_at',
                'milestones.sequence',
                'milestones.is_required',
                'updates.status',
                'updates.progress_percentage',
                'updates.summary',
                'updates.feedback',
                'updates.submitted_at',
                'updates.reviewed_at',
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
                'requests.reviewed_at',
                'requests.created_at',
                'advisers.name as adviser_name',
            ])
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function revisionsFor(int $userId, ?int $researchProjectId): Collection
    {
        if (! $this->tableExists('revision_requests')) {
            return collect();
        }

        $supportsAssignedWorkflow = $this->columnsExist('revision_requests', [
            'assigned_to',
            'document_id',
        ]);

        if (! $supportsAssignedWorkflow && $researchProjectId === null) {
            return collect();
        }

        $query = DB::table('revision_requests')
            ->select('revision_requests.*')
            ->selectRaw('? as workflow_enabled', [$supportsAssignedWorkflow]);

        if (
            $supportsAssignedWorkflow
            && $this->columnExists('documents', 'revision_request_id')
        ) {
            $query->addSelect([
                'latest_document_id' => Document::query()
                    ->select('id')
                    ->whereColumn('revision_request_id', 'revision_requests.id')
                    ->latest('submitted_at')
                    ->limit(1),
                'latest_document_name' => Document::query()
                    ->select('original_filename')
                    ->whereColumn('revision_request_id', 'revision_requests.id')
                    ->latest('submitted_at')
                    ->limit(1),
            ]);
        } else {
            $query->selectRaw('NULL as latest_document_id, NULL as latest_document_name');
        }

        return $query
            ->where(function ($revisionQuery) use (
                $supportsAssignedWorkflow,
                $userId,
                $researchProjectId,
            ): void {
                if ($supportsAssignedWorkflow) {
                    $revisionQuery->where('assigned_to', $userId);
                }

                if ($researchProjectId !== null) {
                    $supportsAssignedWorkflow
                        ? $revisionQuery->orWhere('research_project_id', $researchProjectId)
                        : $revisionQuery->where('research_project_id', $researchProjectId);
                }
            })
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
     * @return Collection<int, object>
     */
    private function classesFor(User $user): Collection
    {
        if (! $this->tablesExist(['research_classes', 'research_class_enrollments', 'users'])) {
            return collect();
        }

        return DB::table('research_class_enrollments as enrollments')
            ->join('research_classes as classes', 'classes.id', '=', 'enrollments.research_class_id')
            ->join('users as facilitators', 'facilitators.id', '=', 'classes.facilitator_id')
            ->where('enrollments.student_id', $user->getKey())
            ->where('enrollments.status', 'active')
            ->where('classes.is_active', true)
            ->latest('enrollments.joined_at')
            ->select([
                'classes.id',
                'classes.name',
                'classes.description',
                'classes.max_students',
                'facilitators.name as facilitator_name',
                'enrollments.joined_at',
            ])
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function classJoinRequestsFor(User $user): Collection
    {
        if (! $this->tablesExist(['research_classes', 'research_class_enrollments', 'users'])) {
            return collect();
        }

        return DB::table('research_class_enrollments as enrollments')
            ->join('research_classes as classes', 'classes.id', '=', 'enrollments.research_class_id')
            ->join('users as facilitators', 'facilitators.id', '=', 'classes.facilitator_id')
            ->where('enrollments.student_id', $user->getKey())
            ->whereIn('enrollments.status', ['pending', 'rejected'])
            ->latest('enrollments.requested_at')
            ->select([
                'enrollments.id',
                'enrollments.status',
                'enrollments.requested_at',
                'enrollments.reviewed_at',
                'classes.name as class_name',
                'facilitators.name as facilitator_name',
            ])
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDashboardOverview(
        ?object $project,
        ?object $adviser,
        Collection $milestones,
        Collection $progress,
        Collection $consultations,
        Collection $consultationRequests,
        Collection $revisions,
        Collection $defenses,
        Collection $documents,
        int $documentCount,
        int $pendingDocumentCount,
    ): array {
        $completedStatuses = ['accepted', 'approved', 'completed', 'resolved'];
        $latestProgress = $progress
            ->filter(fn (object $update): bool => $update->submitted_at !== null)
            ->sortByDesc('submitted_at')
            ->first();
        $completedMilestones = $milestones
            ->filter(fn (object $milestone): bool => in_array($milestone->status, $completedStatuses, true))
            ->count();
        $progressPercentage = (int) round((float) ($latestProgress?->progress_percentage ?? 0));

        if ($latestProgress === null && $milestones->isNotEmpty()) {
            $progressPercentage = (int) round(($completedMilestones / $milestones->count()) * 100);
        }

        $openRevisions = $revisions
            ->reject(fn (object $revision): bool => in_array($revision->status, $completedStatuses, true));
        $actionItems = $openRevisions
            ->map(fn (object $revision): array => [
                'type' => 'revision',
                'title' => $revision->title,
                'description' => $revision->instructions,
                'due_at' => $this->parseDate($revision->due_at),
                'tab' => 'revisions',
            ]);

        $pendingMilestones = $milestones
            ->reject(fn (object $milestone): bool => in_array($milestone->status, $completedStatuses, true));

        foreach ($pendingMilestones as $milestone) {
            $actionItems->push([
                'type' => 'milestone',
                'title' => $milestone->name,
                'description' => $milestone->description,
                'due_at' => $this->parseDate($milestone->due_at),
                'tab' => 'progress',
            ]);
        }

        $actionItems = $actionItems
            ->sortBy(fn (array $item): int => $item['due_at']?->getTimestamp() ?? PHP_INT_MAX)
            ->values();

        $consultationCandidates = collect();

        foreach ($consultations as $consultation) {
            $startsAt = $this->parseDate($consultation->next_consultation_at);

            if ($startsAt?->isFuture()) {
                $consultationCandidates->push([
                    'starts_at' => $startsAt,
                    'ends_at' => null,
                    'adviser_name' => $consultation->facilitator_name ?: $adviser?->name,
                    'mode' => $consultation->consultation_mode,
                    'location' => $consultation->location,
                    'meeting_url' => $consultation->meeting_url,
                ]);
            }
        }

        foreach ($consultationRequests as $consultationRequest) {
            $startsAt = $this->parseDate($consultationRequest->preferred_at);

            if (
                $startsAt?->isFuture()
                && in_array($consultationRequest->status, ['approved', 'confirmed', 'scheduled'], true)
            ) {
                $consultationCandidates->push([
                    'starts_at' => $startsAt,
                    'ends_at' => null,
                    'adviser_name' => $consultationRequest->adviser_name,
                    'mode' => $consultationRequest->consultation_mode,
                    'location' => null,
                    'meeting_url' => null,
                ]);
            }
        }

        $nextConsultation = $consultationCandidates->sortBy('starts_at')->first();
        $nextDefense = $defenses
            ->filter(fn (object $defense): bool => $this->parseDate($defense->starts_at)?->isFuture() === true)
            ->sortBy('starts_at')
            ->first();

        $recentUpdates = collect();

        foreach ($progress as $update) {
            $recentUpdates->push([
                'type' => 'progress',
                'title' => $update->milestone_name,
                'description' => Str::headline((string) $update->status),
                'occurred_at' => $this->parseDate($update->submitted_at),
                'tab' => 'progress',
            ]);
        }

        foreach ($documents as $document) {
            $recentUpdates->push([
                'type' => 'document',
                'title' => $document->original_filename,
                'description' => Str::headline($document->status->value),
                'occurred_at' => $document->submitted_at,
                'tab' => 'repository',
            ]);
        }

        foreach ($revisions as $revision) {
            $recentUpdates->push([
                'type' => 'revision',
                'title' => $revision->title,
                'description' => Str::headline((string) $revision->status),
                'occurred_at' => $this->parseDate($revision->created_at),
                'tab' => 'revisions',
            ]);
        }

        foreach ($consultationRequests as $consultationRequest) {
            $recentUpdates->push([
                'type' => 'consultation',
                'title' => 'Consultation with '.$consultationRequest->adviser_name,
                'description' => Str::headline((string) $consultationRequest->status),
                'occurred_at' => $this->parseDate($consultationRequest->created_at),
                'tab' => 'consultation',
            ]);
        }

        return [
            'project' => $project,
            'progress_percentage' => max(0, min(100, $progressPercentage)),
            'completed_milestones' => $completedMilestones,
            'total_milestones' => $milestones->count(),
            'current_milestones' => $milestones->take(4)->values(),
            'urgent_task_count' => $actionItems->count(),
            'action_items' => $actionItems->take(3)->values(),
            'document_count' => $documentCount,
            'pending_document_count' => $pendingDocumentCount,
            'next_consultation' => $nextConsultation,
            'next_defense' => $nextDefense,
            'recent_updates' => $recentUpdates
                ->filter(fn (array $update): bool => $update['occurred_at'] !== null)
                ->sortByDesc('occurred_at')
                ->take(5)
                ->values(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchDashboardRecords(
        string $search,
        ?object $project,
        Collection $milestones,
        Collection $revisions,
        Collection $documents,
        Collection $classes,
    ): Collection {
        if ($search === '') {
            return collect();
        }

        $records = collect();

        if ($project !== null) {
            $records->push([
                'type' => 'Research',
                'title' => $project->title,
                'description' => $project->abstract,
                'tab' => 'research',
            ]);
        }

        foreach ($milestones as $milestone) {
            $records->push([
                'type' => 'Milestone',
                'title' => $milestone->name,
                'description' => $milestone->description,
                'tab' => 'progress',
            ]);
        }

        foreach ($revisions as $revision) {
            $records->push([
                'type' => 'Revision',
                'title' => $revision->title,
                'description' => $revision->instructions,
                'tab' => 'revisions',
            ]);
        }

        foreach ($documents as $document) {
            $records->push([
                'type' => 'Document',
                'title' => $document->original_filename,
                'description' => Str::headline($document->status->value),
                'tab' => 'repository',
            ]);
        }

        foreach ($classes as $class) {
            $records->push([
                'type' => 'Class',
                'title' => $class->name,
                'description' => 'Facilitator: '.$class->facilitator_name,
                'tab' => 'classes',
            ]);
        }

        $needle = Str::lower($search);

        return $records
            ->filter(fn (array $record): bool => Str::contains(
                Str::lower($record['title'].' '.($record['description'] ?? '')),
                $needle,
            ))
            ->take(10)
            ->values();
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->timezone(config('ndmu-rmas.timezone'));
    }
}
