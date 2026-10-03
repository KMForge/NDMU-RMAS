<?php

namespace App\Modules\Dashboard\Queries;

use App\Enums\DocumentStatus;
use App\Models\Defense;
use App\Models\Document;
use App\Models\ResearchProposal;
use App\Models\RevisionRequest;
use App\Models\User;
use App\Support\CachesDatabaseSchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
        return Cache::remember(
            'admin-dashboard.analytics-data',
            now()->addSeconds(30),
            function (): array {
                $research = $this->researchData();
                $revisions = $this->revisionData();

                return [
                    ...$research,
                    ...$revisions,
                    'staffList' => $this->staffList(),
                    'defensesList' => $this->defensesList(),
                    'repositoryList' => $this->repositoryList(),
                    'proposalsList' => $this->proposalsList(),
                    'adviserOptions' => $this->staffOptions('classes.serve-as-adviser'),
                    'panelistOptions' => $this->staffOptions('evaluations.create'),
                    'pendingActions' => $this->pendingActions(),
                    'securityOverview' => $this->securityOverview(),
                    'systemHealth' => $this->cachedSystemHealth(),
                ];
            },
        );
    }

    /**
     * Return only the data required by the active admin workspace. Hidden
     * workspaces are rendered by Livewire on demand instead of being queried
     * during every dashboard request.
     *
     * @return array<string, mixed>
     */
    public function forTab(string $tab): array
    {
        return match ($tab) {
            'dashboard' => Cache::remember('admin-dashboard.tab.dashboard', now()->addSeconds(30), fn (): array => [
                ...$this->researchData(),
                'pendingActions' => $this->pendingActions(),
                'securityOverview' => $this->securityOverview(),
                'systemHealth' => $this->cachedSystemHealth(),
            ]),
            'research', 'reports' => Cache::remember('admin-dashboard.tab.research', now()->addSeconds(30), fn (): array => [
                ...$this->researchData(),
                ...$this->revisionData(),
            ]),
            'defenses' => Cache::remember('admin-dashboard.tab.defenses', now()->addSeconds(30), fn (): array => [
                'defensesList' => $this->defensesList(),
                'adviserOptions' => $this->staffOptions('classes.serve-as-adviser'),
                'panelistOptions' => $this->staffOptions('evaluations.create'),
            ]),
            'forms' => Cache::remember('admin-dashboard.tab.forms', now()->addSeconds(30), fn (): array => [
                'proposalsList' => $this->proposalsList(),
            ]),
            default => [],
        };
    }

    /** @return array{pending_users:int,active_research:int,pending_defenses:int} */
    public function sidebarSummary(): array
    {
        return Cache::remember('admin-dashboard.sidebar-summary', now()->addSeconds(30), function (): array {
            return [
                'pending_users' => User::query()
                    ->where('user_type', 'student')
                    ->where('status', 'pending')
                    ->count(),
                'active_research' => $this->tableExists('research_projects')
                    ? DB::table('research_projects')
                        ->whereNull('archived_at')
                        ->whereNull('completed_at')
                        ->whereNotIn('status', ['completed', 'archived', 'rejected'])
                        ->count()
                    : 0,
                'pending_defenses' => $this->tableExists('defenses')
                    ? DB::table('defenses')->whereIn('status', ['pending', 'requested'])->count()
                    : 0,
            ];
        });
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
        $pendingDefenses = $this->tableExists('defenses')
            ? DB::table('defenses')->whereIn('status', ['pending', 'requested'])->count()
            : 0;
        $overdueRevisions = $this->tableExists('revision_requests')
            ? DB::table('revision_requests')
                ->whereIn('status', ['open', 'in_progress'])
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->count()
            : 0;

        return [
            ['label' => 'Awaiting Email Verification', 'count' => $pendingStudents, 'tab' => 'users', 'subtab' => 'pending-students', 'icon' => 'ph-envelope-simple', 'tone' => 'amber'],
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
        $queueDriver = (string) config('queue.default', 'sync');
        $queueStatus = $queueDriver === 'sync'
            ? 'Synchronous'
            : ($failedJobs === 0 ? 'Operational' : 'Needs Attention');
        $queueDetail = $queueDriver === 'sync'
            ? 'Jobs run during the web request; no worker is required'
            : ($failedJobs === 0 ? 'No failed jobs recorded' : "{$failedJobs} failed job(s)");
        $heartbeat = Cache::get('system:scheduler-heartbeat');
        $schedulerHealthy = is_string($heartbeat) && Carbon::parse($heartbeat)->greaterThan(now()->subMinutes(5));
        $backupDependenciesConfigured = $this->backupDependenciesAreConfigured();

        return [
            ['label' => 'Database', 'status' => $databaseHealthy ? 'Operational' : 'Unavailable', 'healthy' => $databaseHealthy, 'detail' => 'Application database connection', 'icon' => 'ph-database'],
            ['label' => 'Private Storage', 'status' => $storageHealthy ? 'Operational' : 'Unavailable', 'healthy' => $storageHealthy, 'detail' => 'Protected document storage', 'icon' => 'ph-lock-key'],
            ['label' => 'Queue Processing', 'status' => $queueStatus, 'healthy' => $failedJobs === 0, 'detail' => $queueDetail, 'icon' => 'ph-stack'],
            ['label' => 'Task Scheduler', 'status' => $schedulerHealthy ? 'Operational' : 'No heartbeat', 'healthy' => $schedulerHealthy, 'detail' => $schedulerHealthy ? 'Scheduler heartbeat received within five minutes' : 'Start php artisan schedule:work on the server', 'icon' => 'ph-timer'],
            ['label' => 'Backup Toolchain', 'status' => $backupDependenciesConfigured ? 'Configured' : 'Incomplete', 'healthy' => $backupDependenciesConfigured, 'detail' => $backupDependenciesConfigured ? 'Run backup verification from Backup Management to confirm the toolchain' : 'ZIP or PostgreSQL backup configuration is incomplete', 'icon' => 'ph-hard-drives'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function cachedSystemHealth(): array
    {
        // Process checks (pg_dump/docker) are intentionally kept off the hot
        // request path after the first check in this five-minute window.
        return Cache::remember('admin-dashboard.system-health', now()->addMinutes(5), fn (): array => $this->systemHealth());
    }

    private function backupDependenciesAreConfigured(): bool
    {
        if (! class_exists(\ZipArchive::class) || config('database.default') !== 'pgsql') {
            return false;
        }

        return trim((string) config('backups.pg_dump_binary')) !== ''
            || trim((string) config('backups.pg_dump_docker_container')) !== '';
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
            'title' => ($group->research_title ?? null) ?: ($group->name ?? 'Research Group'),
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
        if (! $this->tablesExist(['defenses', 'defense_schedules', 'research_class_groups'])) {
            return [];
        }

        return Defense::query()
            ->with([
                'currentSchedule.room',
                'group.researchClass:id,name',
                'group.adviser:id,name',
                'group.leader:id,name',
                'group.members.student:id,name',
                'group.researchGroup.currentProject' => fn ($query) => $query->select([
                    'research_projects.id',
                    'research_projects.research_group_id',
                    'research_projects.title',
                ]),
                'activePanelAssignments.user:id,name',
            ])
            ->latest('updated_at')
            ->limit(100)
            ->get()
            ->map(function (Defense $defense): array {
                $schedule = $defense->currentSchedule;
                $start = $schedule?->starts_at;
                $end = $schedule?->ends_at;
                $duration = $start !== null && $end !== null
                    ? $start->diffForHumans($end, true)
                    : '';
                $venue = collect([
                    $schedule?->room?->name,
                    $schedule?->room?->location_notes,
                ])
                    ->filter()
                    ->unique()
                    ->implode(', ');
                $group = $defense->group;
                $memberNames = $group?->members->pluck('student.name')->filter()->values() ?? collect();

                return [
                    'id' => (int) $defense->getKey(),
                    'type' => Str::headline((string) $defense->defense_type),
                    'title' => $group?->title ?: $group?->name ?: 'Research Group',
                    'student' => $memberNames->implode(', ') ?: ($group?->leader?->name ?? ''),
                    'group' => $group?->name ?? '',
                    'class' => $group?->researchClass?->name ?? '',
                    'date' => $start?->format('Y-m-d') ?? '',
                    'time' => $start?->format('H:i') ?? '',
                    'duration' => $duration,
                    'venue' => $venue,
                    'adviser' => $group?->adviser?->name ?? 'Not assigned',
                    'panelists' => $defense->activePanelAssignments
                        ->map(fn ($assignment): array => [
                            'name' => $assignment->user?->name ?? 'Unknown user',
                            'position' => Str::headline((string) ($assignment->panel_position ?: 'panelist')),
                        ])
                        ->values()
                        ->all(),
                    'status' => Str::headline((string) ($schedule?->status ?? $defense->status)),
                    'notes' => $schedule?->reason ?? '',
                    'generateNotice' => false,
                ];
            })
            ->all();
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
