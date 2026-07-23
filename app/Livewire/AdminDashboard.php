<?php

namespace App\Livewire;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class AdminDashboard extends Component
{
    use WithPagination;

    // Navigation states
    public string $activeTab = 'dashboard';

    public string $userManagementTab = 'all-users';

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
        'activeTab' => ['except' => 'dashboard'],
        'userManagementTab' => ['except' => 'all-users'],
        'searchQuery' => ['except' => ''],
        'selectedRole' => ['except' => ''],
    ];

    public function updatedActiveTab(): void
    {
        $this->resetPage();
        $this->successMessage = null;
    }

    public function updatedUserManagementTab(): void
    {
        $this->resetPage();
        $this->successMessage = null;
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

        $this->successMessage = "Student {$user->name} has been approved.";
    }

    public function rejectStudent(int $userId): void
    {
        $user = User::findOrFail($userId);
        $user->update([
            'status' => AccountStatus::Rejected,
            'approved_at' => null,
        ]);

        $this->successMessage = "Student {$user->name} registration has been rejected.";
    }

    public function createStaffAccount(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|string|in:research-adviser,panelist,research-facilitator,college-dean',
            'department' => 'required|string|max:255',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
            'department' => $this->department,
        ]);

        $user->assignRole($this->role);

        $this->successMessage = "Staff account for {$this->name} created successfully.";

        $this->reset(['name', 'email', 'role', 'department', 'password']);
        $this->userManagementTab = 'all-users';
    }

    public function render()
    {
        // Analytics Counts
        $totalUsersCount = User::count();
        $pendingApprovalCount = User::where('status', AccountStatus::Pending)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'student-researcher');
            })
            ->count();
        $activeAccountsCount = User::where('status', AccountStatus::Active)->count();
        $rejectedCount = User::where('status', AccountStatus::Rejected)->count();

        // Admin Dashboard Statistics
        $studentCount = User::role('student-researcher')->count();
        $adviserCount = User::role('research-adviser')->count();
        $panelistCount = User::role('panelist')->count();

        // Recent Activity List (Mocked for dashboard, but fetching actual database counts/registrations is premium!)
        // Let's get the 4 most recently registered users
        $recentUsers = User::orderBy('created_at', 'desc')->take(4)->get();
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

        // Users Query (with Search and Filters)
        $usersQuery = User::with('roles')
            ->when($this->searchQuery, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->searchQuery.'%')
                        ->orWhere('email', 'like', '%'.$this->searchQuery.'%');
                });
            })
            ->when($this->selectedRole, function ($query) {
                $query->role($this->selectedRole);
            })
            ->orderBy('id', 'asc');

        $usersList = $usersQuery->paginate(10);

        // Pending Students Query
        $pendingStudents = User::role('student-researcher')
            ->where('status', AccountStatus::Pending)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.admin-dashboard-content', [
            'totalUsersCount' => $totalUsersCount,
            'pendingApprovalCount' => $pendingApprovalCount,
            'activeAccountsCount' => $activeAccountsCount,
            'rejectedCount' => $rejectedCount,
            'studentCount' => $studentCount,
            'adviserCount' => $adviserCount,
            'panelistCount' => $panelistCount,
            'recentActivities' => $recentActivities,
            'usersList' => $usersList,
            'pendingStudents' => $pendingStudents,
        ]);
    }
}
