{{-- Livewire UI rendered by the AdminDashboard component. --}}
<div class="min-h-screen flex font-sans bg-[#f4f7f6]" x-data="{ 
    showPassword: false,
    selectedDefense: null 
}">
    <!-- Left Sidebar: Navigation -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-[#0e5c3a] text-white flex flex-col justify-between z-20 border-r border-white/5">
        <div class="flex-shrink-0">
            <!-- Logo -->
            <div class="flex items-center gap-3 p-6 border-b border-white/10">
                <div class="p-1 bg-white/10 rounded-xl border border-white/20">
                    <img src="{{ asset('images/ndmu_logo.png') }}" alt="NDMU Logo" class="h-10 w-auto">
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-heading font-extrabold text-xl text-white tracking-tight">NDMU</span>
                    <span class="text-[9px] font-bold text-[#eebc3f] tracking-wider uppercase mt-1">Research Management</span>
                </div>
            </div>

            <!-- Profile Badge -->
            <div class="flex items-center gap-3 px-6 py-5 border-b border-white/10">
                <div class="w-10 h-10 rounded-full bg-[#eebc3f] text-[#0e5c3a] font-bold flex items-center justify-center text-lg flex-shrink-0">
                    S
                </div>
                <div class="flex flex-col leading-tight overflow-hidden">
                    <span class="font-semibold text-sm text-white truncate">System Administrator</span>
                    <span class="text-[10px] text-white/60 font-medium mt-0.5">Administrator</span>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-1 px-6 py-4 space-y-6">
            <div class="space-y-1.5">
                <span class="text-[10px] font-bold tracking-wider text-[#a5c1a0] uppercase px-3 block mb-2">Navigation</span>
                
                <!-- Dashboard Link -->
                <a href="#" 
                   wire:click.prevent="$set('activeTab', 'dashboard')"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 {{ $activeTab === 'dashboard' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold text-[13px] shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]' }}">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-squares-four text-lg"></i>
                        <span>Dashboard</span>
                    </div>
                    @if($activeTab === 'dashboard')
                        <span class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                    @endif
                </a>
                
                <!-- User Management Link -->
                <a href="#" 
                   wire:click.prevent="$set('activeTab', 'users')"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 {{ $activeTab === 'users' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold text-[13px] shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]' }}">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-users text-lg"></i>
                        <span>User Management</span>
                    </div>
                    @if($activeTab === 'users')
                        <span class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                    @endif
                </a>

                <!-- Other navigation links (mocked read-only) -->
                <a href="#" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-shield-check text-lg"></i>
                        <span>Permissions Management</span>
                    </div>
                </a>

                <a href="#" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-book-open text-lg"></i>
                        <span>Research Management</span>
                    </div>
                </a>

                <a href="#" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-calendar text-lg"></i>
                        <span>Defense Scheduling</span>
                    </div>
                </a>

                <a href="#" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-folder text-lg"></i>
                        <span>Research Repository</span>
                    </div>
                </a>

                <a href="#" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-text text-lg"></i>
                        <span>Forms Management</span>
                    </div>
                </a>

                <a href="#" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chart-line-up text-lg"></i>
                        <span>Reports & Analytics</span>
                    </div>
                </a>

                <a href="#" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-list-bullets text-lg"></i>
                        <span>Audit Logs</span>
                    </div>
                </a>

                <a href="#" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-gear text-lg"></i>
                        <span>System Settings</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="flex-shrink-0 px-6 pb-6 mt-auto">
            <div class="pt-4 border-t border-white/10 space-y-1">
                <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <i class="ph ph-bell text-lg"></i>
                    <span>Notifications</span>
                </a>
                
                <!-- Real Logout Form -->
                <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden">
                    @csrf
                </form>
                <a href="#" 
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <i class="ph ph-sign-out text-lg"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 pl-64 flex flex-col min-h-screen">
        <!-- Top Header Navbar -->
        <header class="h-20 bg-white border-b border-gray-150 px-8 flex items-center justify-between sticky top-0 z-10">
            <!-- Search bar -->
            <div class="relative w-96">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <i class="ph ph-magnifying-glass text-lg"></i>
                </span>
                <input 
                    type="text" 
                    placeholder="Search research, documents, or tasks..." 
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300"
                >
            </div>

            <!-- Profile Info and Notification Icon -->
            <div class="flex items-center gap-6">
                <!-- Notification Bell -->
                <button class="relative w-10 h-10 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-600 transition-colors">
                    <i class="ph ph-bell text-xl"></i>
                    <span class="absolute top-2.5 right-2.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                </button>

                <!-- Divider -->
                <div class="h-8 w-px bg-gray-200"></div>

                <!-- User profile badge -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-[#0e5c3a] text-white flex items-center justify-center font-bold text-lg">
                        A
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="font-bold text-sm text-gray-800">System</span>
                        <span class="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-wider">Management Portal</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dynamic Content Body -->
        <main class="flex-grow p-8 max-w-7xl w-full mx-auto">
            <!-- Alert / Success Notification Banner -->
            @if ($successMessage)
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-3 shadow-sm animate-fade-in relative" x-data="{ show: true }" x-show="show">
                    <i class="ph ph-check-circle text-2xl text-emerald-600"></i>
                    <div class="text-sm font-semibold">{{ $successMessage }}</div>
                    <button type="button" @click="show = false" class="absolute right-4 text-emerald-600 hover:text-emerald-800">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>
            @endif

            <!-- TAB 1: ADMIN DASHBOARD VIEW -->
            @if($activeTab === 'dashboard')
                <div class="space-y-8">
                    <!-- Title Section -->
                    <div>
                        <h1 class="text-3xl font-extrabold font-heading text-gray-800 tracking-tight">Admin Dashboard</h1>
                        <p class="text-sm text-gray-500 font-light mt-1">System overview and management</p>
                    </div>

                    <!-- Row of 4 statistics cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <!-- Card 1: Total Users -->
                        <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-emerald-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Users</span>
                                <span class="text-3xl font-extrabold text-gray-800 font-heading">{{ $totalUsersCount }}</span>
                            </div>
                            <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 text-2xl">
                                <i class="ph ph-users"></i>
                            </div>
                        </div>

                        <!-- Card 2: Active Research -->
                        <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-blue-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Active Research</span>
                                <span class="text-3xl font-extrabold text-gray-800 font-heading">48</span>
                            </div>
                            <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 text-2xl">
                                <i class="ph ph-file-text"></i>
                            </div>
                        </div>

                        <!-- Card 3: Completed -->
                        <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-purple-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Completed</span>
                                <span class="text-3xl font-extrabold text-gray-800 font-heading">126</span>
                            </div>
                            <div class="w-12 h-12 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-600 text-2xl">
                                <i class="ph ph-trend-up"></i>
                            </div>
                        </div>

                        <!-- Card 4: System Activity -->
                        <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-amber-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">System Activity</span>
                                <span class="text-3xl font-extrabold text-emerald-600 font-heading">High</span>
                            </div>
                            <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 text-2xl">
                                <i class="ph ph-activity"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Lower Section: Recent Activity & System Statistics -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Recent Activity Card -->
                        <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100">
                            <h3 class="text-lg font-bold text-gray-800 mb-6">Recent Activity</h3>
                            <div class="space-y-4">
                                @foreach($recentActivities as $activity)
                                    <div class="p-4 bg-gray-50/60 hover:bg-gray-50 border border-gray-100 rounded-2xl transition-all duration-200 flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-700">{{ $activity['text'] }}</span>
                                        <span class="text-xs text-gray-400">{{ $activity['time'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- System Statistics Card -->
                        <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100 flex flex-col justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 mb-6">System Statistics</h3>
                                <div class="space-y-6">
                                    <!-- Student Researchers -->
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-sm font-semibold text-gray-600">
                                            <span>Student Researchers</span>
                                            <span class="text-gray-800">{{ $studentCount }}</span>
                                        </div>
                                        <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                                            @php
                                                $studentPercent = $totalUsersCount > 0 ? ($studentCount / $totalUsersCount) * 100 : 0;
                                            @endphp
                                            <div class="h-full bg-emerald-600 rounded-full" style="width: {{ max(10, min(100, $studentPercent)) }}%"></div>
                                        </div>
                                    </div>

                                    <!-- Advisers -->
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-sm font-semibold text-gray-600">
                                            <span>Advisers</span>
                                            <span class="text-gray-800">{{ $adviserCount }}</span>
                                        </div>
                                        <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                                            @php
                                                $adviserPercent = $totalUsersCount > 0 ? ($adviserCount / $totalUsersCount) * 100 : 0;
                                            @endphp
                                            <div class="h-full bg-blue-600 rounded-full" style="width: {{ max(10, min(100, $adviserPercent)) }}%"></div>
                                        </div>
                                    </div>

                                    <!-- Panelists -->
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-sm font-semibold text-gray-600">
                                            <span>Panelists</span>
                                            <span class="text-gray-800">{{ $panelistCount }}</span>
                                        </div>
                                        <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                                            @php
                                                $panelistPercent = $totalUsersCount > 0 ? ($panelistCount / $totalUsersCount) * 100 : 0;
                                            @endphp
                                            <div class="h-full bg-purple-600 rounded-full" style="width: {{ max(10, min(100, $panelistPercent)) }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- TAB 2: USER MANAGEMENT VIEW -->
            @if($activeTab === 'users')
                <div class="space-y-8">
                    <!-- Title Section -->
                    <div>
                        <h1 class="text-3xl font-extrabold font-heading text-gray-800 tracking-tight">User Management</h1>
                        <p class="text-sm text-gray-500 font-light mt-1">Manage accounts, approve registrations, and create staff users</p>
                    </div>

                    <!-- Row of 4 statistics cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <!-- Card 1: Total Users -->
                        <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-teal-500 border border-gray-100 flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Users</span>
                                <span class="text-3xl font-extrabold text-gray-800 font-heading">{{ $totalUsersCount }}</span>
                            </div>
                            <div class="w-12 h-12 bg-teal-50 rounded-2xl flex items-center justify-center text-teal-600 text-xl">
                                <i class="ph ph-users"></i>
                            </div>
                        </div>

                        <!-- Card 2: Pending Approval -->
                        <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-amber-500 border border-gray-100 flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Pending Approval</span>
                                <span class="text-3xl font-extrabold text-gray-800 font-heading">{{ $pendingApprovalCount }}</span>
                            </div>
                            <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 text-xl">
                                <i class="ph ph-clock"></i>
                            </div>
                        </div>

                        <!-- Card 3: Active Accounts -->
                        <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-emerald-500 border border-gray-100 flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Active Accounts</span>
                                <span class="text-3xl font-extrabold text-gray-800 font-heading">{{ $activeAccountsCount }}</span>
                            </div>
                            <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                                <i class="ph ph-check-circle"></i>
                            </div>
                        </div>

                        <!-- Card 4: Rejected -->
                        <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-red-500 border border-gray-100 flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Rejected</span>
                                <span class="text-3xl font-extrabold text-gray-800 font-heading">{{ $rejectedCount }}</span>
                            </div>
                            <div class="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center text-red-600 text-xl">
                                <i class="ph ph-x-circle"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Inner Navigation Tabs -->
                    <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
                        <div class="flex border-b border-gray-100 p-4 bg-gray-50/50">
                            <!-- All Users Tab Button -->
                            <button 
                                wire:click.prevent="$set('userManagementTab', 'all-users')"
                                class="px-6 py-3 text-sm font-bold rounded-xl transition-all duration-300 flex items-center gap-2 {{ $userManagementTab === 'all-users' ? 'bg-[#0e5c3a] text-white shadow-sm' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-100' }}">
                                All Users
                                <span class="px-2 py-0.5 rounded-full text-xs font-extrabold {{ $userManagementTab === 'all-users' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600' }}">
                                    {{ $totalUsersCount }}
                                </span>
                            </button>

                            <!-- Pending Students Tab Button -->
                            <button 
                                wire:click.prevent="$set('userManagementTab', 'pending-students')"
                                class="ml-2 px-6 py-3 text-sm font-bold rounded-xl transition-all duration-300 flex items-center gap-2 {{ $userManagementTab === 'pending-students' ? 'bg-[#0e5c3a] text-white shadow-sm' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-100' }}">
                                Pending Students
                                <span class="px-2 py-0.5 rounded-full text-xs font-extrabold {{ $userManagementTab === 'pending-students' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $pendingApprovalCount }}
                                </span>
                            </button>

                            <!-- Create User Tab Button -->
                            <button 
                                wire:click.prevent="$set('userManagementTab', 'create-user')"
                                class="ml-2 px-6 py-3 text-sm font-bold rounded-xl transition-all duration-300 flex items-center gap-2 {{ $userManagementTab === 'create-user' ? 'bg-[#0e5c3a] text-white shadow-sm' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-100' }}">
                                <i class="ph ph-user-plus text-base"></i> Create User
                            </button>
                        </div>

                        <!-- SUB-TAB CONTENT PANEL -->
                        <div class="p-6">
                            <!-- All Users Panel -->
                            @if($userManagementTab === 'all-users')
                                <div class="space-y-6">
                                    <!-- Search & Filter Controls -->
                                    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                                        <!-- Search input -->
                                        <div class="relative w-full md:w-96">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                                                <i class="ph ph-magnifying-glass text-lg"></i>
                                            </span>
                                            <input 
                                                wire:model.live="searchQuery"
                                                type="text" 
                                                placeholder="Search by name or email..." 
                                                class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300"
                                            >
                                        </div>

                                        <!-- Filter and Refresh Controls -->
                                        <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                                            <!-- Role Filter -->
                                            <select 
                                                wire:model.live="selectedRole"
                                                class="px-4 py-2.5 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300 appearance-none pr-10 relative"
                                            >
                                                <option value="">All Roles</option>
                                                <option value="system-administrator">Administrator</option>
                                                <option value="college-dean">College Dean</option>
                                                <option value="research-facilitator">Research Facilitator</option>
                                                <option value="research-adviser">Research Adviser</option>
                                                <option value="panelist">Panelist</option>
                                                <option value="student-researcher">Student Researcher</option>
                                            </select>

                                            <!-- Refresh Button -->
                                            <button 
                                                wire:click="$refresh"
                                                class="px-4 py-2.5 bg-white hover:bg-gray-50 border border-gray-200 text-gray-600 hover:text-gray-800 text-sm font-semibold rounded-2xl flex items-center gap-2 shadow-sm transition-all duration-300"
                                            >
                                                <i class="ph ph-arrows-counter-clockwise"></i> Refresh
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Table Container -->
                                    <div class="overflow-x-auto rounded-2xl border border-gray-100">
                                        <table class="w-full border-collapse text-left text-sm text-gray-500">
                                            <thead class="bg-gray-50/75 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-150">
                                                <tr>
                                                    <th scope="col" class="px-6 py-4">Name</th>
                                                    <th scope="col" class="px-6 py-4">Email</th>
                                                    <th scope="col" class="px-6 py-4">Role</th>
                                                    <th scope="col" class="px-6 py-4">Status</th>
                                                    <th scope="col" class="px-6 py-4">Department</th>
                                                    <th scope="col" class="px-6 py-4">Created</th>
                                                    <th scope="col" class="px-6 py-4">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 bg-white">
                                                @forelse($usersList as $user)
                                                    <tr class="hover:bg-gray-50/50 transition-colors duration-200">
                                                        <!-- Name (avatar + name) -->
                                                        <td class="px-6 py-4 flex items-center gap-3">
                                                            <div class="w-8 h-8 rounded-full bg-[#0e5c3a]/10 text-[#0e5c3a] flex items-center justify-center font-bold text-sm">
                                                                {{ substr($user->name, 0, 1) }}
                                                            </div>
                                                            <div class="font-bold text-gray-800">{{ $user->name }}</div>
                                                        </td>

                                                        <!-- Email -->
                                                        <td class="px-6 py-4">{{ $user->email }}</td>

                                                        <!-- Role Badge -->
                                                        <td class="px-6 py-4">
                                                            @php
                                                                $roleName = $user->roles->first()?->name ?? 'None';
                                                                $roleBadgeClass = match($roleName) {
                                                                    'system-administrator' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                                                    'college-dean' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                                                    'research-facilitator' => 'bg-sky-50 text-sky-700 border-sky-100',
                                                                    'research-adviser' => 'bg-teal-50 text-teal-700 border-teal-100',
                                                                    'panelist' => 'bg-purple-50 text-purple-700 border-purple-100',
                                                                    default => 'bg-gray-50 text-gray-700 border-gray-100',
                                                                };
                                                                $roleLabel = str($roleName === 'system-administrator' ? 'Administrator' : $roleName)
                                                                    ->replace('-', ' ')
                                                                    ->title();
                                                            @endphp
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $roleBadgeClass }}">
                                                                {{ $roleLabel }}
                                                            </span>
                                                        </td>

                                                        <!-- Status Badge -->
                                                        <td class="px-6 py-4">
                                                            @php
                                                                $status = $user->status instanceof \App\Enums\AccountStatus ? $user->status->value : $user->status;
                                                                $statusBadgeClass = match($status) {
                                                                    'active' => 'bg-emerald-100 text-emerald-800',
                                                                    'pending' => 'bg-amber-100 text-amber-800',
                                                                    'rejected' => 'bg-red-100 text-red-800',
                                                                    default => 'bg-gray-100 text-gray-800',
                                                                };
                                                            @endphp
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $statusBadgeClass }}">
                                                                {{ ucfirst($status) }}
                                                            </span>
                                                        </td>

                                                        <!-- Department -->
                                                        <td class="px-6 py-4 text-xs font-medium text-gray-500 max-w-[150px] truncate">
                                                            {{ $user->department ?? ($user->program ?? 'N/A') }}
                                                        </td>

                                                        <!-- Created Date -->
                                                        <td class="px-6 py-4">{{ $user->created_at?->format('Y-m-d') }}</td>

                                                        <!-- Actions -->
                                                        <td class="px-6 py-4 text-xs font-bold text-gray-400">System</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="7" class="px-6 py-12 text-center text-gray-400 font-light">
                                                            No users found matching your criteria.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination Links -->
                                    <div class="mt-4">
                                        {{ $usersList->links() }}
                                    </div>
                                </div>
                            @endif

                            <!-- Pending Students Panel -->
                            @if($userManagementTab === 'pending-students')
                                <div class="space-y-6">
                                    @forelse($pendingStudents as $student)
                                        <!-- Pending Student Card -->
                                        <div class="p-6 bg-amber-50/30 border border-amber-200/60 rounded-[2rem] flex flex-col md:flex-row md:items-center justify-between gap-6 hover:shadow-sm transition-all duration-300">
                                            <!-- Student Info -->
                                            <div class="flex items-start gap-4">
                                                <div class="w-12 h-12 rounded-full bg-[#0e5c3a] text-white flex items-center justify-center font-bold text-lg flex-shrink-0">
                                                    {{ substr($student->name, 0, 1) }}
                                                </div>
                                                <div class="space-y-1">
                                                    <div class="flex items-center gap-2">
                                                        <h4 class="font-extrabold text-gray-800 text-base leading-snug">{{ $student->name }}</h4>
                                                        <span class="text-xs font-bold text-gray-400">Student Researcher</span>
                                                    </div>
                                                    
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1.5 pt-2 text-xs font-medium text-gray-500">
                                                        <div class="flex items-center gap-2">
                                                            <i class="ph ph-identification-card text-base text-gray-400"></i>
                                                            <span>{{ $student->student_id ?? 'STU-2026-' . str_pad($student->id, 4, '0', STR_PAD_LEFT) }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <i class="ph ph-envelope-simple text-base text-gray-400"></i>
                                                            <span>{{ $student->email }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-2 md:col-span-2">
                                                            <i class="ph ph-graduation-cap text-base text-gray-400"></i>
                                                            <span>{{ $student->program ?? 'Bachelor of Science in Computer Science' }} - {{ $student->year_level ?? '3rd' }} Year</span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <i class="ph ph-calendar text-base text-gray-400"></i>
                                                            <span>Registered: {{ $student->created_at?->format('Y-m-d') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Approval Buttons -->
                                            <div class="flex items-center gap-3 flex-shrink-0 self-end md:self-center">
                                                <button 
                                                    wire:click="approveStudent({{ $student->id }})"
                                                    class="px-5 py-3 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-2xl flex items-center gap-2 shadow-md shadow-emerald-700/10 hover:shadow-lg transition-all duration-300"
                                                >
                                                    <i class="ph ph-check-circle text-base"></i> Approve
                                                </button>
                                                <button 
                                                    wire:click="rejectStudent({{ $student->id }})"
                                                    class="px-5 py-3 border border-red-200 hover:border-red-300 text-red-600 bg-white hover:bg-red-50 text-xs font-bold rounded-2xl flex items-center gap-2 shadow-sm transition-all duration-300"
                                                >
                                                    <i class="ph ph-x-circle text-base"></i> Reject
                                                </button>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="py-16 text-center text-gray-400 font-light">
                                            <i class="ph ph-users-three text-5xl mb-3 text-gray-300 block"></i>
                                            No pending student registrations at the moment.
                                        </div>
                                    @endforelse
                                </div>
                            @endif

                            <!-- Create User Form Panel -->
                            @if($userManagementTab === 'create-user')
                                <div class="max-w-xl mx-auto py-4">
                                    <!-- Alert badge -->
                                    <div class="mb-6 p-4 bg-sky-50 border border-sky-100 text-sky-800 rounded-2xl flex gap-3 text-xs leading-relaxed">
                                        <i class="ph ph-info text-lg text-sky-600 flex-shrink-0"></i>
                                        <div>
                                            Creates a <strong>staff account</strong> (Adviser, Panelist, Research Facilitator, or College Dean). The user will be required to change their temporary password on first login.
                                        </div>
                                    </div>

                                    <!-- Form -->
                                    <form wire:submit.prevent="createStaffAccount" class="space-y-5">
                                        <!-- Full Name -->
                                        <div class="space-y-1.5">
                                            <label for="new_name" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Full Name</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                                                    <i class="ph ph-user text-lg"></i>
                                                </span>
                                                <input 
                                                    id="new_name"
                                                    type="text" 
                                                    wire:model="name"
                                                    placeholder="Dr. Juan Dela Cruz" 
                                                    class="w-full pl-11 pr-3 py-3.5 bg-white border @error('name') border-red-300 focus:border-red-500 focus:ring-red-500/5 @else border-gray-200 focus:border-[#0e5c3a] focus:ring-[#0e5c3a]/5 @enderror rounded-2xl text-sm placeholder-gray-400 focus:outline-none focus:ring-4 transition-all duration-300"
                                                >
                                            </div>
                                            @error('name') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                                        </div>

                                        <!-- Email Address -->
                                        <div class="space-y-1.5">
                                            <label for="new_email" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Email Address</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                                                    <i class="ph ph-envelope-simple text-lg"></i>
                                                </span>
                                                <input 
                                                    id="new_email"
                                                    type="email" 
                                                    wire:model="email"
                                                    placeholder="juan.delacruz@ndmu.edu.ph" 
                                                    class="w-full pl-11 pr-3 py-3.5 bg-white border @error('email') border-red-300 focus:border-red-500 focus:ring-red-500/5 @else border-gray-200 focus:border-[#0e5c3a] focus:ring-[#0e5c3a]/5 @enderror rounded-2xl text-sm placeholder-gray-400 focus:outline-none focus:ring-4 transition-all duration-300"
                                                >
                                            </div>
                                            @error('email') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                                        </div>

                                        <!-- Role dropdown -->
                                        <div class="space-y-1.5">
                                            <label for="new_role" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Role</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                                                    <i class="ph ph-shield text-lg"></i>
                                                </span>
                                                <select 
                                                    id="new_role"
                                                    wire:model="role"
                                                    class="w-full pl-11 pr-10 py-3.5 bg-white border @error('role') border-red-300 focus:border-red-500 focus:ring-red-500/5 @else border-gray-200 focus:border-[#0e5c3a] focus:ring-[#0e5c3a]/5 @enderror rounded-2xl text-sm focus:outline-none focus:ring-4 transition-all duration-300 appearance-none"
                                                >
                                                    <option value="">Select role</option>
                                                    <option value="research-adviser">Research Adviser</option>
                                                    <option value="panelist">Panelist</option>
                                                    <option value="research-facilitator">Research Facilitator</option>
                                                    <option value="college-dean">College Dean</option>
                                                </select>
                                                <span class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 pointer-events-none">
                                                    <i class="ph ph-caret-down text-base"></i>
                                                </span>
                                            </div>
                                            @error('role') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                                        </div>

                                        <!-- Department / College dropdown -->
                                        <div class="space-y-1.5">
                                            <label for="new_department" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Department / College</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                                                    <i class="ph ph-briefcase text-lg"></i>
                                                </span>
                                                <select 
                                                    id="new_department"
                                                    wire:model="department"
                                                    class="w-full pl-11 pr-10 py-3.5 bg-white border @error('department') border-red-300 focus:border-red-500 focus:ring-red-500/5 @else border-gray-200 focus:border-[#0e5c3a] focus:ring-[#0e5c3a]/5 @enderror rounded-2xl text-sm focus:outline-none focus:ring-4 transition-all duration-300 appearance-none"
                                                >
                                                    <option value="">Select department</option>
                                                    <option value="College of Information Technology">College of Information Technology</option>
                                                    <option value="College of Engineering">College of Engineering</option>
                                                    <option value="College of Business Administration">College of Business Administration</option>
                                                    <option value="Office of the College Dean">Office of the College Dean</option>
                                                    <option value="Research & Development Center">Research & Development Center</option>
                                                </select>
                                                <span class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 pointer-events-none">
                                                    <i class="ph ph-caret-down text-base"></i>
                                                </span>
                                            </div>
                                            @error('department') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                                        </div>

                                        <!-- Temporary Password -->
                                        <div class="space-y-1.5">
                                            <label for="new_password" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Temporary Password</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                                                    <i class="ph ph-lock text-lg"></i>
                                                </span>
                                                <input 
                                                    id="new_password"
                                                    :type="showPassword ? 'text' : 'password'" 
                                                    wire:model="password"
                                                    placeholder="Temporary password for first login" 
                                                    class="w-full pl-11 pr-11 py-3.5 bg-white border @error('password') border-red-300 focus:border-red-500 focus:ring-red-500/5 @else border-gray-200 focus:border-[#0e5c3a] focus:ring-[#0e5c3a]/5 @enderror rounded-2xl text-sm placeholder-gray-400 focus:outline-none focus:ring-4 transition-all duration-300"
                                                >
                                                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                                                    <i :class="showPassword ? 'ph ph-eye-slash' : 'ph ph-eye'" class="text-lg"></i>
                                                </button>
                                            </div>
                                            <p class="text-[10px] text-gray-400 font-light mt-1">Share this password securely with the user. They will be prompted to change it on first login.</p>
                                            @error('password') <span class="text-xs font-bold text-red-500 block mt-1">{{ $message }}</span> @enderror
                                        </div>

                                        <!-- Submit button -->
                                        <button 
                                            type="submit" 
                                            class="w-full py-4 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-sm font-bold rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-[#0e5c3a]/10 hover:shadow-xl transition-all duration-300 mt-2"
                                        >
                                            <i class="ph ph-plus text-base"></i> Create Account
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </main>
    </div>
</div>
