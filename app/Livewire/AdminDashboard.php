<?php

namespace App\Livewire;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Modules\Administration\Actions\UpdateSystemSettings;
use App\Modules\AuditLogs\Queries\GetAuditLogsForAdmin;
use App\Modules\Dashboard\Queries\GetAdminDashboardData;
use App\Modules\Documents\Queries\GetDocumentRepositoryData;
use App\Modules\UserManagement\Actions\ManageRoleAccess;
use App\Modules\UserManagement\Actions\ManageUserAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class AdminDashboard extends Component
{
    use WithPagination;

    // Search and filter inputs
    public string $searchQuery = '';

    public string $selectedRole = '';

    public string $auditSearch = '';

    public string $auditEvent = '';

    public string $auditContext = '';

    public string $auditOutcome = '';

    public string $auditDateFrom = '';

    public string $auditDateTo = '';

    // Create Staff Account Form Fields
    public string $name = '';

    public string $email = '';

    public string $department = '';

    public string $password = '';

    public ?string $successMessage = null;

    public ?int $editingRoleId = null;

    public bool $showRoleEditor = false;

    public string $roleName = '';

    /** @var list<string> */
    public array $selectedPermissions = [];

    public ?int $roleAssignmentUserId = null;

    public string $roleAssignmentMode = 'edit';

    /** @var list<string> */
    public array $assignedRoles = [];

    /** @var list<string> */
    public array $originalAssignedRoles = [];

    /** @var array{advised_groups_count: int, panel_evaluations_count: int, pending_forms_count: int} */
    public array $userActiveDuties = [
        'advised_groups_count' => 0,
        'panel_evaluations_count' => 0,
        'pending_forms_count' => 0,
    ];

    public string $assignedUserType = '';

    public string $settingsSystemName = '';

    public string $settingsSupportEmail = '';

    public bool $settingsStudentRegistrationEnabled = true;

    public bool $settingsEmailNotificationsEnabled = true;

    public string $settingsMaintenanceNotice = '';

    public ?int $settingsAcademicYearId = null;

    public ?int $settingsAcademicTermId = null;

    public bool $showAcademicYearModal = false;

    public string $newAcademicYearName = '';

    public string $newAcademicYearStartDate = '';

    public string $newAcademicYearEndDate = '';

    public string $tab = 'dashboard';

    public string $userManagementTab = 'all-users';

    public string $selectedDepartment = 'all';

    public string $selectedUserType = 'all';

    protected $queryString = [
        'tab' => ['except' => 'dashboard'],
        'userManagementTab' => ['except' => 'all-users'],
        'selectedDepartment' => ['except' => 'all'],
        'selectedUserType' => ['except' => 'all'],
        'searchQuery' => ['except' => ''],
        'selectedRole' => ['except' => ''],
        'auditSearch' => ['except' => ''],
        'auditEvent' => ['except' => ''],
        'auditContext' => ['except' => ''],
        'auditOutcome' => ['except' => ''],
        'auditDateFrom' => ['except' => ''],
        'auditDateTo' => ['except' => ''],
    ];

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
        if ($this->tab === 'audit') {
            Gate::authorize('audit-logs.view');
        }
        $this->department = (string) config('academic.college.name');
        $this->loadSystemSettings();
    }

    public function updatedSearchQuery(string $value): void
    {
        $this->searchQuery = mb_substr(strip_tags($value), 0, 100);
        $this->resetPage();
    }

    public function updatedSelectedRole(string $value): void
    {
        if ($value !== '' && $value !== '__without_roles__' && ! Role::query()->where('guard_name', 'web')->where('name', $value)->exists()) {
            $this->selectedRole = '';
        }

        $this->resetPage();
    }

    public function updatedAuditSearch(string $value): void
    {
        $this->auditSearch = mb_substr(strip_tags($value), 0, 100);
        $this->resetPage('auditPage');
    }

    public function updatedAuditEvent(string $value): void
    {
        $this->auditEvent = mb_substr(strip_tags($value), 0, 120);
        $this->resetPage('auditPage');
    }

    public function updatedAuditContext(string $value): void
    {
        $this->auditContext = mb_substr(strip_tags($value), 0, 64);
        $this->resetPage('auditPage');
    }

    public function updatedAuditOutcome(string $value): void
    {
        $this->auditOutcome = in_array($value, ['succeeded', 'denied', 'failed'], true) ? $value : '';
        $this->resetPage('auditPage');
    }

    public function updatedTab(string $value): void
    {
        if ($value === 'audit') {
            Gate::authorize('audit-logs.view');
        }
    }

    public function updatedAuditDateFrom(): void
    {
        $this->resetPage('auditPage');
    }

    public function updatedAuditDateTo(): void
    {
        $this->resetPage('auditPage');
    }

    public function clearAuditFilters(): void
    {
        Gate::authorize('audit-logs.view');
        $this->reset(['auditSearch', 'auditEvent', 'auditContext', 'auditOutcome', 'auditDateFrom', 'auditDateTo']);
        $this->resetPage('auditPage');
    }

    public function updatedSettingsAcademicYearId(?int $value): void
    {
        if ($this->settingsAcademicTermId !== null && ! AcademicTerm::query()
            ->whereKey($this->settingsAcademicTermId)
            ->where('academic_year_id', $value)
            ->exists()) {
            $this->settingsAcademicTermId = null;
        }
    }

    public function setDepartmentFilter(string $department): void
    {
        $this->selectedDepartment = $department;
        $this->resetPage();
    }

    public function setUserTypeFilter(string $userType): void
    {
        $this->selectedUserType = $userType;
        $this->resetPage();
    }

    public function refreshUserManagement(): void
    {
        Gate::authorize('viewAny', User::class);
        $this->resetValidation();
        $this->successMessage = null;
        $this->clearDashboardCache();
    }

    public function activateUser(int $userId, ManageUserAccount $manageUserAccount): void
    {
        $user = User::query()->findOrFail($userId);
        Gate::authorize('changeStatus', $user);
        $manageUserAccount->activate($user, $this->administrator());

        $this->clearDashboardCache();
        $this->successMessage = "Account for {$user->name} has been activated.";
    }

    public function suspendUser(int $userId, ManageUserAccount $manageUserAccount): void
    {
        $user = User::query()->findOrFail($userId);
        Gate::authorize('changeStatus', $user);
        $manageUserAccount->suspend($user, $this->administrator());

        $this->clearDashboardCache();
        $this->successMessage = "Account for {$user->name} has been suspended.";
    }

    public function createStaffAccount(ManageUserAccount $manageUserAccount): void
    {
        Gate::authorize('create', User::class);

        $this->name = trim(strip_tags($this->name));
        $this->email = mb_strtolower(trim($this->email));
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'string', Password::min(12)->mixedCase()->letters()->numbers()->symbols()],
        ]);

        $college = (string) config('academic.college.name');

        $user = $manageUserAccount->createStaff([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'department' => $college,
        ], $this->administrator());

        $this->clearDashboardCache();
        $this->successMessage = "Faculty account for {$user->name} created. Assign a role when access is required.";

        $this->reset(['name', 'email', 'password']);
        $this->department = $college;
        $this->dispatch('staff-account-created');
    }

    public function editRole(int $roleId): void
    {
        $this->authorizeRoleManagement();
        $role = Role::query()->with('permissions:id,name')->findOrFail($roleId);

        $this->editingRoleId = (int) $role->getKey();
        $this->showRoleEditor = true;
        $this->roleName = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->sort()->values()->all();
        $this->resetValidation();
        $this->dispatch('role-editor-opened');
    }

    public function createRole(): void
    {
        $this->authorizeRoleManagement();
        $this->reset(['editingRoleId', 'roleName', 'selectedPermissions']);
        $this->showRoleEditor = true;
        $this->resetValidation();
        $this->dispatch('role-editor-opened');
    }

    public function selectAllPermissions(): void
    {
        $this->authorizeRoleManagement();
        $this->selectedPermissions = collect(config('access-control.permissions', []))
            ->flatMap(fn (array $group): array => array_keys($group))
            ->values()
            ->all();
    }

    public function clearSelectedPermissions(): void
    {
        $this->authorizeRoleManagement();
        $this->selectedPermissions = [];
    }

    public function saveRole(ManageRoleAccess $manageRoleAccess): void
    {
        $this->authorizeRoleManagement();
        $this->roleName = Str::slug(mb_strtolower(trim($this->roleName)));
        $catalogNames = collect(config('access-control.permissions', []))
            ->flatMap(fn (array $group): array => array_keys($group))
            ->values()
            ->all();

        $this->validate([
            'roleName' => [
                'required',
                'string',
                'min:3',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('roles', 'name')->ignore($this->editingRoleId),
            ],
            'selectedPermissions' => ['required', 'array', 'min:1'],
            'selectedPermissions.*' => ['string', Rule::in($catalogNames)],
        ], [
            'selectedPermissions.required' => 'Select at least one permission for this role.',
        ]);

        $role = $this->editingRoleId === null
            ? null
            : Role::query()->findOrFail($this->editingRoleId);
        $savedRole = $manageRoleAccess->saveCustomRole(
            $this->administrator(),
            $role,
            $this->roleName,
            array_values(array_unique($this->selectedPermissions)),
        );

        $this->successMessage = "Role {$savedRole->name} saved successfully.";
        $this->resetRoleEditor();
    }

    public function deleteRole(int $roleId, ManageRoleAccess $manageRoleAccess): void
    {
        $role = Role::query()->findOrFail($roleId);
        $name = $role->name;
        $manageRoleAccess->deleteCustomRole($this->administrator(), $role);
        $this->successMessage = "Role {$name} deleted successfully.";
        $this->resetRoleEditor();
    }

    public function resetRoleEditor(): void
    {
        $this->reset(['editingRoleId', 'roleName', 'selectedPermissions']);
        $this->showRoleEditor = false;
        $this->resetValidation();
        $this->dispatch('role-editor-closed');
    }

    public function openRoleAssignment(int $userId, string $mode = 'edit'): void
    {
        $subject = User::query()->with('roles:id,name')->findOrFail($userId);
        Gate::authorize('manageRoles', $subject);

        $this->roleAssignmentMode = $mode;
        $this->roleAssignmentUserId = (int) $subject->getKey();
        $this->assignedRoles = $subject->roles->pluck('name')->sort()->values()->all();
        $this->originalAssignedRoles = $this->assignedRoles;
        $this->assignedUserType = $subject->user_type->value;
        $this->userActiveDuties = [
            'advised_groups_count' => Schema::hasTable('research_class_groups') ? DB::table('research_class_groups')->where('adviser_id', $subject->id)->where('status', 'active')->count() : 0,
            'panel_evaluations_count' => Schema::hasTable('defense_panel_assignments') ? DB::table('defense_panel_assignments')->where('user_id', $subject->id)->whereNull('ended_at')->count() : 0,
            'pending_forms_count' => Schema::hasTable('official_form_actor_assignments') ? DB::table('official_form_actor_assignments')->where('user_id', $subject->id)->where('status', 'pending')->count() : 0,
        ];
        $this->resetValidation();
        $this->dispatch('role-assignment-opened');
    }

    public function saveUserRoles(ManageRoleAccess $manageRoleAccess): void
    {
        abort_if($this->roleAssignmentUserId === null, 404);
        $subject = User::query()->findOrFail($this->roleAssignmentUserId);
        Gate::authorize('manageRoles', $subject);
        $availableRoles = Role::query()
            ->where('guard_name', 'web')
            ->where('is_assignable', true)
            ->pluck('name')
            ->all();

        $this->validate([
            'assignedRoles' => ['array'],
            'assignedRoles.*' => ['string', Rule::in($availableRoles)],
            'assignedUserType' => ['required', Rule::enum(UserType::class)],
        ]);

        $manageRoleAccess->syncUserRoles(
            $this->administrator(),
            $subject,
            array_values(array_unique($this->assignedRoles)),
            UserType::from($this->assignedUserType),
        );

        $this->successMessage = "Roles for {$subject->name} updated successfully.";
        $this->closeRoleAssignment();
        $this->clearDashboardCache();
    }

    public function selectAllAssignableRoles(): void
    {
        $subject = $this->roleAssignmentSubject();
        Gate::authorize('manageRoles', $subject);

        $this->assignedRoles = Role::query()
            ->where('guard_name', 'web')
            ->where('is_assignable', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    public function clearAssignedRoles(): void
    {
        $subject = $this->roleAssignmentSubject();
        Gate::authorize('manageRoles', $subject);
        $this->assignedRoles = [];
        $this->resetValidation('assignedRoles');
    }

    public function toggleRole(string $roleName): void
    {
        if (in_array($roleName, $this->assignedRoles, true)) {
            $this->assignedRoles = array_values(array_diff($this->assignedRoles, [$roleName]));
        } else {
            $this->assignedRoles[] = $roleName;
        }
    }

    public function removeAssignedRole(string $roleName): void
    {
        $this->assignedRoles = array_values(array_diff($this->assignedRoles, [$roleName]));
    }

    public function closeRoleAssignment(): void
    {
        $this->reset(['roleAssignmentUserId', 'assignedRoles', 'originalAssignedRoles', 'assignedUserType', 'userActiveDuties']);
        $this->resetValidation();
        $this->dispatch('role-assignment-closed');
    }

    public function saveSystemSettings(UpdateSystemSettings $updateSystemSettings): void
    {
        abort_unless($this->administrator()->can('settings.manage'), 403);

        $this->settingsSystemName = trim(strip_tags($this->settingsSystemName));
        $this->settingsSupportEmail = mb_strtolower(trim($this->settingsSupportEmail));
        $this->settingsMaintenanceNotice = trim(strip_tags($this->settingsMaintenanceNotice));

        $this->validate([
            'settingsSystemName' => ['required', 'string', 'min:3', 'max:150'],
            'settingsSupportEmail' => ['required', 'email:rfc', 'max:255'],
            'settingsStudentRegistrationEnabled' => ['boolean'],
            'settingsEmailNotificationsEnabled' => ['boolean'],
            'settingsMaintenanceNotice' => ['nullable', 'string', 'max:500'],
            'settingsAcademicYearId' => ['nullable', 'integer', Rule::exists('academic_years', 'id')],
            'settingsAcademicTermId' => [
                'nullable',
                'integer',
                Rule::exists('academic_terms', 'id')->where(
                    fn ($query) => $query->where('academic_year_id', $this->settingsAcademicYearId),
                ),
            ],
        ]);

        if (($this->settingsAcademicYearId === null) !== ($this->settingsAcademicTermId === null)) {
            $this->addError('settingsAcademicTermId', 'Select both an academic year and one of its terms.');

            return;
        }

        $updateSystemSettings->handle($this->administrator(), [
            'system_name' => $this->settingsSystemName,
            'support_email' => $this->settingsSupportEmail,
            'student_registration_enabled' => $this->settingsStudentRegistrationEnabled,
            'email_notifications_enabled' => $this->settingsEmailNotificationsEnabled,
            'maintenance_notice' => $this->settingsMaintenanceNotice !== '' ? $this->settingsMaintenanceNotice : null,
            'academic_year_id' => $this->settingsAcademicYearId,
            'academic_term_id' => $this->settingsAcademicTermId,
        ]);

        $this->successMessage = 'System settings saved successfully.';
        $this->resetValidation();
    }

    public function seedAcademicCycle(): void
    {
        abort_unless($this->administrator()->can('settings.manage'), 403);

        $ay2025 = AcademicYear::query()->firstOrCreate(
            ['name' => '2025–2026'],
            ['starts_at' => '2025-08-01', 'ends_at' => '2026-05-31', 'is_current' => false]
        );

        AcademicTerm::query()->firstOrCreate(
            ['academic_year_id' => $ay2025->id, 'name' => 'First Semester'],
            ['starts_at' => '2025-08-01', 'ends_at' => '2025-12-20', 'is_current' => false]
        );
        AcademicTerm::query()->firstOrCreate(
            ['academic_year_id' => $ay2025->id, 'name' => 'Second Semester'],
            ['starts_at' => '2026-01-12', 'ends_at' => '2026-05-31', 'is_current' => false]
        );

        $ay2026 = AcademicYear::query()->firstOrCreate(
            ['name' => '2026–2027'],
            ['starts_at' => '2026-08-01', 'ends_at' => '2027-05-31', 'is_current' => true]
        );

        $term1 = AcademicTerm::query()->firstOrCreate(
            ['academic_year_id' => $ay2026->id, 'name' => 'First Semester'],
            ['starts_at' => '2026-08-01', 'ends_at' => '2026-12-20', 'is_current' => true]
        );
        AcademicTerm::query()->firstOrCreate(
            ['academic_year_id' => $ay2026->id, 'name' => 'Second Semester'],
            ['starts_at' => '2027-01-11', 'ends_at' => '2027-05-31', 'is_current' => false]
        );
        AcademicTerm::query()->firstOrCreate(
            ['academic_year_id' => $ay2026->id, 'name' => 'Summer Term'],
            ['starts_at' => '2027-06-07', 'ends_at' => '2027-07-16', 'is_current' => false]
        );

        $this->settingsAcademicYearId = $ay2026->id;
        $this->settingsAcademicTermId = $term1->id;
        $this->successMessage = 'Academic cycle seeded successfully.';
        $this->resetValidation();
    }

    public function openAcademicYearModal(): void
    {
        abort_unless($this->administrator()->can('settings.manage'), 403);
        $this->reset(['newAcademicYearName', 'newAcademicYearStartDate', 'newAcademicYearEndDate']);
        $this->showAcademicYearModal = true;
        $this->resetValidation();
    }

    public function closeAcademicYearModal(): void
    {
        $this->reset(['newAcademicYearName', 'newAcademicYearStartDate', 'newAcademicYearEndDate']);
        $this->showAcademicYearModal = false;
        $this->resetValidation();
    }

    public function createAcademicYear(): void
    {
        abort_unless($this->administrator()->can('settings.manage'), 403);

        $this->newAcademicYearName = trim(strip_tags($this->newAcademicYearName));
        $this->validate([
            'newAcademicYearName' => ['required', 'string', 'max:50', 'unique:academic_years,name'],
            'newAcademicYearStartDate' => ['required', 'date'],
            'newAcademicYearEndDate' => ['required', 'date', 'after:newAcademicYearStartDate'],
        ]);

        $ay = AcademicYear::query()->create([
            'name' => $this->newAcademicYearName,
            'starts_at' => $this->newAcademicYearStartDate,
            'ends_at' => $this->newAcademicYearEndDate,
            'is_current' => false,
        ]);

        $term1 = AcademicTerm::query()->create([
            'academic_year_id' => $ay->id,
            'name' => 'First Semester',
            'starts_at' => $ay->starts_at,
            'ends_at' => Carbon::parse($ay->starts_at)->addMonths(4)->endOfMonth(),
            'is_current' => false,
        ]);
        AcademicTerm::query()->create([
            'academic_year_id' => $ay->id,
            'name' => 'Second Semester',
            'starts_at' => Carbon::parse($ay->starts_at)->addMonths(5)->startOfMonth(),
            'ends_at' => $ay->ends_at,
            'is_current' => false,
        ]);

        $this->settingsAcademicYearId = $ay->id;
        $this->settingsAcademicTermId = $term1->id;
        $this->successMessage = "Academic Year {$ay->name} created successfully.";
        $this->closeAcademicYearModal();
    }

    public function render(GetAdminDashboardData $getAdminDashboardData, GetDocumentRepositoryData $repositoryData, GetAuditLogsForAdmin $auditLogs)
    {
        $data = [
            'totalUsersCount' => 0,
            'pendingApprovalCount' => 0,
            'activeAccountsCount' => 0,
            'rejectedCount' => 0,
            'studentCount' => 0,
            'adviserCount' => 0,
            'panelistCount' => 0,
            'recentActivities' => [],
            'usersList' => null,
            'pendingStudents' => collect(),
            'administrator' => $this->administrator(),
        ];

        $data = array_merge(
            $data,
            $this->dashboardData(),
            $this->userManagementData(),
            $getAdminDashboardData->get(),
            $this->roleManagementData(),
            $this->auditLogData($auditLogs),
            $this->systemSettingsData(),
            $repositoryData->for($this->administrator(), request()->query()),
        );

        $data['sidebarBadges'] = [
            'users' => (int) ($data['pendingApprovalCount'] ?? 0),
            'research' => (int) ($data['activeResearchCount'] ?? 0),
            'defenses' => (int) ($data['pendingDefenseCount'] ?? 0),
            'notifications' => Schema::hasTable('notifications')
                ? $data['administrator']->unreadNotifications()->count()
                : 0,
        ];

        $data['roleAssignmentUser'] = $this->roleAssignmentUserId
            ? User::query()->with('roles:id,name,display_name')->find($this->roleAssignmentUserId)
            : null;

        return view('livewire.admin-dashboard-content', $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardData(): array
    {
        return Cache::remember(
            'admin-dashboard.overview.'.($this->administrator()->can('audit-logs.view') ? 'with-audit' : 'without-audit'),
            now()->addSeconds(30),
            fn (): array => $this->freshDashboardData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function freshDashboardData(): array
    {
        $totalUsersCount = User::query()->count();
        $studentCount = User::query()->where('user_type', 'student')->count();
        $adviserCount = User::permission('classes.serve-as-adviser')->count();
        $panelistCount = User::permission('evaluations.create')->count();

        $recentUsers = User::query()
            ->with('roles:id,name,display_name')
            ->latest()
            ->limit(4)
            ->get();

        $recentActivities = [];

        $auditLogs = $this->administrator()->can('audit-logs.view')
            ? AuditLog::query()->latest('created_at')->latest('id')->limit(5)->get()
            : collect();

        if ($auditLogs->isNotEmpty()) {
            foreach ($auditLogs as $log) {
                $eventName = str_replace(['.', '_', '-'], ' ', Str::title($log->event));
                $recentActivities[] = [
                    'text' => "{$eventName}: {$log->description}",
                    'actor' => $log->actor_name ?? $log->actor_email ?? 'System',
                    'email' => $log->actor_email ?? 'system@ndmu.edu.ph',
                    'event' => $log->event,
                    'time' => $log->created_at->diffForHumans(),
                ];
            }
        } else {
            $recentUsers = User::query()
                ->with('roles:id,name,display_name')
                ->latest()
                ->limit(5)
                ->get();

            foreach ($recentUsers as $user) {
                $roleLabel = $user->roles->first()?->name ?? 'User';
                $roleLabel = str_replace('-', ' ', Str::title($roleLabel));
                $recentActivities[] = [
                    'text' => "New {$roleLabel} registered: {$user->email}",
                    'actor' => $user->name,
                    'email' => $user->email,
                    'event' => 'user.registered',
                    'time' => $user->created_at->diffForHumans(),
                ];
            }
        }

        return [
            'totalUsersCount' => $totalUsersCount,
            'studentCount' => $studentCount,
            'adviserCount' => $adviserCount,
            'panelistCount' => $panelistCount,
            'recentActivities' => $recentActivities,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userManagementData(): array
    {
        $counts = User::query()
            ->selectRaw(
                'COUNT(*) AS total_users_count,
                COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS active_accounts_count,
                COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS rejected_count',
                [AccountStatus::Active->value, AccountStatus::Rejected->value],
            )
            ->first();

        $pendingStudentsQuery = User::query()->where('user_type', 'student')
            ->where('status', AccountStatus::Pending)
            ->latest();

        $pendingStudents = $pendingStudentsQuery->get();

        $departmentStats = [
            'CSD' => [
                'code' => 'CSD',
                'name' => 'Computer Studies Department',
                'programs' => 'BSCS, BSIT, BLIS',
                'student_org' => 'Course-specific computing/library organizations',
                'coordinator' => User::role('program-coordinator')->where(function ($q) {
                    $q->where('department', 'like', '%Computer Studies%')->orWhere('department', 'CSD');
                })->first()?->name ?? 'Engr. Jose Montero',
                'faculty_count' => User::where(fn ($q) => $q->where('user_type', 'faculty')->orWhere('user_type', 'faculty_member'))->where(function ($q) {
                    $q->where('department', 'like', '%Computer Studies%')->orWhere('department', 'CSD');
                })->count(),
                'student_count' => User::where('user_type', 'student')->where(function ($q) {
                    $q->where('department', 'like', '%Computer Studies%')
                        ->orWhere('department', 'CSD')
                        ->orWhere('program', 'like', '%BSCS%')
                        ->orWhere('program', 'like', '%BSIT%')
                        ->orWhere('program', 'like', '%BLIS%');
                })->count(),
                'total' => User::where(function ($q) {
                    $q->where('department', 'like', '%Computer Studies%')
                        ->orWhere('department', 'CSD')
                        ->orWhere('program', 'like', '%BSCS%')
                        ->orWhere('program', 'like', '%BSIT%')
                        ->orWhere('program', 'like', '%BLIS%');
                })->count(),
            ],
            'EECE' => [
                'code' => 'EECE',
                'name' => 'Electrical, Electronics, and Computer Engineering',
                'programs' => 'BSEE, BSECE, BSCpE',
                'student_org' => 'IIEE, JIECEP and ICpEP.SE',
                'coordinator' => User::role('program-coordinator')->where(function ($q) {
                    $q->where('department', 'like', '%Electrical%')->orWhere('department', 'EECE');
                })->first()?->name ?? 'Engr. Michael Diaz',
                'faculty_count' => User::where(fn ($q) => $q->where('user_type', 'faculty')->orWhere('user_type', 'faculty_member'))->where(function ($q) {
                    $q->where('department', 'like', '%Electrical%')->orWhere('department', 'EECE');
                })->count(),
                'student_count' => User::where('user_type', 'student')->where(function ($q) {
                    $q->where('department', 'like', '%Electrical%')
                        ->orWhere('department', 'EECE')
                        ->orWhere('program', 'like', '%BSEE%')
                        ->orWhere('program', 'like', '%BSECE%')
                        ->orWhere('program', 'like', '%BSCPE%')
                        ->orWhere('program', 'like', '%BSCpE%');
                })->count(),
                'total' => User::where(function ($q) {
                    $q->where('department', 'like', '%Electrical%')
                        ->orWhere('department', 'EECE')
                        ->orWhere('program', 'like', '%BSEE%')
                        ->orWhere('program', 'like', '%BSECE%')
                        ->orWhere('program', 'like', '%BSCPE%')
                        ->orWhere('program', 'like', '%BSCpE%');
                })->count(),
            ],
            'CED' => [
                'code' => 'CED',
                'name' => 'Civil Engineering Department',
                'programs' => 'BSCE',
                'student_org' => 'PICE–NDMU Student Chapter',
                'coordinator' => User::role('program-coordinator')->where(function ($q) {
                    $q->where('department', 'like', '%Civil%')->orWhere('department', 'CED');
                })->first()?->name ?? 'Engr. Sarah Reyes',
                'faculty_count' => User::where(fn ($q) => $q->where('user_type', 'faculty')->orWhere('user_type', 'faculty_member'))->where(function ($q) {
                    $q->where('department', 'like', '%Civil%')->orWhere('department', 'CED');
                })->count(),
                'student_count' => User::where('user_type', 'student')->where(function ($q) {
                    $q->where('department', 'like', '%Civil%')
                        ->orWhere('department', 'CED')
                        ->orWhere('program', 'like', '%BSCE%');
                })->count(),
                'total' => User::where(function ($q) {
                    $q->where('department', 'like', '%Civil%')
                        ->orWhere('department', 'CED')
                        ->orWhere('program', 'like', '%BSCE%');
                })->count(),
            ],
            'AD' => [
                'code' => 'AD',
                'name' => 'Architecture Department',
                'programs' => 'BSArch',
                'student_org' => 'UAPSA–NDMU Chapter',
                'coordinator' => User::role('program-coordinator')->where(function ($q) {
                    $q->where('department', 'like', '%Architecture%')->orWhere('department', 'AD');
                })->first()?->name ?? 'Ar. Jonathan Tan',
                'faculty_count' => User::where(fn ($q) => $q->where('user_type', 'faculty')->orWhere('user_type', 'faculty_member'))->where(function ($q) {
                    $q->where('department', 'like', '%Architecture%')->orWhere('department', 'AD');
                })->count(),
                'student_count' => User::where('user_type', 'student')->where(function ($q) {
                    $q->where('department', 'like', '%Architecture%')
                        ->orWhere('department', 'AD')
                        ->orWhere('program', 'like', '%BSARCH%')
                        ->orWhere('program', 'like', '%BSArch%');
                })->count(),
                'total' => User::where(function ($q) {
                    $q->where('department', 'like', '%Architecture%')
                        ->orWhere('department', 'AD')
                        ->orWhere('program', 'like', '%BSARCH%')
                        ->orWhere('program', 'like', '%BSArch%');
                })->count(),
            ],
            'institutional' => [
                'code' => 'institutional',
                'name' => 'College Administration',
                'programs' => 'Dean, Ethics, Librarian, Admin',
                'student_org' => 'CEAC Institutional Leadership',
                'coordinator' => 'Dr. Leonardo Castillo (Dean)',
                'faculty_count' => User::where(function ($q) {
                    $q->where('user_type', 'staff')
                        ->orWhereHas('roles', fn ($r) => $r->whereIn('name', ['college-dean', 'system-administrator', 'ethics-evaluator']));
                })->count(),
                'student_count' => 0,
                'total' => User::where(function ($q) {
                    $q->where(function ($sq) {
                        $sq->whereNull('department')
                            ->orWhere('department', '')
                            ->orWhere('department', 'like', '%College%')
                            ->orWhere('department', 'like', '%Administration%');
                    })->where(function ($sq) {
                        $sq->where('user_type', '<>', 'student')
                            ->orWhereHas('roles', fn ($r) => $r->whereIn('name', ['college-dean', 'system-administrator', 'ethics-evaluator']));
                    });
                })->count(),
            ],
        ];

        $usersList = User::query()
            ->with('roles:id,name,display_name')
            ->when($this->searchQuery, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', '%'.$this->searchQuery.'%')
                        ->orWhere('email', 'like', '%'.$this->searchQuery.'%')
                        ->orWhere('student_id', 'like', '%'.$this->searchQuery.'%')
                        ->orWhere('employee_id', 'like', '%'.$this->searchQuery.'%');
                });
            })
            ->when($this->selectedDepartment !== 'all', function ($query) {
                match ($this->selectedDepartment) {
                    'CSD' => $query->where(function ($q) {
                        $q->where('department', 'like', '%Computer Studies%')
                            ->orWhere('department', 'CSD')
                            ->orWhere('program', 'like', '%BSCS%')
                            ->orWhere('program', 'like', '%BSIT%')
                            ->orWhere('program', 'like', '%BLIS%');
                    }),
                    'EECE' => $query->where(function ($q) {
                        $q->where('department', 'like', '%Electrical%')
                            ->orWhere('department', 'EECE')
                            ->orWhere('program', 'like', '%BSEE%')
                            ->orWhere('program', 'like', '%BSECE%')
                            ->orWhere('program', 'like', '%BSCPE%')
                            ->orWhere('program', 'like', '%BSCpE%');
                    }),
                    'CED' => $query->where(function ($q) {
                        $q->where('department', 'like', '%Civil%')
                            ->orWhere('department', 'CED')
                            ->orWhere('program', 'like', '%BSCE%');
                    }),
                    'AD' => $query->where(function ($q) {
                        $q->where('department', 'like', '%Architecture%')
                            ->orWhere('department', 'AD')
                            ->orWhere('program', 'like', '%BSARCH%')
                            ->orWhere('program', 'like', '%BSArch%');
                    }),
                    'institutional' => $query->where(function ($q) {
                        $q->where(function ($sq) {
                            $sq->whereNull('department')
                                ->orWhere('department', '')
                                ->orWhere('department', 'like', '%College%')
                                ->orWhere('department', 'like', '%Administration%');
                        })->where(function ($sq) {
                            $sq->where('user_type', '<>', 'student')
                                ->orWhereHas('roles', fn ($r) => $r->whereIn('name', ['college-dean', 'system-administrator', 'ethics-evaluator']));
                        });
                    }),
                    default => null,
                };
            })
            ->when($this->selectedUserType !== 'all', function ($query) {
                match ($this->selectedUserType) {
                    'faculty' => $query->where(fn ($q) => $q->where('user_type', 'faculty')->orWhere('user_type', 'faculty_member')),
                    'student' => $query->where('user_type', 'student'),
                    'staff' => $query->where(fn ($q) => $q->where('user_type', 'staff')->orWhereHas('roles', fn ($r) => $r->where('name', 'system-administrator'))),
                    'pending' => $query->where('status', AccountStatus::Pending),
                    default => null,
                };
            })
            ->when($this->selectedRole, function ($query) {
                $this->selectedRole === '__without_roles__'
                    ? $query->doesntHave('roles')
                    : $query->role($this->selectedRole);
            })
            ->orderBy('id')
            ->paginate(15);

        return [
            'totalUsersCount' => (int) $counts->total_users_count,
            'pendingApprovalCount' => $pendingStudents->count(),
            'pendingUsersCount' => $pendingStudents->count(),
            'activeAccountsCount' => (int) $counts->active_accounts_count,
            'rejectedCount' => (int) $counts->rejected_count,
            'withoutRolesCount' => User::query()->doesntHave('roles')->count(),
            'departmentStats' => $departmentStats,
            'usersList' => $usersList,
            'pendingStudents' => $pendingStudents,
        ];
    }

    private function administrator(): User
    {
        $administrator = Auth::user();

        abort_unless($administrator instanceof User, 401);

        return $administrator;
    }

    private function roleAssignmentSubject(): User
    {
        abort_if($this->roleAssignmentUserId === null, 404);

        return User::query()->findOrFail($this->roleAssignmentUserId);
    }

    /** @return array<string, mixed> */
    private function roleManagementData(): array
    {
        $protected = config('access-control.protected_roles', []);

        return [
            'permissionCatalog' => config('access-control.permissions', []),
            'rolesList' => Role::query()
                ->where('guard_name', 'web')
                ->where('is_assignable', true)
                ->withCount(['permissions', 'users'])
                ->with('permissions:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role): array => [
                    'id' => (int) $role->getKey(),
                    'name' => $role->name,
                    'label' => $role->display_name ?: Str::headline($role->name),
                    'description' => $role->description,
                    'permissions_count' => (int) $role->permissions_count,
                    'users_count' => (int) $role->users_count,
                    'protected' => in_array($role->name, $protected, true),
                    'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
                ]),
            'staffRoleOptions' => Role::query()
                ->where('guard_name', 'web')
                ->where('is_assignable', true)
                ->whereNotIn('name', ['administrator', 'student'])
                ->orderBy('display_name')
                ->get(['name', 'display_name'])
                ->map(fn (Role $role): array => [
                    'name' => $role->name,
                    'label' => $role->display_name ?: Str::headline($role->name),
                ]),
            'roleAssignmentUser' => $this->roleAssignmentUserId === null
                ? null
                : User::query()->select(['id', 'name', 'email'])->find($this->roleAssignmentUserId),
        ];
    }

    /** @return array<string, mixed> */
    private function auditLogData(GetAuditLogsForAdmin $query): array
    {
        if (! $this->administrator()->can('audit-logs.view')) {
            return [];
        }

        return $query->get(
            $this->administrator(),
            $this->auditSearch,
            $this->auditEvent,
            $this->auditContext,
            $this->auditOutcome,
            $this->auditDateFrom,
            $this->auditDateTo,
        );
    }

    private function authorizeRoleManagement(): void
    {
        abort_unless(
            $this->administrator()->can('roles.manage')
            && $this->administrator()->can('permissions.manage'),
            403,
        );
    }

    private function loadSystemSettings(): void
    {
        $settings = SystemSetting::query()->firstOrFail();

        $this->settingsSystemName = $settings->system_name;
        $this->settingsSupportEmail = $settings->support_email;
        $this->settingsStudentRegistrationEnabled = $settings->student_registration_enabled;
        $this->settingsEmailNotificationsEnabled = $settings->email_notifications_enabled;
        $this->settingsMaintenanceNotice = $settings->maintenance_notice ?? '';
        $this->settingsAcademicYearId = AcademicYear::query()->where('is_current', true)->value('id');
        $this->settingsAcademicTermId = AcademicTerm::query()->where('is_current', true)->value('id');
    }

    /** @return array<string, mixed> */
    private function systemSettingsData(): array
    {
        return [
            'academicYears' => AcademicYear::query()
                ->with(['terms' => fn ($query) => $query->orderBy('starts_at')])
                ->orderByDesc('starts_at')
                ->get(),
            'systemSettingsUpdatedAt' => SystemSetting::query()->value('updated_at'),
        ];
    }

    private function clearDashboardCache(): void
    {
        Cache::forget('admin-dashboard.overview');
        Cache::forget('admin-dashboard.analytics-data');
        $this->resetPage();
    }
}
