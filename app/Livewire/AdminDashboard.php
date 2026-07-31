<?php

namespace App\Livewire;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class AdminDashboard extends Component
{
    use WithPagination;

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
        $this->department = (string) config('academic.college.name');
    }

    public function approveStudent(int $userId): void
    {
        $user = User::findOrFail($userId);
        $user->update([
            'status' => AccountStatus::Active,
            'approved_at' => now(),
        ]);

        // Ensure student has role
        if (! $user->hasRole('student-researcher')) {
            $user->assignRole('student-researcher');
        }

        Cache::forget('admin-dashboard.overview');
        $this->successMessage = "Student {$user->name} has been approved.";
    }

    public function rejectStudent(int $userId): void
    {
        $user = User::findOrFail($userId);
        $user->update([
            'status' => AccountStatus::Rejected,
            'approved_at' => null,
        ]);

        Cache::forget('admin-dashboard.overview');
        $this->successMessage = "Student {$user->name} registration has been rejected.";
    }

    public function createStaffAccount(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|string|in:research-adviser,panelist,research-facilitator,college-dean',
            'password' => 'required|string|min:8',
        ]);

        $college = (string) config('academic.college.name');

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
            'department' => $college,
        ]);

        $user->assignRole($this->role);

        Cache::forget('admin-dashboard.overview');
        $this->successMessage = "Staff account for {$this->name} created successfully.";

        $this->reset(['name', 'email', 'role', 'password']);
        $this->department = $college;
        $this->dispatch('staff-account-created');
    }

    public function render()
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
        ];

        $data = array_merge(
            $data,
            $this->dashboardData(),
            $this->userManagementData(),
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

        if (count($recentActivities) < 4) {
            // Fill up with mockup data if less than 4 users
            $mockups = [
                'Research submitted: IoT Smart Agriculture',
                'Defense scheduled for May 25, 2026',
                'Document approved by Dr. Maria Santos',
                'System updates applied successfully',
            ];
            $i = 0;
            while (count($recentActivities) < 4 && $i < count($mockups)) {
                $recentActivities[] = [
                    'text' => $mockups[$i],
                    'time' => 'Recently',
                ];
                $i++;
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
}
