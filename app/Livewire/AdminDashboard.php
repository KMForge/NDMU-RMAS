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
use App\Modules\Dashboard\Queries\GetAdminDashboardData;
use App\Modules\Documents\Queries\GetDocumentRepositoryData;
use App\Modules\UserManagement\Actions\ManageRoleAccess;
use App\Modules\UserManagement\Actions\ManageUserAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
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

    public string $auditDateFrom = '';

    public string $auditDateTo = '';

    public function updatedSearchQuery(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedRole(): void
    {
        $this->resetPage();
    }

    public function updatedAuditSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAuditEvent(): void
    {
        $this->resetPage();
    }

    public function updatedAuditDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedAuditDateTo(): void
    {
        $this->resetPage();
    }

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

    /** @var list<string> */
    public array $assignedRoles = [];

    public string $assignedUserType = '';

    public string $settingsSystemName = '';

    public string $settingsSupportEmail = '';

    public bool $settingsStudentRegistrationEnabled = true;

    public bool $settingsEmailNotificationsEnabled = true;

    public string $settingsMaintenanceNotice = '';

    public ?int $settingsAcademicYearId = null;

    public ?int $settingsAcademicTermId = null;

    protected $queryString = [
        'searchQuery' => ['except' => ''],
        'selectedRole' => ['except' => ''],
        'auditSearch' => ['except' => ''],
        'auditEvent' => ['except' => ''],
        'auditDateFrom' => ['except' => ''],
        'auditDateTo' => ['except' => ''],
    ];

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
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
        $this->reset(['auditSearch', 'auditEvent', 'auditDateFrom', 'auditDateTo']);
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

    public function refreshUserManagement(): void
    {
        Gate::authorize('viewAny', User::class);
        $this->resetValidation();
        $this->successMessage = null;
        $this->clearDashboardCache();
    }

    public function approveStudent(int $userId, ManageUserAccount $manageUserAccount): void
    {
        $user = User::query()->findOrFail($userId);
        Gate::authorize('changeStatus', $user);
        $manageUserAccount->approveStudent($user, $this->administrator());

        $this->clearDashboardCache();
        $this->successMessage = "Student {$user->name} has been approved.";
    }

    public function rejectStudent(int $userId, ManageUserAccount $manageUserAccount): void
    {
        $user = User::query()->findOrFail($userId);
        Gate::authorize('changeStatus', $user);
        $manageUserAccount->rejectStudent($user, $this->administrator());

        $this->clearDashboardCache();
        $this->successMessage = "Student {$user->name} registration has been rejected.";
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

    public function openRoleAssignment(int $userId): void
    {
        $subject = User::query()->with('roles:id,name')->findOrFail($userId);
        Gate::authorize('manageRoles', $subject);

        $this->roleAssignmentUserId = (int) $subject->getKey();
        $this->assignedRoles = $subject->roles->pluck('name')->sort()->values()->all();
        $this->assignedUserType = $subject->user_type->value;
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

    public function closeRoleAssignment(): void
    {
        $this->reset(['roleAssignmentUserId', 'assignedRoles', 'assignedUserType']);
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

    public function render(GetAdminDashboardData $getAdminDashboardData, GetDocumentRepositoryData $repositoryData)
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
            $this->auditLogData(),
            $this->systemSettingsData(),
            $repositoryData->for($this->administrator(), request()->query()),
        );

        return view('livewire.admin-dashboard-content', $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardData(): array
    {
        return Cache::remember(
            'admin-dashboard.overview',
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

        $auditLogs = AuditLog::query()
            ->latest('created_at')
            ->limit(5)
            ->get();

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

        $usersList = User::query()
            ->with('roles:id,name,display_name')
            ->when($this->searchQuery, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', '%'.$this->searchQuery.'%')
                        ->orWhere('email', 'like', '%'.$this->searchQuery.'%');
                });
            })
            ->when($this->selectedRole, function ($query) {
                $this->selectedRole === '__without_roles__'
                    ? $query->doesntHave('roles')
                    : $query->role($this->selectedRole);
            })
            ->orderBy('id')
            ->paginate(10);

        return [
            'totalUsersCount' => (int) $counts->total_users_count,
            'pendingApprovalCount' => $pendingStudents->count(),
            'activeAccountsCount' => (int) $counts->active_accounts_count,
            'rejectedCount' => (int) $counts->rejected_count,
            'withoutRolesCount' => User::query()->doesntHave('roles')->count(),
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
    private function auditLogData(): array
    {
        $search = trim(mb_substr(strip_tags($this->auditSearch), 0, 100));
        $event = trim(mb_substr(strip_tags($this->auditEvent), 0, 120));
        $dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->auditDateFrom) === 1 ? $this->auditDateFrom : null;
        $dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->auditDateTo) === 1 ? $this->auditDateTo : null;

        $auditLogs = AuditLog::query()
            ->with(['actor:id,name,email', 'auditable'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('actor_name', 'like', '%'.$search.'%')
                        ->orWhere('actor_email', 'like', '%'.$search.'%')
                        ->orWhere('subject_name', 'like', '%'.$search.'%')
                        ->orWhere('subject_email', 'like', '%'.$search.'%')
                        ->orWhere('event', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhere('ip_address', 'like', '%'.$search.'%');
                });
            })
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->when($dateFrom !== null, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== null, fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest('created_at')
            ->paginate(20, ['*'], 'auditPage');

        return [
            'auditLogs' => $auditLogs,
            'auditLogEvents' => AuditLog::query()
                ->distinct()
                ->orderBy('event')
                ->pluck('event'),
            'auditLogStats' => [
                'today' => AuditLog::query()->where('created_at', '>=', now()->startOfDay())->count(),
                'workspace_switches' => AuditLog::query()->where('event', 'workspace.switched')->count(),
                'access_changes' => AuditLog::query()->whereIn('event', ['user.access-updated', 'role.created', 'role.updated', 'role.deleted'])->count(),
            ],
        ];
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
        $this->resetPage();
    }
}
