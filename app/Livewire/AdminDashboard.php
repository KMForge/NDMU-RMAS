<?php

namespace App\Livewire;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Modules\Dashboard\Queries\GetAdminDashboardData;
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

    private const CREATABLE_STAFF_ROLES = [
        'research-adviser',
        'panelist',
        'research-facilitator',
        'college-dean',
    ];

    // Search and filter inputs
    public string $searchQuery = '';

    public string $selectedRole = '';

    // Create Staff Account Form Fields
    public string $name = '';

    public string $email = '';

    public string $role = '';

    public string $department = '';

    public string $password = '';

    public ?string $successMessage = null;

    public ?int $editingRoleId = null;

    public string $roleName = '';

    /** @var list<string> */
    public array $selectedPermissions = [];

    public ?int $roleAssignmentUserId = null;

    /** @var list<string> */
    public array $assignedRoles = [];

    protected $queryString = [
        'searchQuery' => ['except' => ''],
        'selectedRole' => ['except' => ''],
    ];

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
        $this->department = (string) config('academic.college.name');
    }

    public function updatedSearchQuery(string $value): void
    {
        $this->searchQuery = mb_substr(strip_tags($value), 0, 100);
        $this->resetPage();
    }

    public function updatedSelectedRole(string $value): void
    {
        if ($value !== '' && ! Role::query()->where('guard_name', 'web')->where('name', $value)->exists()) {
            $this->selectedRole = '';
        }

        $this->resetPage();
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
            'role' => 'required|string|in:'.implode(',', self::CREATABLE_STAFF_ROLES),
            'password' => ['required', 'string', Password::min(12)->mixedCase()->letters()->numbers()->symbols()],
        ]);

        $college = (string) config('academic.college.name');

        $user = $manageUserAccount->createStaff([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'department' => $college,
            'role' => $this->role,
        ], $this->administrator());

        $this->clearDashboardCache();
        $this->successMessage = "Staff account for {$user->name} created successfully.";

        $this->reset(['name', 'email', 'role', 'password']);
        $this->department = $college;
        $this->dispatch('staff-account-created');
    }

    public function editRole(int $roleId): void
    {
        $this->authorizeRoleManagement();
        $role = Role::query()->with('permissions:id,name')->findOrFail($roleId);

        abort_if(in_array($role->name, config('access-control.protected_roles', []), true), 403);

        $this->editingRoleId = (int) $role->getKey();
        $this->roleName = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->sort()->values()->all();
        $this->resetValidation();
        $this->dispatch('role-editor-opened');
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
                Rule::notIn(config('access-control.protected_roles', [])),
                Rule::unique('roles', 'name')->ignore($this->editingRoleId),
            ],
            'selectedPermissions' => ['required', 'array', 'min:1'],
            'selectedPermissions.*' => ['string', Rule::in($catalogNames)],
        ], [
            'roleName.not_in' => 'Built-in portal role names are reserved.',
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
        $this->resetValidation();
        $this->dispatch('role-editor-closed');
    }

    public function openRoleAssignment(int $userId): void
    {
        $subject = User::query()->with('roles:id,name')->findOrFail($userId);
        Gate::authorize('manageRoles', $subject);

        $this->roleAssignmentUserId = (int) $subject->getKey();
        $this->assignedRoles = $subject->roles->pluck('name')->sort()->values()->all();
        $this->resetValidation();
        $this->dispatch('role-assignment-opened');
    }

    public function saveUserRoles(ManageRoleAccess $manageRoleAccess): void
    {
        abort_if($this->roleAssignmentUserId === null, 404);
        $subject = User::query()->findOrFail($this->roleAssignmentUserId);
        Gate::authorize('manageRoles', $subject);
        $availableRoles = Role::query()->where('guard_name', 'web')->pluck('name')->all();

        $this->validate([
            'assignedRoles' => ['required', 'array', 'min:1'],
            'assignedRoles.*' => ['string', Rule::in($availableRoles)],
        ]);

        $manageRoleAccess->syncUserRoles(
            $this->administrator(),
            $subject,
            array_values(array_unique($this->assignedRoles)),
        );

        $this->successMessage = "Roles for {$subject->name} updated successfully.";
        $this->closeRoleAssignment();
        $this->clearDashboardCache();
    }

    public function closeRoleAssignment(): void
    {
        $this->reset(['roleAssignmentUserId', 'assignedRoles']);
        $this->resetValidation();
        $this->dispatch('role-assignment-closed');
    }

    public function render(GetAdminDashboardData $getAdminDashboardData)
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
        $studentCount = User::role('student-researcher')->count();
        $adviserCount = User::role('research-adviser')->count();
        $panelistCount = User::role('panelist')->count();

        $recentUsers = User::query()
            ->with('roles:id,name')
            ->latest()
            ->limit(4)
            ->get();

        $recentActivities = [];

        foreach ($recentUsers as $user) {
            $roleLabel = $user->roles->first()?->name ?? 'User';
            $roleLabel = str_replace('-', ' ', Str::title($roleLabel));
            $recentActivities[] = [
                'text' => "New {$roleLabel} registered: {$user->email}",
                'time' => $user->created_at->diffForHumans(),
            ];
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

        $pendingStudentsQuery = User::role('student-researcher')
            ->where('status', AccountStatus::Pending)
            ->latest();

        $pendingStudents = $pendingStudentsQuery->get();

        $usersList = User::query()
            ->with('roles:id,name')
            ->when($this->searchQuery, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', '%'.$this->searchQuery.'%')
                        ->orWhere('email', 'like', '%'.$this->searchQuery.'%');
                });
            })
            ->when($this->selectedRole, function ($query) {
                $query->role($this->selectedRole);
            })
            ->orderBy('id')
            ->paginate(10);

        return [
            'totalUsersCount' => (int) $counts->total_users_count,
            'pendingApprovalCount' => $pendingStudents->count(),
            'activeAccountsCount' => (int) $counts->active_accounts_count,
            'rejectedCount' => (int) $counts->rejected_count,
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

    /** @return array<string, mixed> */
    private function roleManagementData(): array
    {
        $protected = config('access-control.protected_roles', []);

        return [
            'permissionCatalog' => config('access-control.permissions', []),
            'rolesList' => Role::query()
                ->where('guard_name', 'web')
                ->withCount(['permissions', 'users'])
                ->with('permissions:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role): array => [
                    'id' => (int) $role->getKey(),
                    'name' => $role->name,
                    'label' => Str::headline($role->name),
                    'permissions_count' => (int) $role->permissions_count,
                    'users_count' => (int) $role->users_count,
                    'protected' => in_array($role->name, $protected, true),
                    'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
                ]),
            'roleAssignmentUser' => $this->roleAssignmentUserId === null
                ? null
                : User::query()->select(['id', 'name', 'email'])->find($this->roleAssignmentUserId),
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

    private function clearDashboardCache(): void
    {
        Cache::forget('admin-dashboard.overview');
        $this->resetPage();
    }
}
