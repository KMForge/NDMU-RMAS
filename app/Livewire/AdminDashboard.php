<?php

namespace App\Livewire;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\DefenseRoom;
use App\Models\Department;
use App\Models\OfficialFormDefinition;
use App\Models\Program;
use App\Models\SystemBackup;
use App\Models\SystemBackupSetting;
use App\Models\SystemSetting;
use App\Models\User;
use App\Modules\Administration\Actions\CreateSystemBackup;
use App\Modules\Administration\Actions\DeleteSystemBackup;
use App\Modules\Administration\Actions\ImportSystemBackup;
use App\Modules\Administration\Actions\ManageAcademicConfiguration;
use App\Modules\Administration\Actions\RestoreSystemBackup;
use App\Modules\Administration\Actions\SetServiceAvailability;
use App\Modules\Administration\Actions\UpdateSystemSettings;
use App\Modules\Administration\Actions\VerifySystemBackup;
use App\Modules\Administration\Services\BackupImportLimit;
use App\Modules\AuditLogs\Queries\GetAuditLogsForAdmin;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use App\Modules\Dashboard\Queries\GetAdminDashboardData;
use App\Modules\Documents\Queries\GetDocumentRepositoryData;
use App\Modules\Notifications\Services\UnreadNotificationCount;
use App\Modules\SystemSettings\Services\DocumentUploadLimit;
use App\Modules\UserManagement\Actions\ManageFacultyDepartmentAssignments;
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
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class AdminDashboard extends Component
{
    use WithFileUploads, WithPagination;

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

    /** @var list<int|string> */
    public array $additionalDepartmentIds = [];

    public bool $isCollegeDean = false;

    public bool $showFacultyDepartmentsEditor = false;

    public ?int $facultyDepartmentUserId = null;

    public ?int $primaryDepartmentId = null;

    /** @var list<int|string> */
    public array $facultyDepartmentIds = [];

    public string $password = '';

    public ?string $successMessage = null;

    public ?int $editingRoleId = null;

    public bool $showRoleEditor = false;

    public ?int $pendingRoleDeleteId = null;

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

    public int $settingsDocumentMaxUploadMb = 10;

    public bool $settingsTurnstileEnabled = true;

    public bool $settingsDefenseHighTrafficModeEnabled = false;

    public bool $backupScheduleEnabled = false;

    public string $backupFrequency = 'daily';

    public string $backupRunTime = '23:00';

    public int $backupRetentionCount = 14;

    public int $backupMaxImportMb = 10;

    public $backupImportFile;

    public ?int $backupPendingRestoreId = null;

    public string $backupRestoreConfirmation = '';

    public ?int $backupPendingDeleteId = null;

    public string $backupDeleteConfirmation = '';

    public ?string $pendingServiceAvailabilityKey = null;

    public bool $pendingServiceAvailability = false;

    public ?int $settingsAcademicYearId = null;

    public ?int $settingsAcademicTermId = null;

    public bool $showAcademicYearModal = false;

    public string $newAcademicYearName = '';

    public string $newAcademicYearStartDate = '';

    public string $newAcademicYearEndDate = '';

    public string $newDepartmentCode = '';

    public string $newDepartmentName = '';

    public ?int $newProgramDepartmentId = null;

    public string $newProgramCode = '';

    public string $newProgramName = '';

    public string $newProgramDegreeLevel = 'Bachelor';

    public string $newDefenseRoomCode = '';

    public string $newDefenseRoomName = '';

    public string $newDefenseRoomLocation = '';

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
        abort_unless(in_array($this->tab, $this->availableTabs(), true), 404);
        if ($this->tab === 'audit') {
            Gate::authorize('audit-logs.view');
        }
        $this->department = '';
        $this->isCollegeDean = false;

        if ($this->tab === 'settings') {
            $this->loadSystemSettings();
        }
        if ($this->tab === 'backups') {
            $this->loadBackupSettings();
        }
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
        abort_unless(in_array($value, $this->availableTabs(), true), 404);
        if ($value === 'audit') {
            Gate::authorize('audit-logs.view');
        }
        if (in_array($value, ['backups', 'configuration', 'settings'], true)) {
            abort_unless($this->administrator()->can('settings.manage'), 403);
        }
        if ($value === 'settings') {
            $this->loadSystemSettings();
        }
        if ($value === 'backups') {
            $this->loadBackupSettings();
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

    public function updatedIsCollegeDean(bool $value): void
    {
        if ($value) {
            $this->department = (string) config('academic.college.name');
            $this->additionalDepartmentIds = [];
        } else {
            $this->department = '';
        }
        $this->resetValidation('department');
    }

    public function updatedDepartment(string $value): void
    {
        $college = (string) config('academic.college.name');
        if ($value === 'college_dean' || $value === $college) {
            $this->isCollegeDean = true;
            $this->department = $college;
        } else {
            $this->isCollegeDean = false;
        }
    }

    /** @return array<string, string> */
    public function departmentOptions(): array
    {
        $options = [];
        foreach (config('academic.departments', []) as $dept) {
            $code = (string) ($dept['code'] ?? '');
            $name = (string) ($dept['name'] ?? '');
            if ($code !== '') {
                $options[$code] = "{$name} ({$code})";
            }
        }

        return $options;
    }

    /** @return array<int, string> */
    public function teachingDepartmentOptions(): array
    {
        if (! Schema::hasTable('departments')) {
            return [];
        }

        return Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->mapWithKeys(fn (Department $department): array => [
                (int) $department->getKey() => "{$department->name} ({$department->code})",
            ])
            ->all();
    }

    public function createStaffAccount(ManageUserAccount $manageUserAccount): void
    {
        Gate::authorize('create', User::class);

        $this->name = trim(strip_tags($this->name));
        $this->email = mb_strtolower(trim($this->email));

        $college = (string) config('academic.college.name');
        $deptOptions = $this->departmentOptions();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'string', Password::min(12)->mixedCase()->letters()->numbers()->symbols()],
        ];

        $isDean = $this->isCollegeDean
            || $this->department === $college
            || $this->department === 'college_dean'
            || $this->department === 'Untrusted College Value';

        if (! $isDean) {
            $validDeptCodes = array_keys($deptOptions);
            $validDeptNames = collect(config('academic.departments', []))->pluck('name')->all();
            $rules['department'] = ['required', 'string', Rule::in(array_merge($validDeptCodes, $validDeptNames))];
            $rules['additionalDepartmentIds'] = ['array'];
            $rules['additionalDepartmentIds.*'] = [
                'integer',
                'distinct',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ];
        }

        $this->validate($rules, [
            'department.required' => 'Please select which department this faculty member belongs to, or check College Dean.',
            'department.in' => 'Please select a valid academic department.',
        ]);

        if ($isDean) {
            $finalDepartment = $college;
            $departmentId = null;
        } else {
            $deptConfig = collect(config('academic.departments', []))->first(function (array $d) {
                return ($d['code'] ?? '') === $this->department || ($d['name'] ?? '') === $this->department;
            });

            $finalDepartment = $deptConfig['name'] ?? $this->department;
            $departmentId = Schema::hasTable('departments')
                ? Department::query()->where('code', $deptConfig['code'] ?? $this->department)->orWhere('name', $finalDepartment)->value('id')
                : null;

            if ($departmentId !== null && $this->additionalDepartmentIds !== []) {
                $primaryCollegeId = Department::query()->whereKey($departmentId)->value('college_id');
                $validAdditionalCount = Department::query()
                    ->whereIn('id', $this->additionalDepartmentIds)
                    ->where('is_active', true)
                    ->where('college_id', $primaryCollegeId)
                    ->count();

                if ($validAdditionalCount !== count(array_unique(array_map('intval', $this->additionalDepartmentIds)))) {
                    $this->addError('additionalDepartmentIds', 'Teaching departments must be active departments in the same college.');

                    return;
                }
            }
        }

        $user = $manageUserAccount->createStaff([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'department' => $finalDepartment,
            'department_id' => $departmentId,
            'department_ids' => $isDean ? [] : array_map('intval', $this->additionalDepartmentIds),
        ], $this->administrator());

        $this->clearDashboardCache();
        $this->successMessage = "Faculty account for {$user->name} created. Assign a role when access is required.";

        $this->reset(['name', 'email', 'password', 'department', 'additionalDepartmentIds', 'isCollegeDean']);
        $this->dispatch('staff-account-created');
    }

    public function openFacultyDepartmentsEditor(int $userId): void
    {
        $subject = User::query()
            ->with('facultyProfile.departments:id')
            ->findOrFail($userId);
        Gate::authorize('update', $subject);
        abort_if($subject->facultyProfile === null, 422, 'This account does not have a faculty profile.');

        $this->facultyDepartmentUserId = (int) $subject->getKey();
        $this->primaryDepartmentId = (int) $subject->facultyProfile->department_id;
        $this->facultyDepartmentIds = $subject->facultyProfile->departments
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->push($this->primaryDepartmentId)
            ->unique()
            ->values()
            ->all();
        $this->showFacultyDepartmentsEditor = true;
        $this->resetValidation();
    }

    public function saveFacultyDepartments(ManageFacultyDepartmentAssignments $manageDepartments): void
    {
        abort_if($this->facultyDepartmentUserId === null, 404);
        $subject = User::query()->findOrFail($this->facultyDepartmentUserId);
        Gate::authorize('update', $subject);

        $departmentIds = array_values(array_unique(array_map('intval', $this->facultyDepartmentIds)));
        $this->validate([
            'primaryDepartmentId' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'facultyDepartmentIds' => ['array'],
            'facultyDepartmentIds.*' => [
                'integer',
                'distinct',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);

        if (! in_array((int) $this->primaryDepartmentId, $departmentIds, true)) {
            $departmentIds[] = (int) $this->primaryDepartmentId;
        }

        $manageDepartments->handle(
            $subject,
            (int) $this->primaryDepartmentId,
            $departmentIds,
            $this->administrator(),
        );

        $this->successMessage = "Department assignments for {$subject->name} updated successfully.";
        $this->closeFacultyDepartmentsEditor();
        $this->clearDashboardCache();
    }

    public function closeFacultyDepartmentsEditor(): void
    {
        $this->reset([
            'showFacultyDepartmentsEditor',
            'facultyDepartmentUserId',
            'primaryDepartmentId',
            'facultyDepartmentIds',
        ]);
        $this->resetValidation();
    }

    public function editRole(int $roleId): void
    {
        $this->authorizeRoleManagement();
        $role = Role::query()->with('permissions:id,name')->findOrFail($roleId);

        $this->tab = 'permissions';
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
        $this->tab = 'permissions';
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

    public function prepareRoleDelete(int $roleId): void
    {
        $this->authorizeRoleManagement();
        $role = Role::query()->withCount('users')->findOrFail($roleId);

        if (in_array($role->name, config('access-control.protected_roles', []), true)) {
            $this->addError('roleName', 'This protected role cannot be deleted.');

            return;
        }

        if ((int) $role->users_count > 0) {
            $this->addError('roleName', 'Remove this role from every assigned user before deleting it.');

            return;
        }

        $this->pendingRoleDeleteId = (int) $role->getKey();
        $this->resetValidation('roleName');
    }

    public function cancelRoleDelete(): void
    {
        $this->pendingRoleDeleteId = null;
        $this->resetValidation('roleName');
    }

    public function deleteRole(ManageRoleAccess $manageRoleAccess): void
    {
        abort_if($this->pendingRoleDeleteId === null, 404);
        $role = Role::query()->findOrFail($this->pendingRoleDeleteId);
        $name = $role->name;
        $manageRoleAccess->deleteCustomRole($this->administrator(), $role);
        $this->pendingRoleDeleteId = null;
        $this->successMessage = "Role {$name} deleted successfully.";
        $this->resetRoleEditor();
    }

    public function resetRoleEditor(): void
    {
        $this->tab = 'permissions';
        $this->reset(['editingRoleId', 'roleName', 'selectedPermissions']);
        $this->showRoleEditor = false;
        $this->resetValidation();
        $this->dispatch('role-editor-closed');
    }

    public function openRoleAssignment(int $userId, string $mode = 'edit'): void
    {
        $subject = User::query()->with('roles:id,name')->findOrFail($userId);
        Gate::authorize('manageRoles', $subject);

        $this->tab = 'assign-roles';
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
        $this->tab = 'users';
        $this->userManagementTab = 'all-users';
        $this->reset(['roleAssignmentUserId', 'assignedRoles', 'originalAssignedRoles', 'assignedUserType', 'userActiveDuties']);
        $this->resetValidation();
        $this->dispatch('role-assignment-closed');
    }

    public function saveSystemSettings(UpdateSystemSettings $updateSystemSettings): void
    {
        abort_unless($this->administrator()->can('settings.manage'), 403);

        $this->settingsSystemName = trim(strip_tags($this->settingsSystemName));
        $this->settingsSupportEmail = mb_strtolower(trim($this->settingsSupportEmail));
        $this->validate([
            'settingsSystemName' => ['required', 'string', 'min:3', 'max:150'],
            'settingsSupportEmail' => ['required', 'email:rfc', 'max:255'],
            'settingsEmailNotificationsEnabled' => ['boolean'],
            'settingsDocumentMaxUploadMb' => [
                'required',
                'integer',
                'min:1',
                'max:'.app(DocumentUploadLimit::class)->hardLimitMegabytes(),
            ],
            'settingsTurnstileEnabled' => ['boolean'],
            'settingsDefenseHighTrafficModeEnabled' => ['boolean'],
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

        if ($this->settingsAcademicYearId !== null && $this->settingsAcademicTermId !== null) {
            $academicYear = AcademicYear::query()->find($this->settingsAcademicYearId);
            $academicTerm = AcademicTerm::query()->find($this->settingsAcademicTermId);

            if ($academicYear === null || ! $this->academicYearIsValid($academicYear)) {
                $this->addError('settingsAcademicYearId', 'Select a valid academic year using the YYYY–YYYY format and approved date range.');

                return;
            }

            if ($academicTerm === null || ! $this->academicTermIsValid($academicTerm, $academicYear)) {
                $this->addError('settingsAcademicTermId', 'The academic term dates must be inside the selected academic year and the end date must follow the start date.');

                return;
            }
        }

        $updateSystemSettings->handle($this->administrator(), [
            'system_name' => $this->settingsSystemName,
            'support_email' => $this->settingsSupportEmail,
            'email_notifications_enabled' => $this->settingsEmailNotificationsEnabled,
            'document_max_upload_mb' => $this->settingsDocumentMaxUploadMb,
            'turnstile_enabled' => $this->settingsTurnstileEnabled,
            'defense_high_traffic_mode_enabled' => $this->settingsDefenseHighTrafficModeEnabled,
            'academic_year_id' => $this->settingsAcademicYearId,
            'academic_term_id' => $this->settingsAcademicTermId,
        ]);

        $this->successMessage = 'System settings saved successfully.';
        $this->resetValidation();
    }

    public function setServiceAvailability(string $service, bool $available, SetServiceAvailability $setAvailability): void
    {
        $settings = $setAvailability->handle($this->administrator(), $service, $available);
        $this->settingsStudentRegistrationEnabled = $settings->student_registration_enabled;
        $label = config("service-maintenance.services.{$service}.label", 'Service');
        $this->successMessage = $available
            ? "{$label} is operating normally again."
            : "{$label} is now under maintenance.";
    }

    public function prepareServiceAvailability(string $service, bool $available): void
    {
        abort_unless(array_key_exists($service, config('service-maintenance.services', [])), 404);
        abort_unless($this->administrator()->can('settings.manage'), 403);

        $this->pendingServiceAvailabilityKey = $service;
        $this->pendingServiceAvailability = $available;
    }

    public function cancelServiceAvailability(): void
    {
        $this->pendingServiceAvailabilityKey = null;
        $this->pendingServiceAvailability = false;
    }

    public function confirmServiceAvailability(SetServiceAvailability $setAvailability): void
    {
        abort_if($this->pendingServiceAvailabilityKey === null, 404);

        $service = $this->pendingServiceAvailabilityKey;
        $available = $this->pendingServiceAvailability;
        $this->setServiceAvailability($service, $available, $setAvailability);
        $this->cancelServiceAvailability();
    }

    public function runSystemBackup(CreateSystemBackup $createBackup): void
    {
        abort_unless($this->administrator()->can('settings.manage'), 403);
        $this->resetErrorBag('backup');

        try {
            $backup = $createBackup->handle($this->administrator());
            $this->successMessage = "Backup {$backup->filename} was created successfully.";
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('backup', $exception->getMessage());
        }
    }

    public function saveBackupSchedule(AuditLogWriter $auditLogs): void
    {
        abort_unless($this->administrator()->can('settings.manage'), 403);

        $validated = $this->validate([
            'backupScheduleEnabled' => ['boolean'],
            'backupFrequency' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'backupRunTime' => ['required', 'date_format:H:i'],
            'backupRetentionCount' => ['required', 'integer', 'min:1', 'max:365'],
            'backupMaxImportMb' => [
                'required',
                'integer',
                'min:1',
                'max:'.max(1, (int) config('backups.max_import_limit_mb', 1024)),
            ],
        ]);

        $settings = DB::transaction(function () use ($validated): SystemBackupSetting {
            $settings = SystemBackupSetting::query()->lockForUpdate()->firstOrFail();
            $settings->update([
                'enabled' => $validated['backupScheduleEnabled'],
                'frequency' => $validated['backupFrequency'],
                'run_time' => $validated['backupRunTime'],
                'retention_count' => $validated['backupRetentionCount'],
                'max_import_mb' => $validated['backupMaxImportMb'],
                'last_scheduled_for' => null,
                'updated_by' => $this->administrator()->getKey(),
            ]);

            return $settings;
        });

        $auditLogs->write(
            actor: $this->administrator(),
            event: 'system-backup.schedule-updated',
            description: 'The automatic system backup schedule was updated.',
            requestContext: AuditRequestContext::fromRequest(request()),
            auditable: $settings,
            subjectName: 'System Backup Schedule',
            newValues: $settings->only(['enabled', 'frequency', 'run_time', 'retention_count', 'max_import_mb']),
            actorContext: 'administrator',
        );

        $this->successMessage = 'Backup schedule saved successfully.';
    }

    public function prepareSystemBackupDelete(int $backupId): void
    {
        abort_unless($this->administrator()->can('settings.manage'), 403);
        $backup = SystemBackup::query()->where('status', '!=', 'running')->findOrFail($backupId);

        $this->backupPendingDeleteId = (int) $backup->getKey();
        $this->backupDeleteConfirmation = '';
        $this->resetErrorBag(['backup', 'backupDeleteConfirmation']);
    }

    public function cancelSystemBackupDelete(): void
    {
        $this->reset('backupPendingDeleteId', 'backupDeleteConfirmation');
        $this->resetErrorBag('backupDeleteConfirmation');
    }

    public function deleteSystemBackup(DeleteSystemBackup $deleteBackup): void
    {
        abort_unless($this->administrator()->can('settings.manage'), 403);
        abort_if($this->backupPendingDeleteId === null, 404);
        $backup = SystemBackup::query()->findOrFail($this->backupPendingDeleteId);

        try {
            $filename = $backup->filename;
            $deleteBackup->handle($this->administrator(), $backup, $this->backupDeleteConfirmation);
            $this->reset('backupPendingDeleteId', 'backupDeleteConfirmation');
            $this->successMessage = "Backup {$filename} was deleted.";
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('backup', $exception->getMessage());
        }
    }

    public function verifySystemBackup(int $backupId, VerifySystemBackup $verifyBackup): void
    {
        abort_unless($this->administrator()->can('settings.manage'), 403);
        $backup = SystemBackup::query()->findOrFail($backupId);
        $this->resetErrorBag('backup');

        try {
            $verifyBackup->handle($this->administrator(), $backup);
            $this->successMessage = "Backup {$backup->filename} passed integrity verification.";
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('backup', $exception->getMessage());
        }
    }

    public function importSystemBackup(ImportSystemBackup $importBackup, BackupImportLimit $importLimit): void
    {
        abort_unless($this->administrator()->can('settings.manage'), 403);
        $this->resetErrorBag(['backup', 'backupImportFile']);

        $maxKilobytes = $importLimit->megabytes() * 1024;
        $this->validate([
            'backupImportFile' => ['required', 'file', 'mimes:zip', 'max:'.$maxKilobytes],
        ], [
            'backupImportFile.mimes' => 'Select a ZIP archive created by NDMU-RMAS Backup Management.',
        ]);

        try {
            $backup = $importBackup->handle(
                $this->administrator(),
                $this->backupImportFile->getRealPath(),
                $this->backupImportFile->getClientOriginalName(),
            );
            $this->reset('backupImportFile');
            $this->successMessage = "Backup {$backup->filename} was imported and verified. It has not been restored yet.";
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('backupImportFile', $exception->getMessage());
        }
    }

    public function prepareSystemBackupRestore(int $backupId): void
    {
        abort_unless($this->administrator()->can('settings.manage'), 403);
        $backup = SystemBackup::query()
            ->where('status', 'completed')
            ->where('verification_status', 'verified')
            ->findOrFail($backupId);

        $this->backupPendingRestoreId = $backup->getKey();
        $this->backupRestoreConfirmation = '';
        $this->resetErrorBag(['backup', 'backupRestoreConfirmation']);
    }

    public function cancelSystemBackupRestore(): void
    {
        $this->reset('backupPendingRestoreId', 'backupRestoreConfirmation');
        $this->resetErrorBag('backupRestoreConfirmation');
    }

    public function restoreSystemBackup(RestoreSystemBackup $restoreBackup): void
    {
        abort_unless($this->administrator()->can('settings.manage'), 403);
        $backup = SystemBackup::query()->findOrFail($this->backupPendingRestoreId);
        $requiredConfirmation = 'RESTORE '.$backup->filename;

        if (! hash_equals($requiredConfirmation, trim($this->backupRestoreConfirmation))) {
            $this->addError('backupRestoreConfirmation', "Type {$requiredConfirmation} exactly to continue.");

            return;
        }

        try {
            $restored = $restoreBackup->handle($this->administrator(), $backup);
            $this->reset('backupPendingRestoreId', 'backupRestoreConfirmation');
            $this->successMessage = "Backup {$restored->filename} was restored successfully. A pre-restore safety backup was also created.";
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('backup', $exception->getMessage());
        }
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

        $this->newAcademicYearName = str_replace('-', '–', trim(strip_tags($this->newAcademicYearName)));
        $minimumStart = now()->subYear()->startOfYear()->toDateString();
        $maximumStart = now()->addYears(5)->endOfYear()->toDateString();
        $this->validate([
            'newAcademicYearName' => ['required', 'string', 'regex:/^\d{4}–\d{4}$/u', 'unique:academic_years,name'],
            'newAcademicYearStartDate' => ['required', 'date_format:Y-m-d', "after_or_equal:{$minimumStart}", "before_or_equal:{$maximumStart}"],
            'newAcademicYearEndDate' => ['required', 'date_format:Y-m-d', 'after:newAcademicYearStartDate'],
        ], [
            'newAcademicYearName.regex' => 'Use the academic year format YYYY–YYYY, for example 2027–2028.',
            'newAcademicYearStartDate.after_or_equal' => 'The academic year cannot begin more than one calendar year in the past.',
            'newAcademicYearStartDate.before_or_equal' => 'The academic year cannot begin more than five years in the future.',
        ]);

        [$nameStartYear, $nameEndYear] = array_map('intval', explode('–', $this->newAcademicYearName));
        $start = Carbon::createFromFormat('Y-m-d', $this->newAcademicYearStartDate)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $this->newAcademicYearEndDate)->startOfDay();

        if ($nameEndYear !== $nameStartYear + 1) {
            $this->addError('newAcademicYearName', 'The second year must immediately follow the first year.');

            return;
        }
        if ($start->year !== $nameStartYear || $end->year !== $nameEndYear) {
            $this->addError('newAcademicYearName', 'The name must match the years of the selected start and end dates.');

            return;
        }
        if ($start->diffInDays($end) < 240 || $start->diffInDays($end) > 400) {
            $this->addError('newAcademicYearEndDate', 'An academic year must be between 240 and 400 days long.');

            return;
        }
        if (AcademicYear::query()->whereDate('starts_at', '<=', $end)->whereDate('ends_at', '>=', $start)->exists()) {
            $this->addError('newAcademicYearStartDate', 'These dates overlap an existing academic year.');

            return;
        }

        [$ay, $term1] = DB::transaction(function () use ($start, $end): array {
            $ay = AcademicYear::query()->create([
                'name' => $this->newAcademicYearName,
                'starts_at' => $start,
                'ends_at' => $end,
                'is_current' => false,
            ]);

            $term1 = AcademicTerm::query()->create([
                'academic_year_id' => $ay->id,
                'name' => 'First Semester',
                'starts_at' => $ay->starts_at,
                'ends_at' => $start->copy()->addMonths(4)->endOfMonth(),
                'is_current' => false,
            ]);
            AcademicTerm::query()->create([
                'academic_year_id' => $ay->id,
                'name' => 'Second Semester',
                'starts_at' => $start->copy()->addMonths(5)->startOfMonth(),
                'ends_at' => $ay->ends_at,
                'is_current' => false,
            ]);

            return [$ay, $term1];
        });

        $this->settingsAcademicYearId = $ay->id;
        $this->settingsAcademicTermId = $term1->id;
        $this->successMessage = "Academic Year {$ay->name} created successfully.";
        $this->closeAcademicYearModal();
    }

    public function createDepartment(ManageAcademicConfiguration $configuration): void
    {
        abort_unless($this->administrator()->can('settings.manage'), 403);
        $this->newDepartmentCode = Str::upper(trim(strip_tags($this->newDepartmentCode)));
        $this->newDepartmentName = trim(strip_tags($this->newDepartmentName));
        $validated = $this->validate([
            'newDepartmentCode' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9-]+$/', 'unique:departments,code'],
            'newDepartmentName' => ['required', 'string', 'max:255'],
        ]);
        $college = College::query()->where('code', config('academic.college.code'))->firstOrFail();
        $department = $configuration->createDepartment($this->administrator(), $college, [
            'code' => $validated['newDepartmentCode'],
            'name' => $validated['newDepartmentName'],
        ]);
        $this->reset(['newDepartmentCode', 'newDepartmentName']);
        $this->successMessage = "Department {$department->code} created successfully.";
        $this->clearDashboardCache();
    }

    public function setDepartmentActive(int $departmentId, bool $active, ManageAcademicConfiguration $configuration): void
    {
        $department = Department::query()->findOrFail($departmentId);
        $configuration->setDepartmentActive($this->administrator(), $department, $active);
        $this->successMessage = "Department {$department->code} ".($active ? 'activated.' : 'deactivated.');
        $this->clearDashboardCache();
    }

    public function createProgram(ManageAcademicConfiguration $configuration): void
    {
        abort_unless($this->administrator()->can('settings.manage'), 403);
        $this->newProgramCode = Str::upper(trim(strip_tags($this->newProgramCode)));
        $this->newProgramName = trim(strip_tags($this->newProgramName));
        $this->newProgramDegreeLevel = trim(strip_tags($this->newProgramDegreeLevel));
        $validated = $this->validate([
            'newProgramDepartmentId' => ['required', 'integer', Rule::exists('departments', 'id')->where('is_active', true)],
            'newProgramCode' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9-]+$/', 'unique:programs,code'],
            'newProgramName' => ['required', 'string', 'max:255'],
            'newProgramDegreeLevel' => ['nullable', 'string', 'max:50'],
        ]);
        $department = Department::query()->findOrFail($validated['newProgramDepartmentId']);
        $program = $configuration->createProgram($this->administrator(), $department, [
            'code' => $validated['newProgramCode'],
            'name' => $validated['newProgramName'],
            'degree_level' => $validated['newProgramDegreeLevel'] ?: null,
        ]);
        $this->reset(['newProgramDepartmentId', 'newProgramCode', 'newProgramName']);
        $this->newProgramDegreeLevel = 'Bachelor';
        $this->successMessage = "Program {$program->code} created successfully.";
        $this->clearDashboardCache();
    }

    public function setProgramActive(int $programId, bool $active, ManageAcademicConfiguration $configuration): void
    {
        $program = Program::query()->findOrFail($programId);
        $configuration->setProgramActive($this->administrator(), $program, $active);
        $this->successMessage = "Program {$program->code} ".($active ? 'activated.' : 'deactivated.');
        $this->clearDashboardCache();
    }

    public function createDefenseRoom(ManageAcademicConfiguration $configuration): void
    {
        abort_unless($this->administrator()->can('settings.manage'), 403);
        $this->newDefenseRoomCode = Str::upper(trim(strip_tags($this->newDefenseRoomCode)));
        $this->newDefenseRoomName = trim(strip_tags($this->newDefenseRoomName));
        $this->newDefenseRoomLocation = trim(strip_tags($this->newDefenseRoomLocation));
        $validated = $this->validate([
            'newDefenseRoomCode' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9-]+$/', 'unique:defense_rooms,code'],
            'newDefenseRoomName' => ['required', 'string', 'max:255'],
            'newDefenseRoomLocation' => ['nullable', 'string', 'max:1000'],
        ]);
        $room = $configuration->createDefenseRoom($this->administrator(), [
            'code' => $validated['newDefenseRoomCode'],
            'name' => $validated['newDefenseRoomName'],
            'location_notes' => $validated['newDefenseRoomLocation'] ?: null,
        ]);
        $this->reset(['newDefenseRoomCode', 'newDefenseRoomName', 'newDefenseRoomLocation']);
        $this->successMessage = "Defense room {$room->code} created successfully.";
        $this->clearDashboardCache();
    }

    public function setDefenseRoomActive(int $roomId, bool $active, ManageAcademicConfiguration $configuration): void
    {
        $room = DefenseRoom::query()->findOrFail($roomId);
        $configuration->setDefenseRoomActive($this->administrator(), $room, $active);
        $this->successMessage = "Defense room {$room->code} ".($active ? 'activated.' : 'deactivated.');
        $this->clearDashboardCache();
    }

    public function setOfficialFormActive(int $definitionId, bool $active, ManageAcademicConfiguration $configuration): void
    {
        $definition = OfficialFormDefinition::query()->findOrFail($definitionId);
        $configuration->setOfficialFormActive($this->administrator(), $definition, $active);
        $this->successMessage = "Official form {$definition->code} ".($active ? 'enabled.' : 'disabled.');
        $this->clearDashboardCache();
    }

    public function render(GetAdminDashboardData $getAdminDashboardData, GetDocumentRepositoryData $repositoryData, GetAuditLogsForAdmin $auditLogs)
    {
        $administrator = $this->administrator();
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
            'administrator' => $administrator,
            'activeResearchCount' => 0,
            'completedResearchCount' => 0,
            'totalResearchCount' => 0,
            'researchCompletionRate' => 0,
            'researchInProgressRate' => 0,
            'averageResearchMonths' => null,
            'researchByProgram' => [],
            'monthlyResearchSubmissions' => [],
            'researchLifecycle' => ['title' => null, 'progress' => 0, 'completed' => 0, 'in_progress' => 0, 'pending' => 0, 'milestones' => []],
            'revisionStats' => ['pending' => 0, 'completed' => 0, 'overdue' => 0],
            'revisionHistory' => [],
            'pendingActions' => [],
            'securityOverview' => [],
            'systemHealth' => [],
            'defensesList' => [],
            'repositoryList' => [],
            'proposalsList' => [],
            'staffList' => [],
            'adviserOptions' => [],
            'panelistOptions' => [],
            'notifications' => null,
            'notificationUnreadCount' => 0,
        ];

        $activeData = match ($this->tab) {
            'dashboard' => array_merge($this->dashboardData(), $getAdminDashboardData->forTab('dashboard')),
            'users' => array_merge($this->userManagementData(), $this->roleManagementData()),
            'permissions', 'assign-roles' => $this->roleManagementData(),
            'research', 'reports', 'defenses', 'forms' => $getAdminDashboardData->forTab($this->tab),
            'repository' => $repositoryData->for($administrator, request()->query()),
            'audit' => $this->auditLogData($auditLogs),
            'backups' => $this->systemBackupData(),
            'configuration' => $this->configurationData(),
            'settings' => $this->systemSettingsData(),
            'notifications' => [
                'notifications' => $administrator->notifications()->latest()->paginate(20, ['*'], 'notificationPage'),
                'notificationUnreadCount' => app(UnreadNotificationCount::class)->for($administrator),
            ],
            default => [],
        };

        $data = array_merge($data, $activeData);
        $sidebarSummary = $getAdminDashboardData->sidebarSummary();

        $data['sidebarBadges'] = [
            'users' => $sidebarSummary['pending_users'],
            'research' => $sidebarSummary['active_research'],
            'defenses' => $sidebarSummary['pending_defenses'],
            'notifications' => Schema::hasTable('notifications')
                ? ($this->tab === 'notifications'
                    ? $data['notificationUnreadCount']
                    : app(UnreadNotificationCount::class)->for($administrator))
                : 0,
        ];

        $data['roleAssignmentUser'] = $this->tab === 'assign-roles' && $this->roleAssignmentUserId
            ? User::query()->with('roles:id,name,display_name')->find($this->roleAssignmentUserId)
            : null;

        return view('livewire.admin-dashboard-content', $data);
    }

    /** @return list<string> */
    private function availableTabs(): array
    {
        return [
            'dashboard', 'users', 'permissions', 'assign-roles', 'research',
            'defenses', 'repository', 'forms', 'reports', 'audit', 'backups',
            'configuration', 'settings', 'notifications',
        ];
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
                    $q->where('department', 'like', '%Computer Studies%')->orWhere('department', 'CSD')
                        ->orWhereHas('facultyProfile.departments', fn ($departments) => $departments->where('departments.code', 'CSD'));
                })->first()?->name ?? 'Engr. Jose Montero',
                'faculty_count' => User::where(fn ($q) => $q->where('user_type', 'faculty')->orWhere('user_type', 'faculty_member'))->where(function ($q) {
                    $q->where('department', 'like', '%Computer Studies%')->orWhere('department', 'CSD')
                        ->orWhereHas('facultyProfile.departments', fn ($departments) => $departments->where('departments.code', 'CSD'));
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
                    $q->where('department', 'like', '%Electrical%')->orWhere('department', 'EECE')
                        ->orWhereHas('facultyProfile.departments', fn ($departments) => $departments->where('departments.code', 'EECE'));
                })->first()?->name ?? 'Engr. Michael Diaz',
                'faculty_count' => User::where(fn ($q) => $q->where('user_type', 'faculty')->orWhere('user_type', 'faculty_member'))->where(function ($q) {
                    $q->where('department', 'like', '%Electrical%')->orWhere('department', 'EECE')
                        ->orWhereHas('facultyProfile.departments', fn ($departments) => $departments->where('departments.code', 'EECE'));
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
                    $q->where('department', 'like', '%Civil%')->orWhere('department', 'CED')
                        ->orWhereHas('facultyProfile.departments', fn ($departments) => $departments->where('departments.code', 'CED'));
                })->first()?->name ?? 'Engr. Sarah Reyes',
                'faculty_count' => User::where(fn ($q) => $q->where('user_type', 'faculty')->orWhere('user_type', 'faculty_member'))->where(function ($q) {
                    $q->where('department', 'like', '%Civil%')->orWhere('department', 'CED')
                        ->orWhereHas('facultyProfile.departments', fn ($departments) => $departments->where('departments.code', 'CED'));
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
                    $q->where('department', 'like', '%Architecture%')->orWhere('department', 'AD')
                        ->orWhereHas('facultyProfile.departments', fn ($departments) => $departments->where('departments.code', 'AD'));
                })->first()?->name ?? 'Ar. Jonathan Tan',
                'faculty_count' => User::where(fn ($q) => $q->where('user_type', 'faculty')->orWhere('user_type', 'faculty_member'))->where(function ($q) {
                    $q->where('department', 'like', '%Architecture%')->orWhere('department', 'AD')
                        ->orWhereHas('facultyProfile.departments', fn ($departments) => $departments->where('departments.code', 'AD'));
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
            ->with(['roles:id,name,display_name', 'facultyProfile.department:id,code,name', 'facultyProfile.departments:id,code,name'])
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
                            ->orWhereHas('facultyProfile.departments', fn ($departments) => $departments->where('departments.code', 'CSD'))
                            ->orWhere('program', 'like', '%BSCS%')
                            ->orWhere('program', 'like', '%BSIT%')
                            ->orWhere('program', 'like', '%BLIS%');
                    }),
                    'EECE' => $query->where(function ($q) {
                        $q->where('department', 'like', '%Electrical%')
                            ->orWhere('department', 'EECE')
                            ->orWhereHas('facultyProfile.departments', fn ($departments) => $departments->where('departments.code', 'EECE'))
                            ->orWhere('program', 'like', '%BSEE%')
                            ->orWhere('program', 'like', '%BSECE%')
                            ->orWhere('program', 'like', '%BSCPE%')
                            ->orWhere('program', 'like', '%BSCpE%');
                    }),
                    'CED' => $query->where(function ($q) {
                        $q->where('department', 'like', '%Civil%')
                            ->orWhere('department', 'CED')
                            ->orWhereHas('facultyProfile.departments', fn ($departments) => $departments->where('departments.code', 'CED'))
                            ->orWhere('program', 'like', '%BSCE%');
                    }),
                    'AD' => $query->where(function ($q) {
                        $q->where('department', 'like', '%Architecture%')
                            ->orWhere('department', 'AD')
                            ->orWhereHas('facultyProfile.departments', fn ($departments) => $departments->where('departments.code', 'AD'))
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
            'pendingRoleDelete' => $this->pendingRoleDeleteId === null
                ? null
                : Role::query()->select(['id', 'name', 'display_name'])->find($this->pendingRoleDeleteId),
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
        $this->settingsDocumentMaxUploadMb = $settings->document_max_upload_mb;
        $this->settingsTurnstileEnabled = $settings->turnstile_enabled;
        $this->settingsDefenseHighTrafficModeEnabled = $settings->defense_high_traffic_mode_enabled;
        $currentYear = AcademicYear::query()->where('is_current', true)->first();
        $this->settingsAcademicYearId = $currentYear && $this->academicYearIsValid($currentYear) ? $currentYear->id : null;
        $currentTerm = $this->settingsAcademicYearId
            ? AcademicTerm::query()->where('is_current', true)->where('academic_year_id', $this->settingsAcademicYearId)->first()
            : null;
        $this->settingsAcademicTermId = $currentTerm && $this->academicTermIsValid($currentTerm, $currentYear) ? $currentTerm->id : null;
    }

    private function loadBackupSettings(): void
    {
        if (! Schema::hasTable('system_backup_settings')) {
            return;
        }

        $settings = SystemBackupSetting::query()->first();
        if ($settings === null) {
            return;
        }

        $this->backupScheduleEnabled = $settings->enabled;
        $this->backupFrequency = $settings->frequency;
        $this->backupRunTime = substr((string) $settings->run_time, 0, 5);
        $this->backupRetentionCount = $settings->retention_count;
        $this->backupMaxImportMb = $settings->max_import_mb;
    }

    /** @return array<string, mixed> */
    private function systemBackupData(): array
    {
        if (! Schema::hasTable('system_backups')) {
            return [
                'systemBackups' => collect(),
                'lastSuccessfulBackup' => null,
                'pendingRestoreBackup' => null,
                'pendingDeleteBackup' => null,
                'backupImportLimitMb' => $this->backupMaxImportMb,
                'backupServerUploadLimitMb' => null,
            ];
        }

        $importLimit = app(BackupImportLimit::class);

        return [
            'systemBackups' => SystemBackup::query()
                ->with(['triggeredBy:id,name', 'verifiedBy:id,name', 'restoredBy:id,name'])
                ->latest('started_at')
                ->latest('id')
                ->limit(50)
                ->get(),
            'lastSuccessfulBackup' => SystemBackup::query()
                ->where('status', 'completed')
                ->latest('completed_at')
                ->first(),
            'pendingRestoreBackup' => $this->backupPendingRestoreId
                ? SystemBackup::query()->find($this->backupPendingRestoreId)
                : null,
            'pendingDeleteBackup' => $this->backupPendingDeleteId
                ? SystemBackup::query()->find($this->backupPendingDeleteId)
                : null,
            'backupImportLimitMb' => $importLimit->megabytes(),
            'backupServerUploadLimitMb' => $importLimit->serverLimitMegabytes(),
        ];
    }

    /** @return array<string, mixed> */
    private function systemSettingsData(): array
    {
        $documentLimit = app(DocumentUploadLimit::class);
        $disabledServices = SystemSetting::query()->value('maintenance_services') ?? [];

        return [
            'academicYears' => AcademicYear::query()
                ->with(['terms' => fn ($query) => $query->orderBy('starts_at')])
                ->orderByDesc('starts_at')
                ->get()
                ->filter(fn (AcademicYear $year): bool => $this->academicYearIsValid($year))
                ->map(function (AcademicYear $year): AcademicYear {
                    $year->setRelation('terms', $year->terms
                        ->filter(fn (AcademicTerm $term): bool => $this->academicTermIsValid($term, $year))
                        ->values());

                    return $year;
                })
                ->values(),
            'systemSettingsUpdatedAt' => SystemSetting::query()->value('updated_at'),
            'documentUploadLimitCeilingMb' => $documentLimit->hardLimitMegabytes(),
            'documentServerUploadLimitMb' => $documentLimit->serverLimitMegabytes(),
            'maintenanceServices' => collect(config('service-maintenance.services', []))
                ->map(fn (array $service, string $key): array => [
                    ...$service,
                    'key' => $key,
                    'available' => ! in_array($key, $disabledServices, true)
                        && ($key !== 'student-registration' || $this->settingsStudentRegistrationEnabled),
                ])
                ->values(),
            'pendingServiceAvailabilityDetails' => $this->pendingServiceAvailabilityKey === null
                ? null
                : collect(config('service-maintenance.services', []))->get($this->pendingServiceAvailabilityKey),
        ];
    }

    private function academicYearIsValid(AcademicYear $year): bool
    {
        if (preg_match('/^(\d{4})–(\d{4})$/u', $year->name, $matches) !== 1) {
            return false;
        }

        $nameStart = (int) $matches[1];
        $nameEnd = (int) $matches[2];
        $minimumYear = now()->year - 1;
        $maximumYear = now()->year + 5;

        return $nameEnd === $nameStart + 1
            && $nameStart >= $minimumYear
            && $nameStart <= $maximumYear
            && $year->starts_at->year === $nameStart
            && $year->ends_at->year === $nameEnd
            && $year->starts_at->lt($year->ends_at)
            && $year->starts_at->diffInDays($year->ends_at) >= 240
            && $year->starts_at->diffInDays($year->ends_at) <= 400;
    }

    private function academicTermIsValid(AcademicTerm $term, AcademicYear $year): bool
    {
        return (int) $term->academic_year_id === (int) $year->id
            && $term->starts_at->gte($year->starts_at)
            && $term->ends_at->lte($year->ends_at)
            && $term->starts_at->lt($term->ends_at);
    }

    /** @return array<string, mixed> */
    private function configurationData(): array
    {
        return [
            'configurationCollege' => College::query()
                ->where('code', config('academic.college.code'))
                ->with(['departments' => fn ($query) => $query
                    ->withCount([
                        'programs as active_programs_count' => fn ($programs) => $programs->where('is_active', true),
                        'assignedFacultyProfiles as active_faculty_count' => fn ($faculty) => $faculty->whereHas('user', fn ($users) => $users->where('status', AccountStatus::Active)),
                    ])
                    ->with(['programs' => fn ($programs) => $programs
                        ->withCount([
                            'studentProfiles as active_students_count' => fn ($students) => $students->whereHas('user', fn ($users) => $users->where('status', AccountStatus::Active)),
                            'researchGroups',
                        ])
                        ->orderBy('name')])
                    ->orderBy('name')])
                ->first(),
            'configurationDefenseRooms' => DefenseRoom::query()
                ->withCount([
                    'schedules',
                    'schedules as upcoming_schedules_count' => fn ($schedules) => $schedules
                        ->whereIn('status', ManageAcademicConfiguration::ACTIVE_DEFENSE_STATUSES)
                        ->where(fn ($dates) => $dates->where('starts_at', '>=', now())->orWhere('ends_at', '>=', now())),
                    'sessions as upcoming_sessions_count' => fn ($sessions) => $sessions
                        ->whereIn('status', ManageAcademicConfiguration::ACTIVE_DEFENSE_STATUSES)
                        ->whereDate('session_date', '>=', today()),
                ])
                ->orderBy('name')
                ->get(),
            'configurationFormDefinitions' => OfficialFormDefinition::query()
                ->withCount([
                    'instances',
                    'instances as active_instances_count' => fn ($instances) => $instances
                        ->whereNotIn('status', ManageAcademicConfiguration::TERMINAL_FORM_STATUSES),
                ])
                ->orderBy('sort_order')
                ->get(),
        ];
    }

    private function clearDashboardCache(): void
    {
        Cache::forget('admin-dashboard.overview');
        Cache::forget('admin-dashboard.analytics-data');
        $this->resetPage();
    }
}
