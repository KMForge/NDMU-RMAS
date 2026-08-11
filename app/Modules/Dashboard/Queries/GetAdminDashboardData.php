<?php

namespace App\Modules\Dashboard\Queries;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\ResearchProposal;
use App\Models\RevisionRequest;
use App\Models\User;
use App\Support\CachesDatabaseSchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GetAdminDashboardData
{
    use CachesDatabaseSchema;

    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $research = $this->researchData();
        $revisions = $this->revisionData();

        return [
            ...$research,
            ...$revisions,
            'staffList' => $this->staffList(),
            'defensesList' => $this->defensesList(),
            'repositoryList' => [],
            'proposalsList' => $this->proposalsList(),
            'adviserOptions' => $this->staffOptions('classes.serve-as-adviser'),
            'panelistOptions' => $this->staffOptions('evaluations.create'),
            'pendingActions' => $this->pendingActions(),
            'securityOverview' => $this->securityOverview(),
            'systemHealth' => $this->systemHealth(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pendingActions(): array
    {
        $pendingStudents = User::query()
            ->where(function ($query): void {
                $query->where('user_type', 'student')
                    ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'student-researcher'));
            })
            ->where('status', 'pending')
            ->count();
        $pendingDocuments = $this->tableExists('documents')
            ? DB::table('documents')->whereIn('status', ['pending', 'submitted', 'under_review'])->count()
            : 0;
        $pendingProposals = $this->tableExists('research_proposals')
            ? DB::table('research_proposals')->whereIn('status', ['pending', 'submitted', 'under_review'])->count()
            : 0;
        $pendingJoinRequests = $this->tableExists('research_class_enrollments')
            ? DB::table('research_class_enrollments')->where('status', 'pending')->count()
            : 0;
        $pendingDefenses = $this->tableExists('defense_requests')
            ? DB::table('defense_requests')->whereIn('status', ['pending', 'requested'])->count()
            : 0;
        $overdueRevisions = $this->tableExists('revision_requests')
            ? DB::table('revision_requests')
                ->whereIn('status', ['open', 'in_progress'])
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->count()
            : 0;

        return [
            ['label' => 'Student Registrations', 'count' => $pendingStudents, 'tab' => 'users', 'subtab' => 'pending-students', 'icon' => 'ph-user-plus', 'tone' => 'amber'],
            ['label' => 'Document Reviews', 'count' => $pendingDocuments, 'tab' => 'repository', 'subtab' => null, 'icon' => 'ph-file-text', 'tone' => 'purple'],
            ['label' => 'Proposal Reviews', 'count' => $pendingProposals, 'tab' => 'forms', 'subtab' => null, 'icon' => 'ph-clipboard-text', 'tone' => 'blue'],
            ['label' => 'Class Join Requests', 'count' => $pendingJoinRequests, 'tab' => 'users', 'subtab' => 'all-users', 'icon' => 'ph-users-three', 'tone' => 'emerald'],
            ['label' => 'Defense Requests', 'count' => $pendingDefenses, 'tab' => 'defenses', 'subtab' => null, 'icon' => 'ph-calendar', 'tone' => 'red'],
            ['label' => 'Overdue Revisions', 'count' => $overdueRevisions, 'tab' => 'audit', 'subtab' => null, 'icon' => 'ph-warning', 'tone' => 'orange'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function securityOverview(): array
    {
        $suspendedAccounts = User::query()->where('status', 'suspended')->count();
        $unverifiedAccounts = User::query()->whereNull('email_verified_at')->count();
        $accountsWithoutRoles = User::query()->doesntHave('roles')->count();
        $failedUploads = $this->tableExists('document_upload_audits')
            ? DB::table('document_upload_audits')
                ->where('upload_status', 'failed')
                ->where('attempted_at', '>=', now()->subDay())
                ->count()
            : 0;

        return [
            ['label' => 'Suspended Accounts', 'count' => $suspendedAccounts, 'detail' => 'Access currently blocked', 'attention' => $suspendedAccounts > 0, 'icon' => 'ph-user-minus'],
            ['label' => 'Unverified Accounts', 'count' => $unverifiedAccounts, 'detail' => 'Email verification pending', 'attention' => $unverifiedAccounts > 0, 'icon' => 'ph-envelope-simple'],
            ['label' => 'Accounts Without Roles', 'count' => $accountsWithoutRoles, 'detail' => 'No system access role assigned', 'attention' => $accountsWithoutRoles > 0, 'icon' => 'ph-shield-warning'],
            ['label' => 'Failed Uploads (24h)', 'count' => $failedUploads, 'detail' => 'Rejected or failed attempts', 'attention' => $failedUploads > 0, 'icon' => 'ph-file-x'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function systemHealth(): array
    {
        $databaseHealthy = $this->databaseIsHealthy();
        $storageHealthy = $this->privateStorageIsHealthy();
        $failedJobs = $this->tableExists('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

        return [
            ['label' => 'Database', 'status' => $databaseHealthy ? 'Operational' : 'Unavailable', 'healthy' => $databaseHealthy, 'detail' => 'Application database connection', 'icon' => 'ph-database'],
            ['label' => 'Private Storage', 'status' => $storageHealthy ? 'Operational' : 'Unavailable', 'healthy' => $storageHealthy, 'detail' => 'Protected document storage', 'icon' => 'ph-lock-key'],
            ['label' => 'Queue Processing', 'status' => $failedJobs === 0 ? 'Operational' : 'Needs Attention', 'healthy' => $failedJobs === 0, 'detail' => $failedJobs === 0 ? 'No failed jobs' : "{$failedJobs} failed job(s)", 'icon' => 'ph-stack'],
        ];
    }

    private function databaseIsHealthy(): bool
    {
        try {
            DB::select('select 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function privateStorageIsHealthy(): bool
    {
        try {
            $disk = (string) config('ndmu-rmas.document.storage_disk', 'local');
            Storage::disk($disk)->files('', false);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function researchData(): array
    {
        $emptyLifecycle = [
            'title' => null,
            'progress' => 0,
            'completed' => 0,
            'in_progress' => 0,
            'pending' => 0,
            'milestones' => [],
        ];
        $empty = [
            'activeResearchCount' => 0,
            'completedResearchCount' => 0,
            'totalResearchCount' => 0,
            'researchCompletionRate' => 0,
            'researchInProgressRate' => 0,
            'averageResearchMonths' => null,
            'researchByProgram' => [],
            'monthlyResearchSubmissions' => $this->emptyMonths(),
            'researchLifecycle' => $emptyLifecycle,
        ];

        if (! $this->tableExists('research_projects')) {
            return $empty;
        }

        $projects = DB::table('research_projects')
            ->select(['id', 'research_group_id', 'title', 'status', 'completed_at', 'archived_at', 'created_at', 'updated_at'])
            ->get();
        $total = $projects->count();
        $completed = $projects->filter(fn (object $project): bool => $project->completed_at !== null || in_array($project->status, ['completed', 'archived'], true)
        );
        $active = $projects->filter(fn (object $project): bool => $project->archived_at === null
            && $project->completed_at === null
            && ! in_array($project->status, ['completed', 'archived', 'rejected'], true)
        );
        $durations = $completed
            ->filter(fn (object $project): bool => $project->completed_at !== null && $project->created_at !== null)
            ->map(function (object $project): float {
                $start = Carbon::parse($project->created_at);
                $end = Carbon::parse($project->completed_at);

                return round($start->floatDiffInMonths($end), 1);
            });

        return [
            'activeResearchCount' => $active->count(),
            'completedResearchCount' => $completed->count(),
            'totalResearchCount' => $total,
            'researchCompletionRate' => $total > 0 ? (int) round(($completed->count() / $total) * 100) : 0,
            'researchInProgressRate' => $total > 0 ? (int) round(($active->count() / $total) * 100) : 0,
            'averageResearchMonths' => $durations->isEmpty() ? null : round((float) $durations->average(), 1),
            'researchByProgram' => $this->researchByProgram(),
            'monthlyResearchSubmissions' => $this->monthlyResearchSubmissions($projects),
            'researchLifecycle' => $this->researchLifecycle(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function researchLifecycle(): array
    {
        $empty = [
            'title' => null,
            'progress' => 0,
            'completed' => 0,
            'in_progress' => 0,
            'pending' => 0,
            'milestones' => [],
        ];
        if (! $this->tablesExist(['research_class_groups', 'research_group_milestones', 'milestone_definitions'])) {
            return $empty;
        }

        $group = DB::table('research_class_groups')
            ->latest('updated_at')
            ->first();

        if ($group === null) {
            return $empty;
        }

        $milestones = $this->milestonesForGroup((int) $group->id);
        $completed = $milestones->where('status', 'completed')->count();
        $inProgress = $milestones->where('status', 'in_progress')->count();
        $applicable = $milestones->where('status', '!=', 'not_applicable');
        $denominator = (float) $applicable->sum('weight');
        $completedWeight = (float) $applicable->where('status', 'completed')->sum('weight');
        $progress = $denominator > 0 ? (int) round(($completedWeight / $denominator) * 100) : 0;

        return [
            'title' => $group->research_title ?: $group->name,
            'progress' => max(0, min(100, $progress)),
            'completed' => $completed,
            'in_progress' => $inProgress,
            'pending' => $milestones->where('status', 'pending')->count(),
            'milestones' => $milestones->values()->all(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function milestonesForGroup(int $groupId): Collection
    {
        return DB::table('research_group_milestones as progress')
            ->join('milestone_definitions as definitions', 'definitions.id', '=', 'progress.milestone_definition_id')
            ->where('progress.research_class_group_id', $groupId)
            ->where('definitions.is_active', true)
            ->orderBy('definitions.sequence')
            ->select([
                'progress.id', 'progress.status', 'progress.due_at',
                'definitions.name', 'definitions.description', 'definitions.weight',
            ])
            ->get()
            ->map(fn (object $milestone): array => [
                'id' => (int) $milestone->id,
                'name' => $milestone->name,
                'description' => $milestone->description,
                'due_at' => $milestone->due_at === null
                    ? null
                    : Carbon::parse($milestone->due_at)->format('M j, Y'),
                'status' => $milestone->status,
                'weight' => (float) $milestone->weight,
            ]);
    }

    /**
     * @return array<int, array{name: string, count: int, percentage: int}>
     */
    private function researchByProgram(): array
    {
        if (! $this->tablesExist(['research_groups', 'programs'])) {
            return [];
        }

        $rows = DB::table('research_projects as projects')
            ->join('research_groups as groups', 'groups.id', '=', 'projects.research_group_id')
            ->join('programs', 'programs.id', '=', 'groups.program_id')
            ->selectRaw('programs.name, COUNT(projects.id) as aggregate')
            ->groupBy('programs.id', 'programs.name')
            ->orderByDesc('aggregate')
            ->get();
        $maximum = max(1, (int) $rows->max('aggregate'));

        return $rows->map(fn (object $row): array => [
            'name' => $row->name,
            'count' => (int) $row->aggregate,
            'percentage' => (int) round(((int) $row->aggregate / $maximum) * 100),
        ])->all();
    }

    /**
     * @param  Collection<int, object>  $projects
     * @return array<int, array{label: string, count: int, percentage: int}>
     */
    private function monthlyResearchSubmissions(Collection $projects): array
    {
        $year = now()->year;
        $counts = $projects
            ->filter(fn (object $project): bool => Carbon::parse($project->created_at)->year === $year)
            ->countBy(fn (object $project): int => Carbon::parse($project->created_at)->month);
        $maximum = max(1, (int) $counts->max());

        return collect(range(1, 12))->map(fn (int $month): array => [
            'label' => Carbon::create($year, $month)->format('M'),
            'count' => (int) $counts->get($month, 0),
            'percentage' => (int) round(((int) $counts->get($month, 0) / $maximum) * 100),
        ])->all();
    }

    /**
     * @return array<int, array{label: string, count: int, percentage: int}>
     */
    private function emptyMonths(): array
    {
        return collect(range(1, 12))->map(fn (int $month): array => [
            'label' => Carbon::create(now()->year, $month)->format('M'),
            'count' => 0,
            'percentage' => 0,
        ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staffList(): array
    {
        return User::query()
            ->whereHas('roles.permissions', fn ($query) => $query->whereIn('name', [
                'classes.serve-as-adviser',
                'dashboards.facilitator.view',
            ]))
            ->with(['roles:id,name', 'permissions:id,name'])
            ->orderBy('name')
            ->get()
            ->map(function (User $user): array {
                $role = $user->can('classes.serve-as-adviser')
                    ? 'Research Adviser'
                    : 'Research Facilitator';
                $capabilities = [
                    'paper' => $user->can('research.view-assigned'),
                    'evaluation' => $user->can('evaluations.create'),
                    'defense' => $user->can('evaluations.view-assigned'),
                    'schedule' => $user->can('defenses.view'),
                    'recommendations' => $user->can('revisions.create'),
                ];

                if ($role === 'Research Adviser') {
                    $capabilities += [
                        'users' => $user->can('users.manage'),
                        'stats' => $user->can('reports.view'),
                        'screening' => $user->can('documents.review'),
                    ];
                }

                return [
                    'id' => (int) $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $role,
                    'department' => $user->department,
                    'activeCount' => collect($capabilities)->filter()->count(),
                    'totalCount' => count($capabilities),
                    'tempPassword' => false,
                    'permissions' => $capabilities,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function defensesList(): array
    {
        if (! $this->tablesExist(['defense_requests', 'defense_schedules', 'defense_rooms', 'research_projects'])) {
            return [];
        }

        $rows = DB::table('defense_requests as requests')
            ->join('research_projects as projects', 'projects.id', '=', 'requests.research_project_id')
            ->leftJoin('defense_schedules as schedules', 'schedules.defense_request_id', '=', 'requests.id')
            ->leftJoin('defense_rooms as rooms', 'rooms.id', '=', 'schedules.room_id')
            ->leftJoin('users as students', 'students.id', '=', 'requests.requested_by')
            ->select([
                'requests.id',
                'requests.research_project_id',
                'requests.defense_type',
                'requests.status as request_status',
                'requests.remarks',
                'projects.title',
                'students.name as student_name',
                'schedules.starts_at',
                'schedules.ends_at',
                'schedules.status as schedule_status',
                'schedules.notes',
                'rooms.name as room_name',
                'rooms.building',
                'rooms.location',
            ])
            ->orderByDesc('schedules.starts_at')
            ->get();
        $advisers = $this->advisersByProject($rows->pluck('research_project_id'));

        $adviserNames = $advisers->all();

        return $rows->map(function (object $row) use ($adviserNames): array {
            $start = $row->starts_at === null ? null : Carbon::parse($row->starts_at);
            $end = $row->ends_at === null ? null : Carbon::parse($row->ends_at);
            $duration = $start !== null && $end !== null
                ? $start->diffForHumans($end, true)
                : '';
            $venue = collect([$row->room_name, $row->building, $row->location])
                ->filter()
                ->unique()
                ->implode(', ');

            return [
                'id' => (int) $row->id,
                'type' => Str::headline((string) $row->defense_type),
                'title' => $row->title,
                'student' => $row->student_name ?? '',
                'date' => $start?->format('Y-m-d') ?? '',
                'time' => $start?->format('H:i') ?? '',
                'duration' => $duration,
                'venue' => $venue,
                'adviser' => (string) ($adviserNames[(int) $row->research_project_id] ?? ''),
                'panelists' => [],
                'status' => Str::headline((string) ($row->schedule_status ?? $row->request_status)),
                'notes' => $row->notes ?? $row->remarks ?? '',
                'generateNotice' => false,
            ];
        })->all();
    }

    /**
     * @param  Collection<int, int>  $projectIds
     * @return Collection<int, string>
     */
    private function advisersByProject(Collection $projectIds): Collection
    {
        if ($projectIds->isEmpty() || ! $this->tablesExist(['adviser_assignments', 'faculty_profiles'])) {
            return collect();
        }

        return DB::table('adviser_assignments as assignments')
            ->join('faculty_profiles as faculty', 'faculty.id', '=', 'assignments.adviser_id')
            ->join('users', 'users.id', '=', 'faculty.user_id')
            ->whereIn('assignments.research_project_id', $projectIds->unique())
            ->where('assignments.status', 'active')
            ->whereNull('assignments.ended_at')
            ->pluck('users.name', 'assignments.research_project_id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function repositoryList(): array
    {
        return Document::query()
            ->with('user:id,name')
            ->latest('submitted_at')
            ->limit(60)
            ->get()
            ->map(function (Document $document): array {
                $status = match ($document->status) {
                    DocumentStatus::Accepted => 'Approved',
                    DocumentStatus::UnderReview => 'For Evaluation',
                    DocumentStatus::RevisionRequested => 'Revisions Requested',
                    DocumentStatus::Rejected => 'Rejected',
                    default => 'Pending Review',
                };
                $statusClass = match ($document->status) {
                    DocumentStatus::Accepted => 'bg-emerald-50 text-emerald-800 border-emerald-100',
                    DocumentStatus::UnderReview => 'bg-purple-50 text-purple-800 border-purple-100',
                    DocumentStatus::RevisionRequested => 'bg-orange-50 text-orange-800 border-orange-100',
                    DocumentStatus::Rejected => 'bg-red-50 text-red-800 border-red-100',
                    default => 'bg-amber-50 text-amber-800 border-amber-100',
                };

                return [
                    'id' => (int) $document->getKey(),
                    'label' => Str::upper($document->file_type),
                    'type' => Str::upper($document->file_type),
                    'formatColor' => $document->file_type === 'pdf'
                        ? 'text-red-500 bg-red-50'
                        : 'text-blue-500 bg-blue-50',
                    'status' => $status,
                    'statusClass' => $statusClass,
                    'title' => $document->original_filename,
                    'description' => '',
                    'size' => $document->formattedFileSize(),
                    'date' => $document->submitted_at?->format('M j, Y') ?? '',
                    'author' => $document->user?->name ?? '',
                    'viewUrl' => route('documents.view', $document),
                    'downloadUrl' => route('documents.download', $document),
                ];
            })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function proposalsList(): array
    {
        if (! $this->tableExists('research_proposals')) {
            return [];
        }

        return ResearchProposal::query()
            ->with(['submitter:id,name', 'reviewer:id,name', 'document:id,user_id,original_filename'])
            ->latest('submitted_at')
            ->limit(30)
            ->get()
            ->map(fn (ResearchProposal $proposal): array => [
                'id' => (int) $proposal->getKey(),
                'title' => $proposal->title,
                'status' => Str::headline($proposal->status),
                'submitted' => $proposal->submitted_at?->format('F j, Y') ?? '',
                'submitter' => $proposal->submitter?->name ?? '',
                'reviewer' => $proposal->reviewer?->name ?? 'Not reviewed',
                'approvalDate' => $proposal->reviewed_at?->format('F j, Y') ?? '',
                'viewUrl' => $proposal->document === null ? null : route('documents.view', $proposal->document),
                'downloadUrl' => $proposal->document === null ? null : route('documents.download', $proposal->document),
            ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function revisionData(): array
    {
        $empty = [
            'revisionStats' => ['pending' => 0, 'completed' => 0, 'overdue' => 0],
            'revisionHistory' => [],
        ];

        if (! $this->tableExists('revision_requests')) {
            return $empty;
        }

        $scope = RevisionRequest::query();
        $pending = (clone $scope)->whereIn('status', ['open', 'in_progress', 'submitted'])->count();
        $completed = (clone $scope)->where('status', 'resolved')->count();
        $overdue = (clone $scope)
            ->whereIn('status', ['open', 'in_progress'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();
        $history = $scope
            ->with(['requester:id,name', 'assignee:id,name'])
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn (RevisionRequest $revision): array => [
                'id' => (int) $revision->getKey(),
                'title' => $revision->title,
                'instructions' => $revision->instructions,
                'status' => Str::headline($revision->status->value),
                'statusValue' => $revision->status->value,
                'requester' => $revision->requester?->name ?? '',
                'assignee' => $revision->assignee?->name ?? '',
                'date' => $revision->created_at?->format('M j, Y') ?? '',
                'dueDate' => $revision->due_at?->format('M j, Y') ?? '',
            ])->all();

        return [
            'revisionStats' => compact('pending', 'completed', 'overdue'),
            'revisionHistory' => $history,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function staffOptions(string $permission): array
    {
        return User::query()
            ->permission($permission)
            ->where('status', 'active')
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}
