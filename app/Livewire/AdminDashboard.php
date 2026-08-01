<?php

namespace App\Livewire;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Modules\Dashboard\Queries\GetAdminDashboardData;
use App\Modules\UserManagement\Actions\ManageUserAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithPagination;

class AdminDashboard extends Component
{
    use WithPagination;

    private const MANAGED_ROLES = [
        'system-administrator',
        'college-dean',
        'research-facilitator',
        'research-adviser',
        'panelist',
        'student-researcher',
    ];

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
        if ($value !== '' && ! in_array($value, self::MANAGED_ROLES, true)) {
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

    private function clearDashboardCache(): void
    {
        Cache::forget('admin-dashboard.overview');
        $this->resetPage();
    }
}
