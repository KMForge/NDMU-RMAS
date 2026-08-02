{{-- Livewire UI rendered by the AdminDashboard component. --}}
<style>[x-cloak] { display: none !important; }</style>
<div
    class="min-h-screen flex font-sans bg-[#f4f7f6]"
    x-data="{
    activeTab: 'dashboard',
    userManagementTab: 'all-users',
    showPassword: false,
    selectedDefense: null,
    permissionsSearch: '',
    permissionsRole: '',
    showPermissionsModal: false,
    selectedUser: null,
    modalPermissions: {},
    showScheduleModal: false,
    showViewModal: false,
    showEditModal: false,
    panelistInput: '',
    defenseFilterType: 'All Defense Types',
    defenseFilterStatus: 'All Status',
    formDefense: {
        id: null,
        type: 'Proposal Defense',
        title: '',
        student: '',
        date: '',
        time: '',
        duration: '2 hours',
        venue: '',
        adviser: '',
        panelists: [],
        status: 'Pending',
        notes: '',
        generateNotice: true
    },
    defensesList: @js($defensesList),
    repositorySearch: '',
    repositoryFilter: 'All Status',
    repositoryList: @js($repositoryList),
    proposalsList: @js($proposalsList),
    staffList: @js($staffList),
    adviserOptions: @js($adviserOptions),
    panelistOptions: @js($panelistOptions)
}"
    @staff-account-created.window="activeTab = 'users'; userManagementTab = 'all-users'"
>
    <!-- Left Sidebar: Navigation -->
    <aside class="fixed inset-y-0 left-0 w-72 bg-[#0e5c3a] text-white flex flex-col justify-between z-20 border-r border-white/5">
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
                    {{ mb_strtoupper(mb_substr($administrator?->name ?? 'A', 0, 1)) }}
                </div>
                <div class="flex flex-col leading-tight overflow-hidden">
                    <span class="font-semibold text-sm text-white truncate">{{ $administrator?->name ?? 'Administrator' }}</span>
                    <span class="text-[10px] text-white/60 font-medium mt-0.5">Administrator</span>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-1 px-6 py-4 space-y-6">
            <div class="space-y-1.5">
                <span class="text-[10px] font-bold tracking-wider text-[#a5c1a0] uppercase px-3 block mb-2">Navigation</span>
                
                <!-- Dashboard Link -->
                <button
                   type="button"
                   @click="activeTab = 'dashboard'"
                   :class="activeTab === 'dashboard' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px]">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-squares-four text-lg"></i>
                        <span>Dashboard</span>
                    </div>
                    <span x-show="activeTab === 'dashboard'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>
                
                <!-- User Management Link -->
                <button
                   type="button"
                   @click="activeTab = 'users'"
                   :class="activeTab === 'users' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px]">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-users text-lg"></i>
                        <span>User Management</span>
                    </div>
                    <span x-show="activeTab === 'users'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <button
                   type="button"
                   @click="activeTab = 'permissions'"
                   :class="activeTab === 'permissions' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px]">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-shield-check text-lg"></i>
                        <span>Permissions Management</span>
                    </div>
                    <span x-show="activeTab === 'permissions'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <button
                   type="button"
                   @click="activeTab = 'research'"
                   :class="activeTab === 'research' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px]">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-book-open text-lg"></i>
                        <span>Research Management</span>
                    </div>
                    <span x-show="activeTab === 'research'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <button
                   type="button"
                   @click="activeTab = 'defenses'"
                   :class="activeTab === 'defenses' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px]">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-calendar text-lg"></i>
                        <span>Defense Scheduling</span>
                    </div>
                    <span x-show="activeTab === 'defenses'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <button 
                    type="button"
                    @click="activeTab = 'repository'"
                    :class="activeTab === 'repository' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px]"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-folder text-lg"></i>
                        <span>Research Repository</span>
                    </div>
                    <span x-show="activeTab === 'repository'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <button 
                    type="button"
                    @click="activeTab = 'forms'"
                    :class="activeTab === 'forms' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px]"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-text text-lg"></i>
                        <span>Forms Management</span>
                    </div>
                    <span x-show="activeTab === 'forms'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <button 
                    type="button"
                    @click="activeTab = 'reports'"
                    :class="activeTab === 'reports' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px]"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chart-line-up text-lg"></i>
                        <span>Reports & Analytics</span>
                    </div>
                    <span x-show="activeTab === 'reports'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <button 
                    type="button"
                    @click="activeTab = 'audit'"
                    :class="activeTab === 'audit' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px]"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-list-bullets text-lg"></i>
                        <span>Audit Logs</span>
                    </div>
                    <span x-show="activeTab === 'audit'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <button 
                    type="button"
                    @click="alert('System settings are configured automatically.')"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-gear text-lg"></i>
                        <span>System Settings</span>
                    </div>
                </button>
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
    <div class="flex-1 pl-72 flex flex-col min-h-screen">
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
        <main class="flex-grow px-10 py-8 w-full">
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
            @error('account')
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">
                    {{ $message }}
                </div>
            @enderror

            <!-- TAB 1: ADMIN DASHBOARD VIEW -->
            <div x-show="activeTab === 'dashboard'" x-cloak class="space-y-8">
                    <!-- Hero Header Card Section -->
                    <div class="relative overflow-hidden bg-white rounded-2xl p-8 border border-slate-200/60 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                        <!-- Subtle abstract NDMU logo watermark -->
                        <div class="absolute -right-6 -bottom-6 opacity-[0.04] pointer-events-none">
                            <img src="{{ asset('images/ndmu_logo.png') }}" alt="" class="w-64 h-auto">
                        </div>

                        <div class="relative z-10 space-y-1">
                            <div class="flex items-center gap-2 text-xs font-semibold text-slate-400">
                                <span>{{ now()->timezone(config('ndmu-rmas.timezone'))->format('l, F j, Y') }}</span>
                                <span>•</span>
                                <span class="text-[#0e5c3a] font-bold">System Administration Portal</span>
                            </div>
                            <h1 class="text-2xl md:text-3xl font-extrabold font-heading text-slate-900 tracking-tight">Welcome back, Administrator</h1>
                            <p class="text-xs text-slate-500 max-w-xl">System overview, account management, permissions, and audit monitoring</p>
                        </div>

                        <div class="relative z-10 flex items-center gap-3">
                            <button @click="activeTab = 'users'" class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer">
                                <i class="ph ph-users text-base"></i>
                                <span>Manage Users</span>
                            </button>
                        </div>
                    </div>

                    <!-- Row of 4 statistics cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <!-- Card 1: Total Users -->
                        <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total Users</span>
                                <span class="text-3xl font-extrabold text-slate-900 tracking-tight block">{{ $totalUsersCount }}</span>
                            </div>
                            <div class="w-11 h-11 bg-emerald-50/80 text-[#0e5c3a] border border-emerald-100/60 rounded-xl flex items-center justify-center text-xl shadow-2xs">
                                <i class="ph ph-users"></i>
                            </div>
                        </div>

                        <!-- Card 2: Active Research -->
                        <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Active Research</span>
                                <span class="text-3xl font-extrabold text-slate-900 tracking-tight block">{{ $activeResearchCount }}</span>
                            </div>
                            <div class="w-11 h-11 bg-blue-50/80 text-blue-700 border border-blue-100/60 rounded-xl flex items-center justify-center text-xl shadow-2xs">
                                <i class="ph ph-file-text"></i>
                            </div>
                        </div>

                        <!-- Card 3: Completed -->
                        <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Completed</span>
                                <span class="text-3xl font-extrabold text-slate-900 tracking-tight block">{{ $completedResearchCount }}</span>
                            </div>
                            <div class="w-11 h-11 bg-purple-50/80 text-purple-700 border border-purple-100/60 rounded-xl flex items-center justify-center text-xl shadow-2xs">
                                <i class="ph ph-trend-up"></i>
                            </div>
                        </div>

                        <!-- Card 4: System Activity -->
                        <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Recent Activity</span>
                                <span class="text-3xl font-extrabold text-slate-900 tracking-tight block">{{ count($recentActivities) }}</span>
                            </div>
                            <div class="w-11 h-11 bg-amber-50/80 text-amber-700 border border-amber-100/60 rounded-xl flex items-center justify-center text-xl shadow-2xs">
                                <i class="ph ph-activity"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Lower Section: Recent Activity & System Statistics -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Recent Activity Card -->
                        <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md transition-all duration-200">
                            <h3 class="text-lg font-bold text-gray-800 mb-6">Recent Activity</h3>
                            <div class="space-y-4">
                                @forelse($recentActivities as $activity)
                                    <div class="p-4 bg-gray-50/60 hover:bg-gray-50 border border-gray-100 rounded-2xl transition-all duration-200 flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-700">{{ $activity['text'] }}</span>
                                        <span class="text-xs text-gray-400">{{ $activity['time'] }}</span>
                                    </div>
                                @empty
                                    <div class="p-6 bg-gray-50/60 border border-gray-100 rounded-2xl text-center text-sm text-gray-500">
                                        No recent activity found.
                                    </div>
                                @endforelse
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
                                            <div class="h-full bg-emerald-600 rounded-full" x-init="$el.style.width = @js(max(0, min(100, $studentPercent))) + '%'"></div>
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
                                            <div class="h-full bg-blue-600 rounded-full" x-init="$el.style.width = @js(max(0, min(100, $adviserPercent))) + '%'"></div>
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
                                            <div class="h-full bg-purple-600 rounded-full" x-init="$el.style.width = @js(max(0, min(100, $panelistPercent))) + '%'"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Actions -->
                    <section class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between gap-4 mb-6">
                            <div>
                                <h2 class="text-lg font-bold text-gray-800">Pending Actions</h2>
                                <p class="text-xs text-gray-500 mt-1">Items that still require review or attention</p>
                            </div>
                            <span class="px-3 py-1.5 rounded-full bg-amber-50 text-amber-700 text-xs font-bold">
                                {{ collect($pendingActions)->sum('count') }} total
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            @foreach ($pendingActions as $action)
                                @php
                                    $actionTone = match ($action['tone']) {
                                        'purple' => 'bg-purple-50 text-purple-700 border-purple-100',
                                        'blue' => 'bg-blue-50 text-blue-700 border-blue-100',
                                        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                        'red' => 'bg-red-50 text-red-700 border-red-100',
                                        'orange' => 'bg-orange-50 text-orange-700 border-orange-100',
                                        default => 'bg-amber-50 text-amber-700 border-amber-100',
                                    };
                                @endphp
                                <button
                                    type="button"
                                    data-tab="{{ $action['tab'] }}"
                                    data-subtab="{{ $action['subtab'] }}"
                                    @click="if ($el.dataset.tab) { activeTab = $el.dataset.tab; if ($el.dataset.subtab) userManagementTab = $el.dataset.subtab; }"
                                    @disabled($action['tab'] === null)
                                    class="p-5 rounded-2xl border text-left flex items-center justify-between gap-4 transition-all hover:shadow-sm disabled:cursor-default {{ $actionTone }}"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-10 h-10 rounded-xl bg-white/80 flex items-center justify-center flex-shrink-0">
                                            <i class="ph {{ $action['icon'] }} text-xl"></i>
                                        </span>
                                        <span class="text-xs font-bold truncate">{{ $action['label'] }}</span>
                                    </div>
                                    <span class="text-2xl font-extrabold font-heading">{{ $action['count'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </section>

                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                        <!-- Security Overview -->
                        <section class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                                    <i class="ph ph-shield-check text-xl"></i>
                                </span>
                                <div>
                                    <h2 class="text-lg font-bold text-gray-800">Security Overview</h2>
                                    <p class="text-xs text-gray-500 mt-1">Account and upload security indicators</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach ($securityOverview as $item)
                                    <div @class([
                                        'p-5 rounded-2xl border flex items-start justify-between gap-4',
                                        'bg-red-50/60 border-red-100' => $item['attention'],
                                        'bg-emerald-50/50 border-emerald-100' => ! $item['attention'],
                                    ])>
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-gray-700">{{ $item['label'] }}</p>
                                            <p class="text-[10px] text-gray-500 mt-1">{{ $item['detail'] }}</p>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <i @class(['ph', $item['icon'], 'text-lg', 'text-red-600' => $item['attention'], 'text-emerald-600' => ! $item['attention']])></i>
                                            <span class="block text-xl font-extrabold text-gray-800 mt-1">{{ $item['count'] }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <!-- System Health -->
                        <section class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center">
                                    <i class="ph ph-heartbeat text-xl"></i>
                                </span>
                                <div>
                                    <h2 class="text-lg font-bold text-gray-800">System Health</h2>
                                    <p class="text-xs text-gray-500 mt-1">Safe operational checks without exposing server details</p>
                                </div>
                            </div>

                            <div class="space-y-3">
                                @foreach ($systemHealth as $health)
                                    <div class="p-4 rounded-2xl border border-gray-100 bg-gray-50/60 flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span @class([
                                                'w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0',
                                                'bg-emerald-100 text-emerald-700' => $health['healthy'],
                                                'bg-red-100 text-red-700' => ! $health['healthy'],
                                            ])>
                                                <i class="ph {{ $health['icon'] }} text-lg"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-gray-700">{{ $health['label'] }}</p>
                                                <p class="text-[10px] text-gray-500 mt-0.5 truncate">{{ $health['detail'] }}</p>
                                            </div>
                                        </div>
                                        <span @class([
                                            'px-3 py-1 rounded-full text-[10px] font-bold flex-shrink-0',
                                            'bg-emerald-100 text-emerald-700' => $health['healthy'],
                                            'bg-red-100 text-red-700' => ! $health['healthy'],
                                        ])>{{ $health['status'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    </div>
            </div>

            <!-- TAB 2: USER MANAGEMENT VIEW -->
            <div x-show="activeTab === 'users'" x-cloak class="space-y-8">
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
                            <div class="flex border-b border-gray-200 px-8 pt-6 bg-white gap-6">
                                <!-- All Users Tab Button -->
                                <button 
                                    type="button"
                                    @click="userManagementTab = 'all-users'"
                                    :class="userManagementTab === 'all-users' ? 'border-[#0e5c3a] text-[#0e5c3a]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                                    class="pb-4 border-b-2 text-sm font-extrabold flex items-center gap-2 transition-all duration-200 focus:outline-none -mb-px">
                                    All Users
                                    <span
                                        :class="userManagementTab === 'all-users' ? 'bg-[#0e5c3a] text-white' : 'bg-gray-100 text-gray-600'"
                                        class="px-2.5 py-0.5 rounded-full text-xs font-extrabold transition-all duration-200"
                                    >
                                        {{ $totalUsersCount }}
                                    </span>
                                </button>
    
                                <!-- Pending Students Tab Button -->
                                <button 
                                    type="button"
                                    @click="userManagementTab = 'pending-students'"
                                    :class="userManagementTab === 'pending-students' ? 'border-[#0e5c3a] text-[#0e5c3a]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                                    class="pb-4 border-b-2 text-sm font-extrabold flex items-center gap-2 transition-all duration-200 focus:outline-none -mb-px">
                                    Pending Students
                                    <span
                                        :class="userManagementTab === 'pending-students' ? 'bg-[#0e5c3a] text-white' : 'bg-gray-100 text-gray-600'"
                                        class="px-2.5 py-0.5 rounded-full text-xs font-extrabold transition-all duration-200"
                                    >
                                        {{ $pendingApprovalCount }}
                                    </span>
                                </button>
    
                                <!-- Create User Tab Button -->
                                <button 
                                    type="button"
                                    @click="userManagementTab = 'create-user'"
                                    :class="userManagementTab === 'create-user' ? 'border-[#0e5c3a] text-[#0e5c3a]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                                    class="pb-4 border-b-2 text-sm font-extrabold flex items-center gap-2 transition-all duration-200 focus:outline-none -mb-px">
                                    <i class="ph ph-user-plus text-base"></i> Create User
                                </button>
                            </div>

                        <!-- SUB-TAB CONTENT PANEL -->
                        <div class="p-6">
                            <!-- All Users Panel -->
                            <div x-show="userManagementTab === 'all-users'" x-cloak class="space-y-6">
                                    <!-- Search & Filter Controls -->
                                    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                                        <!-- Search input -->
                                        <div class="relative w-full md:w-96">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                                                <i class="ph ph-magnifying-glass text-lg"></i>
                                            </span>
                                            <input 
                                                wire:model.live.debounce.400ms="searchQuery"
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
                                                type="button"
                                                wire:click="refreshUserManagement"
                                                wire:loading.attr="disabled"
                                                wire:target="refreshUserManagement"
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

                                                        <!-- Role Badges -->
                                                        <td class="px-6 py-4">
                                                            <div class="flex flex-wrap gap-1">
                                                                @forelse ($user->roles as $assignedRole)
                                                                    @php
                                                                        $roleBadgeClass = match($assignedRole->name) {
                                                                            'system-administrator' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                                                            'college-dean' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                                                            'research-facilitator' => 'bg-sky-50 text-sky-700 border-sky-100',
                                                                            'research-adviser' => 'bg-teal-50 text-teal-700 border-teal-100',
                                                                            'panelist' => 'bg-purple-50 text-purple-700 border-purple-100',
                                                                            default => 'bg-gray-50 text-gray-700 border-gray-100',
                                                                        };
                                                                        $roleLabel = str($assignedRole->name === 'system-administrator' ? 'Administrator' : $assignedRole->name)
                                                                            ->replace('-', ' ')
                                                                            ->title();
                                                                    @endphp
                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $roleBadgeClass }}">
                                                                        {{ $roleLabel }}
                                                                    </span>
                                                                @empty
                                                                    <span class="text-xs text-gray-400">No role</span>
                                                                @endforelse
                                                            </div>
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
                                                        <td class="px-6 py-4">
                                                            @if (auth()->id() === $user->id)
                                                                <span class="text-xs font-bold text-gray-400">Current account</span>
                                                            @elseif ($status === 'active')
                                                                <button
                                                                    type="button"
                                                                    wire:click="suspendUser({{ $user->id }})"
                                                                    wire:confirm="Suspend this account? The user will no longer be able to sign in."
                                                                    wire:loading.attr="disabled"
                                                                    wire:target="suspendUser({{ $user->id }})"
                                                                    class="px-3 py-2 rounded-xl border border-amber-200 bg-amber-50 text-xs font-bold text-amber-700 hover:bg-amber-100 disabled:opacity-50"
                                                                >
                                                                    Suspend
                                                                </button>
                                                            @else
                                                                <button
                                                                    type="button"
                                                                    wire:click="activateUser({{ $user->id }})"
                                                                    wire:confirm="Activate this account?"
                                                                    wire:loading.attr="disabled"
                                                                    wire:target="activateUser({{ $user->id }})"
                                                                    class="px-3 py-2 rounded-xl bg-[#0e5c3a] text-xs font-bold text-white hover:bg-[#0a4a2e] disabled:opacity-50"
                                                                >
                                                                    Activate
                                                                </button>
                                                            @endif
                                                        </td>
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

                            <!-- Pending Students Panel -->
                            <div x-show="userManagementTab === 'pending-students'" x-cloak class="space-y-6">
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
                                                            <span>{{ $student->student_id ?? 'Student ID not provided' }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <i class="ph ph-envelope-simple text-base text-gray-400"></i>
                                                            <span>{{ $student->email }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-2 md:col-span-2">
                                                            <i class="ph ph-graduation-cap text-base text-gray-400"></i>
                                                            <span>{{ $student->program ?? 'Program not specified' }} - {{ $student->year_level ? $student->year_level.' Year' : 'Year level not specified' }}</span>
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
                                                    wire:loading.attr="disabled"
                                                    wire:target="approveStudent({{ $student->id }})"
                                                    class="px-5 py-3 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-2xl flex items-center gap-2 shadow-md shadow-emerald-700/10 hover:shadow-lg transition-all duration-300"
                                                >
                                                    <i class="ph ph-check-circle text-base"></i> Approve
                                                </button>
                                                <button 
                                                    wire:click="rejectStudent({{ $student->id }})"
                                                    wire:confirm="Reject this student registration?"
                                                    wire:loading.attr="disabled"
                                                    wire:target="rejectStudent({{ $student->id }})"
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

                            <!-- Create User Form Panel -->
                            <div x-show="userManagementTab === 'create-user'" x-cloak class="max-w-xl mx-auto py-4">
                                    <!-- Alert badge -->
                                    <div class="mb-6 p-4 bg-sky-50 border border-sky-100 text-sky-800 rounded-2xl flex gap-3 text-xs leading-relaxed">
                                        <i class="ph ph-info text-lg text-sky-600 flex-shrink-0"></i>
                                        <div>
                                            Creates a <strong>staff account</strong> (Adviser, Panelist, Research Facilitator, or College Dean). Share the temporary password securely and ask the user to change it through the password reset flow.
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

                                        <!-- College (NDMU-RMAS is scoped to CEAC) -->
                                        <div class="space-y-1.5">
                                            <label for="new_department" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">College</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                                                    <i class="ph ph-briefcase text-lg"></i>
                                                </span>
                                                <input
                                                    type="text"
                                                    id="new_department"
                                                    value="{{ config('academic.college.name') }}"
                                                    readonly
                                                    aria-readonly="true"
                                                    class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm text-gray-600 focus:outline-none"
                                                >
                                            </div>
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
                                            wire:loading.attr="disabled"
                                            wire:target="createStaffAccount"
                                            class="w-full py-4 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-sm font-bold rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-[#0e5c3a]/10 hover:shadow-xl transition-all duration-300 mt-2"
                                        >
                                            <i class="ph ph-plus text-base"></i>
                                            <span wire:loading.remove wire:target="createStaffAccount">Create Account</span>
                                            <span wire:loading wire:target="createStaffAccount">Creating Account...</span>
                                        </button>
                                    </form>
                            </div>
                        </div>
                    </div>
            </div>

            <!-- TAB 3: PERMISSIONS MANAGEMENT VIEW -->
            <div x-show="activeTab === 'permissions'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title Section -->
                <div>
                    <h1 class="text-3xl font-extrabold font-heading text-gray-800 tracking-tight">Permissions Management</h1>
                    <p class="text-sm text-gray-500 font-light mt-1">Manage extra role features and permissions for staff accounts</p>
                </div>

                <!-- Row of 4 statistics cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Card 1: Eligible Users -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-emerald-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Eligible Users</span>
                            <span class="text-3xl font-extrabold text-gray-800 font-heading" x-text="staffList.length">0</span>
                        </div>
                        <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 text-2xl">
                            <i class="ph ph-user-check"></i>
                        </div>
                    </div>

                    <!-- Card 2: With Permissions -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-amber-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">With Permissions</span>
                            <span class="text-3xl font-extrabold text-gray-800 font-heading" x-text="staffList.filter(s => s.activeCount > 0).length">0</span>
                        </div>
                        <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 text-2xl">
                            <i class="ph ph-shield"></i>
                        </div>
                    </div>

                    <!-- Card 3: Advisers -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-blue-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Advisers</span>
                            <span class="text-3xl font-extrabold text-gray-800 font-heading" x-text="staffList.filter(s => s.role === 'Research Adviser').length">0</span>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 text-2xl">
                            <i class="ph ph-graduation-cap"></i>
                        </div>
                    </div>

                    <!-- Card 4: Facilitators -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-pink-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Facilitators</span>
                            <span class="text-3xl font-extrabold text-gray-800 font-heading" x-text="staffList.filter(s => s.role === 'Research Facilitator').length">0</span>
                        </div>
                        <div class="w-12 h-12 bg-pink-50 rounded-2xl flex items-center justify-center text-pink-600 text-2xl">
                            <i class="ph ph-buildings"></i>
                        </div>
                    </div>
                </div>

                <!-- Main Container -->
                <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-6 space-y-6">
                    <!-- Search & Filter Controls -->
                    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                        <!-- Search input -->
                        <div class="relative w-full md:w-96">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                                <i class="ph ph-magnifying-glass text-lg"></i>
                            </span>
                            <input 
                                x-model="permissionsSearch"
                                type="text" 
                                placeholder="Search by name or email..." 
                                class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300"
                            >
                        </div>

                        <!-- Filter and Refresh Controls -->
                        <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                            <!-- Role Filter -->
                            <select 
                                x-model="permissionsRole"
                                class="px-4 py-2.5 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300 appearance-none pr-10 relative"
                            >
                                <option value="">All Roles</option>
                                <option value="Research Facilitator">Research Facilitator</option>
                                <option value="Research Adviser">Research Adviser</option>
                            </select>

                            <!-- Refresh Button -->
                            <button 
                                type="button"
                                @click="permissionsSearch = ''; permissionsRole = '';"
                                class="px-4 py-2.5 bg-white hover:bg-gray-50 border border-gray-200 text-gray-600 hover:text-gray-800 text-sm font-semibold rounded-2xl flex items-center gap-2 shadow-sm transition-all duration-300"
                            >
                                <i class="ph ph-arrows-counter-clockwise"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- Alert Banner -->
                    <div class="p-4 bg-blue-50 border border-blue-100 text-blue-800 rounded-2xl flex items-center gap-3 shadow-sm">
                        <i class="ph ph-shield-check text-2xl text-blue-600"></i>
                        <div class="text-xs font-semibold">
                            Grant additional role features to staff accounts. <span class="text-blue-900 font-extrabold">Permissions are applied instantly</span> and appear as extra menu items in the user's sidebar.
                        </div>
                    </div>

                    <!-- Table of Users -->
                    <div class="overflow-x-auto rounded-2xl border border-gray-100">
                        <table class="w-full border-collapse text-left text-sm text-gray-500">
                            <thead class="bg-gray-50/75 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-150">
                                <tr>
                                    <th scope="col" class="px-6 py-4">User</th>
                                    <th scope="col" class="px-6 py-4">Email</th>
                                    <th scope="col" class="px-6 py-4">Role</th>
                                    <th scope="col" class="px-6 py-4">Department</th>
                                    <th scope="col" class="px-6 py-4 text-center">Active Permissions</th>
                                    <th scope="col" class="px-6 py-4 text-center">Manage</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <template x-for="user in staffList.filter(u => (permissionsSearch === '' || u.name.toLowerCase().includes(permissionsSearch.toLowerCase()) || u.email.toLowerCase().includes(permissionsSearch.toLowerCase())) && (permissionsRole === '' || u.role === permissionsRole))" :key="user.id">
                                    <tr class="hover:bg-gray-50/50 transition-colors duration-200">
                                        <!-- User (avatar + name + label) -->
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-[#0e5c3a]/10 text-[#0e5c3a] flex items-center justify-center font-bold text-sm">
                                                    <span x-text="user.name.charAt(0)"></span>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-gray-800 flex items-center gap-2">
                                                        <span x-text="user.name"></span>
                                                        <template x-if="user.tempPassword">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-amber-100 text-amber-800 uppercase tracking-wider">
                                                                Temp password
                                                            </span>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Email -->
                                        <td class="px-6 py-4" x-text="user.email"></td>

                                        <!-- Role -->
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border bg-teal-50 text-teal-700 border-teal-100" x-text="user.role"></span>
                                        </td>

                                        <!-- Department -->
                                        <td class="px-6 py-4" x-text="user.department"></td>

                                        <!-- Active Permissions Count -->
                                        <td class="px-6 py-4 text-center font-bold text-gray-700">
                                            <span x-text="user.activeCount">0</span> / <span x-text="user.totalCount">5</span>
                                        </td>

                                        <!-- Configure Button -->
                                        <td class="px-6 py-4 text-center">
                                            <button 
                                                type="button"
                                                @click="
                                                    selectedUser = user; 
                                                    modalPermissions = { ...user.permissions }; 
                                                    showPermissionsModal = true;
                                                "
                                                class="px-4 py-2 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl flex items-center gap-1.5 shadow-sm transition-all duration-300 mx-auto"
                                            >
                                                <i class="ph ph-sliders text-sm"></i> Configure
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Available Permission Groups Card Section -->
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider">Available Permission Groups</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Panelist Features Group Card -->
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex items-start gap-4">
                            <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 text-2xl flex-shrink-0">
                                <i class="ph ph-shield"></i>
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-bold text-gray-800 text-sm">Panelist Features</h4>
                                <p class="text-xs text-gray-500 font-light leading-relaxed">
                                    For Advisers: Grant panelist capabilities like assigned papers, proposal evaluation, and defense evaluation
                                </p>
                            </div>
                        </div>

                        <!-- Research Facilitator Features Group Card -->
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex items-start gap-4">
                            <div class="w-12 h-12 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-600 text-2xl flex-shrink-0">
                                <i class="ph ph-calendar"></i>
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-bold text-gray-800 text-sm">Research Facilitator Features</h4>
                                <p class="text-xs text-gray-500 font-light leading-relaxed">
                                    For Advisers: Grant facilitator capabilities like user management, research statistics, and screening
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PERMISSIONS CONFIGURATION MODAL -->
            <div 
                x-show="showPermissionsModal" 
                x-cloak 
                class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none"
            >
                <!-- Modal Backdrop overlay -->
                <div 
                    @click="showPermissionsModal = false"
                    class="fixed inset-0 bg-slate-900/60 transition-opacity backdrop-blur-sm"
                ></div>

                <!-- Modal Dialog Container -->
                <div class="relative w-full max-w-xl mx-auto my-6 z-10 px-4">
                    <!-- Modal Card Content -->
                    <div class="relative flex flex-col w-full bg-white border border-gray-100 rounded-[2rem] shadow-2xl outline-none focus:outline-none overflow-hidden">
                        
                        <!-- Header Banner -->
                        <div class="bg-[#0e5c3a] text-white p-6 flex items-start justify-between relative">
                            <div class="flex items-center gap-4">
                                <!-- Initial circle avatar -->
                                <div class="w-12 h-12 rounded-full bg-white/10 text-white font-extrabold flex items-center justify-center text-xl border border-white/20">
                                    <span x-text="selectedUser ? selectedUser.name.charAt(0) : ''"></span>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-lg font-bold" x-text="selectedUser ? selectedUser.name : ''"></h3>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/25 text-white animate-fade-in" x-text="selectedUser ? selectedUser.role : ''"></span>
                                    </div>
                                    <p class="text-xs text-white/70" x-text="selectedUser ? selectedUser.department : ''"></p>
                                </div>
                            </div>
                            <!-- Close X Button -->
                            <button 
                                type="button"
                                @click="showPermissionsModal = false"
                                class="text-white/60 hover:text-white hover:scale-105 transition-all text-xl"
                            >
                                <i class="ph ph-x font-bold"></i>
                            </button>
                        </div>

                        <!-- Modal Alert Banner -->
                        <div class="bg-[#d1e7dd] text-[#0f5132] px-6 py-4 flex items-center gap-2 border-b border-[#badbcc]">
                            <i class="ph ph-lightning text-xl text-[#0f5132]"></i>
                            <span class="text-xs font-semibold">Enabled features appear instantly in this user's sidebar. No re-login required.</span>
                        </div>

                        <!-- Modal Body Options (List of toggles) -->
                        <div class="p-6 max-h-[450px] overflow-y-auto space-y-4">
                            <!-- Section: Panelist Features -->
                            <div class="space-y-3">
                                <h4 class="text-[10px] font-bold tracking-wider text-gray-400 uppercase">Panelist Features</h4>
                                
                                <!-- Toggle 1: Assigned Research Papers -->
                                <div class="flex items-center justify-between p-4 bg-gray-50/60 hover:bg-gray-50 border border-gray-150 rounded-2xl transition-all duration-200">
                                    <div class="space-y-0.5 pr-4">
                                        <h5 class="text-xs font-bold text-gray-800">Assigned Research Papers</h5>
                                        <p class="text-[10px] text-gray-400 font-light">View and access research papers assigned for panel evaluation</p>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="modalPermissions.paper = !modalPermissions.paper"
                                        :class="modalPermissions.paper ? 'bg-[#0e5c3a]' : 'bg-gray-200'"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                                        <span 
                                            :class="modalPermissions.paper ? 'translate-x-5' : 'translate-x-0'"
                                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                        </span>
                                    </button>
                                </div>

                                <!-- Toggle 2: Proposal Evaluation -->
                                <div class="flex items-center justify-between p-4 bg-gray-50/60 hover:bg-gray-50 border border-gray-150 rounded-2xl transition-all duration-200">
                                    <div class="space-y-0.5 pr-4">
                                        <h5 class="text-xs font-bold text-gray-800">Proposal Evaluation</h5>
                                        <p class="text-[10px] text-gray-400 font-light">Evaluate and score student research proposals as a panelist</p>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="modalPermissions.evaluation = !modalPermissions.evaluation"
                                        :class="modalPermissions.evaluation ? 'bg-[#0e5c3a]' : 'bg-gray-200'"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                                        <span 
                                            :class="modalPermissions.evaluation ? 'translate-x-5' : 'translate-x-0'"
                                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                        </span>
                                    </button>
                                </div>

                                <!-- Toggle 3: Final Defense Evaluation -->
                                <div class="flex items-center justify-between p-4 bg-gray-50/60 hover:bg-gray-50 border border-gray-150 rounded-2xl transition-all duration-200">
                                    <div class="space-y-0.5 pr-4">
                                        <h5 class="text-xs font-bold text-gray-800">Final Defense Evaluation</h5>
                                        <p class="text-[10px] text-gray-400 font-light">Grade and provide feedback for final oral defense sessions</p>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="modalPermissions.defense = !modalPermissions.defense"
                                        :class="modalPermissions.defense ? 'bg-[#0e5c3a]' : 'bg-gray-200'"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                                        <span 
                                            :class="modalPermissions.defense ? 'translate-x-5' : 'translate-x-0'"
                                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                        </span>
                                    </button>
                                </div>

                                <!-- Toggle 4: Panel Defense Schedule -->
                                <div class="flex items-center justify-between p-4 bg-gray-50/60 hover:bg-gray-50 border border-gray-150 rounded-2xl transition-all duration-200">
                                    <div class="space-y-0.5 pr-4">
                                        <h5 class="text-xs font-bold text-gray-800">Panel Defense Schedule</h5>
                                        <p class="text-[10px] text-gray-400 font-light">View defense schedule and session assignments as a panelist</p>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="modalPermissions.schedule = !modalPermissions.schedule"
                                        :class="modalPermissions.schedule ? 'bg-[#0e5c3a]' : 'bg-gray-200'"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                                        <span 
                                            :class="modalPermissions.schedule ? 'translate-x-5' : 'translate-x-0'"
                                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                        </span>
                                    </button>
                                </div>

                                <!-- Toggle 5: Panel Recommendations -->
                                <div class="flex items-center justify-between p-4 bg-gray-50/60 hover:bg-gray-50 border border-gray-150 rounded-2xl transition-all duration-200">
                                    <div class="space-y-0.5 pr-4">
                                        <h5 class="text-xs font-bold text-gray-800">Panel Recommendations</h5>
                                        <p class="text-[10px] text-gray-400 font-light">Submit written recommendations and revision requirements</p>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="modalPermissions.recommendations = !modalPermissions.recommendations"
                                        :class="modalPermissions.recommendations ? 'bg-[#0e5c3a]' : 'bg-gray-200'"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                                        <span 
                                            :class="modalPermissions.recommendations ? 'translate-x-5' : 'translate-x-0'"
                                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Section: Research Facilitator Features (Only for Research Advisers) -->
                            <template x-if="selectedUser && selectedUser.role === 'Research Adviser'">
                                <div class="space-y-3 pt-2">
                                    <h4 class="text-[10px] font-bold tracking-wider text-gray-400 uppercase">Research Facilitator Features</h4>
                                    
                                    <!-- Toggle 6: User Management -->
                                    <div class="flex items-center justify-between p-4 bg-gray-50/60 hover:bg-gray-50 border border-gray-150 rounded-2xl transition-all duration-200">
                                        <div class="space-y-0.5 pr-4">
                                            <h5 class="text-xs font-bold text-gray-800">User Management</h5>
                                            <p class="text-[10px] text-gray-400 font-light">Manage staff and student accounts, approve registrations</p>
                                        </div>
                                        <button 
                                            type="button" 
                                            @click="modalPermissions.users = !modalPermissions.users"
                                            :class="modalPermissions.users ? 'bg-[#0e5c3a]' : 'bg-gray-200'"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                                            <span 
                                                :class="modalPermissions.users ? 'translate-x-5' : 'translate-x-0'"
                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                            </span>
                                        </button>
                                    </div>

                                    <!-- Toggle 7: Research Statistics -->
                                    <div class="flex items-center justify-between p-4 bg-gray-50/60 hover:bg-gray-50 border border-gray-150 rounded-2xl transition-all duration-200">
                                        <div class="space-y-0.5 pr-4">
                                            <h5 class="text-xs font-bold text-gray-800">Research Statistics</h5>
                                            <p class="text-[10px] text-gray-400 font-light">Access system-wide research metrics and charts</p>
                                        </div>
                                        <button 
                                            type="button" 
                                            @click="modalPermissions.stats = !modalPermissions.stats"
                                            :class="modalPermissions.stats ? 'bg-[#0e5c3a]' : 'bg-gray-200'"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                                            <span 
                                                :class="modalPermissions.stats ? 'translate-x-5' : 'translate-x-0'"
                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                            </span>
                                        </button>
                                    </div>

                                    <!-- Toggle 8: Document Screening -->
                                    <div class="flex items-center justify-between p-4 bg-gray-50/60 hover:bg-gray-50 border border-gray-150 rounded-2xl transition-all duration-200">
                                        <div class="space-y-0.5 pr-4">
                                            <h5 class="text-xs font-bold text-gray-800">Document Screening</h5>
                                            <p class="text-[10px] text-gray-400 font-light">Review and screen official research submissions</p>
                                        </div>
                                        <button 
                                            type="button" 
                                            @click="modalPermissions.screening = !modalPermissions.screening"
                                            :class="modalPermissions.screening ? 'bg-[#0e5c3a]' : 'bg-gray-200'"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                                            <span 
                                                :class="modalPermissions.screening ? 'translate-x-5' : 'translate-x-0'"
                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Footer buttons -->
                        <div class="p-6 bg-gray-50 flex items-center justify-end gap-3 border-t border-gray-100">
                            <!-- Cancel Button -->
                            <button 
                                type="button" 
                                @click="showPermissionsModal = false"
                                class="px-5 py-3 border border-gray-200 text-gray-500 hover:text-gray-700 text-xs font-bold rounded-2xl transition-all duration-300"
                            >
                                Cancel
                            </button>
                            <!-- Save Button -->
                            <button 
                                type="button" 
                                @click="
                                    selectedUser.permissions = { ...modalPermissions };
                                    selectedUser.activeCount = Object.values(modalPermissions).filter(Boolean).length;
                                    showPermissionsModal = false;
                                "
                                class="px-5 py-3 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-2xl shadow-md shadow-emerald-700/10 hover:shadow-lg transition-all duration-300"
                            >
                                Save Permissions
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: RESEARCH MANAGEMENT VIEW -->
            <div x-show="activeTab === 'research'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title Section -->
                <div>
                    <h1 class="text-3xl font-extrabold font-heading text-gray-800 tracking-tight">Research Lifecycle Tracker</h1>
                    <p class="text-sm text-gray-500 font-light mt-1">Track your research progress through each milestone</p>
                </div>

                <!-- Overall Progress Card -->
                <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100 space-y-6">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <h3 class="text-lg font-bold text-gray-800">Overall Progress</h3>
                            <p class="text-xs text-gray-400 font-light">{{ $researchLifecycle['title'] ?? 'No research project found' }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-extrabold text-emerald-600 font-heading">{{ $researchLifecycle['progress'] }}%</span>
                            <span class="text-[10px] text-gray-400 font-bold block uppercase tracking-wider mt-0.5">Complete</span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full h-4 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-600 rounded-full" x-init="$el.style.width = @js($researchLifecycle['progress']) + '%'"></div>
                    </div>

                    <!-- Counts -->
                    <div class="grid grid-cols-3 gap-6 text-center pt-2">
                        <div>
                            <span class="text-xl font-extrabold text-emerald-600 font-heading block">{{ $researchLifecycle['completed'] }}</span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mt-1 block">Completed</span>
                        </div>
                        <div class="border-l border-r border-gray-150">
                            <span class="text-xl font-extrabold text-amber-500 font-heading block">{{ $researchLifecycle['in_progress'] }}</span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mt-1 block">In Progress</span>
                        </div>
                        <div>
                            <span class="text-xl font-extrabold text-gray-400 font-heading block">{{ $researchLifecycle['pending'] }}</span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mt-1 block">Pending</span>
                        </div>
                    </div>
                </div>

                <!-- Research Milestones Container -->
                <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100 space-y-6">
                    <h3 class="text-base font-bold text-gray-800">Research Milestones</h3>

                    <!-- Timeline Vertical Container -->
                    <div class="relative pl-10 border-l-2 border-gray-150 space-y-8 ml-6 py-2">

                        @forelse ($researchLifecycle['milestones'] as $milestone)
                            @php
                                $milestoneCompleted = $milestone['status'] === 'completed';
                                $milestoneInProgress = $milestone['status'] === 'in_progress';
                            @endphp
                            <div class="relative">
                                <span @class([
                                    'absolute -left-[57px] top-1.5 flex h-8 w-8 items-center justify-center rounded-full shadow-sm',
                                    'bg-[#0fa47b] border border-[#0fa47b]' => $milestoneCompleted,
                                    'bg-[#f59e0b] border border-[#f59e0b]' => $milestoneInProgress,
                                    'bg-white border-2 border-gray-200' => ! $milestoneCompleted && ! $milestoneInProgress,
                                ])>
                                    @if ($milestoneCompleted)
                                        <i class="ph-bold ph-check text-white text-xs"></i>
                                    @elseif ($milestoneInProgress)
                                        <i class="ph-bold ph-clock text-white text-xs"></i>
                                    @else
                                        <span class="w-2.5 h-2.5 bg-gray-200 rounded-full"></span>
                                    @endif
                                </span>
                                <div @class([
                                    'p-6 border rounded-3xl hover:shadow-sm transition-all duration-300 flex items-center justify-between',
                                    'bg-[#f4faf7] border-emerald-100' => $milestoneCompleted,
                                    'bg-[#fdfaf2] border-amber-100' => $milestoneInProgress,
                                    'bg-white border-gray-150 opacity-70' => ! $milestoneCompleted && ! $milestoneInProgress,
                                ])>
                                    <div class="space-y-1.5">
                                        <h4 class="font-extrabold text-gray-800 text-sm">{{ $milestone['name'] }}</h4>
                                        <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                                            <i class="ph ph-calendar"></i>
                                            <span>{{ $milestone['due_at'] ?? 'No due date' }}</span>
                                        </div>
                                        @if ($milestone['description'])
                                            <p class="text-xs text-gray-500 font-medium">{{ $milestone['description'] }}</p>
                                        @endif
                                    </div>
                                    <span @class([
                                        'text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider',
                                        'bg-[#0fa47b]' => $milestoneCompleted,
                                        'bg-amber-500' => $milestoneInProgress,
                                        'bg-gray-400' => ! $milestoneCompleted && ! $milestoneInProgress,
                                    ])>{{ str($milestone['status'])->headline() }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="p-10 bg-gray-50/60 border border-gray-100 rounded-3xl text-center text-sm text-gray-500">
                                No research milestones found.
                            </div>
                        @endforelse

                        {{-- Historical static milestone examples intentionally excluded from rendered output.
                        <!-- Milestone 1: Research Title Presentation (Completed) -->
                        <div class="relative">
                            <span class="absolute -left-[57px] top-1.5 flex h-8 w-8 items-center justify-center rounded-full bg-[#0fa47b] border border-[#0fa47b] shadow-sm">
                                <i class="ph-bold ph-check text-white text-xs"></i>
                            </span>
                            <div class="p-6 bg-[#f4faf7] border border-emerald-100 hover:border-emerald-200 rounded-3xl hover:shadow-sm transition-all duration-300 flex items-center justify-between">
                                <div class="space-y-1.5">
                                    <h4 class="font-extrabold text-gray-800 text-sm">Research Title Presentation</h4>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                                        <i class="ph ph-calendar"></i>
                                        <span>Feb 15, 2026</span>
                                    </div>
                                    <p class="text-xs text-emerald-700 font-medium flex items-center gap-1">✓ All requirements met and approved</p>
                                </div>
                                <span class="bg-[#0fa47b] text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Completed</span>
                            </div>
                        </div>

                        <!-- Milestone 2: Proposal Approval (Completed) -->
                        <div class="relative">
                            <span class="absolute -left-[57px] top-1.5 flex h-8 w-8 items-center justify-center rounded-full bg-[#0fa47b] border border-[#0fa47b] shadow-sm">
                                <i class="ph-bold ph-check text-white text-xs"></i>
                            </span>
                            <div class="p-6 bg-[#f4faf7] border border-emerald-100 hover:border-emerald-200 rounded-3xl hover:shadow-sm transition-all duration-300 flex items-center justify-between">
                                <div class="space-y-1.5">
                                    <h4 class="font-extrabold text-gray-800 text-sm">Proposal Approval</h4>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                                        <i class="ph ph-calendar"></i>
                                        <span>Mar 10, 2026</span>
                                    </div>
                                    <p class="text-xs text-emerald-700 font-medium flex items-center gap-1">✓ All requirements met and approved</p>
                                </div>
                                <span class="bg-[#0fa47b] text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Completed</span>
                            </div>
                        </div>

                        <!-- Milestone 3: Adviser Endorsement (Completed) -->
                        <div class="relative">
                            <span class="absolute -left-[57px] top-1.5 flex h-8 w-8 items-center justify-center rounded-full bg-[#0fa47b] border border-[#0fa47b] shadow-sm">
                                <i class="ph-bold ph-check text-white text-xs"></i>
                            </span>
                            <div class="p-6 bg-[#f4faf7] border border-emerald-100 hover:border-emerald-200 rounded-3xl hover:shadow-sm transition-all duration-300 flex items-center justify-between">
                                <div class="space-y-1.5">
                                    <h4 class="font-extrabold text-gray-800 text-sm">Adviser Endorsement</h4>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                                        <i class="ph ph-calendar"></i>
                                        <span>Mar 20, 2026</span>
                                    </div>
                                    <p class="text-xs text-emerald-700 font-medium flex items-center gap-1">✓ All requirements met and approved</p>
                                </div>
                                <span class="bg-[#0fa47b] text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Completed</span>
                            </div>
                        </div>

                        <!-- Milestone 4: Instrument Validation (Completed) -->
                        <div class="relative">
                            <span class="absolute -left-[57px] top-1.5 flex h-8 w-8 items-center justify-center rounded-full bg-[#0fa47b] border border-[#0fa47b] shadow-sm">
                                <i class="ph-bold ph-check text-white text-xs"></i>
                            </span>
                            <div class="p-6 bg-[#f4faf7] border border-emerald-100 hover:border-emerald-200 rounded-3xl hover:shadow-sm transition-all duration-300 flex items-center justify-between">
                                <div class="space-y-1.5">
                                    <h4 class="font-extrabold text-gray-800 text-sm">Instrument Validation</h4>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                                        <i class="ph ph-calendar"></i>
                                        <span>Apr 5, 2026</span>
                                    </div>
                                    <p class="text-xs text-emerald-700 font-medium flex items-center gap-1">✓ All requirements met and approved</p>
                                </div>
                                <span class="bg-[#0fa47b] text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Completed</span>
                            </div>
                        </div>

                        <!-- Milestone 5: Data Gathering (In Progress) -->
                        <div class="relative">
                            <span class="absolute -left-[57px] top-1.5 flex h-8 w-8 items-center justify-center rounded-full bg-[#f59e0b] border border-[#f59e0b] shadow-sm">
                                <i class="ph-bold ph-clock text-white text-xs"></i>
                            </span>
                            <div class="p-6 bg-[#fdfaf2] border border-amber-100 hover:border-amber-200 rounded-3xl hover:shadow-sm transition-all duration-300 flex items-center justify-between">
                                <div class="space-y-1.5">
                                    <h4 class="font-extrabold text-gray-800 text-sm">Data Gathering</h4>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                                        <i class="ph ph-calendar"></i>
                                        <span>In Progress</span>
                                    </div>
                                    <p class="text-xs text-amber-600 font-medium">Currently working on this milestone</p>
                                </div>
                                <span class="bg-amber-500 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">In Progress</span>
                            </div>
                        </div>

                        <!-- Milestone 6: Proposal Defense (Completed) -->
                        <div class="relative">
                            <span class="absolute -left-[57px] top-1.5 flex h-8 w-8 items-center justify-center rounded-full bg-[#0fa47b] border border-[#0fa47b] shadow-sm">
                                <i class="ph-bold ph-check text-white text-xs"></i>
                            </span>
                            <div class="p-6 bg-[#f4faf7] border border-emerald-100 hover:border-emerald-200 rounded-3xl hover:shadow-sm transition-all duration-300 flex items-center justify-between">
                                <div class="space-y-1.5">
                                    <h4 class="font-extrabold text-gray-800 text-sm">Proposal Defense</h4>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                                        <i class="ph ph-calendar"></i>
                                        <span>May 10, 2026</span>
                                    </div>
                                    <p class="text-xs text-emerald-700 font-medium flex items-center gap-1">✓ All requirements met and approved</p>
                                </div>
                                <span class="bg-[#0fa47b] text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Completed</span>
                            </div>
                        </div>

                        <!-- Milestone 7: Revisions (In Progress) -->
                        <div class="relative">
                            <span class="absolute -left-[57px] top-1.5 flex h-8 w-8 items-center justify-center rounded-full bg-[#f59e0b] border border-[#f59e0b] shadow-sm">
                                <i class="ph-bold ph-clock text-white text-xs"></i>
                            </span>
                            <div class="p-6 bg-[#fdfaf2] border border-amber-100 hover:border-amber-250 rounded-3xl hover:shadow-sm transition-all duration-300 flex items-center justify-between">
                                <div class="space-y-1.5">
                                    <h4 class="font-extrabold text-gray-800 text-sm">Revisions</h4>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                                        <i class="ph ph-calendar"></i>
                                        <span>May 18, 2026</span>
                                    </div>
                                    <p class="text-xs text-amber-600 font-medium">Currently working on this milestone</p>
                                </div>
                                <span class="bg-amber-500 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">In Progress</span>
                            </div>
                        </div>

                        <!-- Milestone 8: Final Defense (Pending) -->
                        <div class="relative">
                            <span class="absolute -left-[57px] top-1.5 flex h-8 w-8 items-center justify-center rounded-full bg-white border-2 border-gray-200 shadow-sm">
                                <span class="w-2.5 h-2.5 bg-gray-200 rounded-full"></span>
                            </span>
                            <div class="p-6 bg-white border border-gray-150 rounded-3xl hover:shadow-sm transition-all duration-300 flex items-center justify-between opacity-70">
                                <div class="space-y-1.5">
                                    <h4 class="font-extrabold text-gray-800 text-sm">Final Defense</h4>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                                        <i class="ph ph-calendar"></i>
                                        <span>Jul 15, 2026</span>
                                    </div>
                                </div>
                                <span class="bg-gray-400 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Pending</span>
                            </div>
                        </div>

                        <!-- Milestone 9: Technical Editing (Pending) -->
                        <div class="relative">
                            <span class="absolute -left-[57px] top-1.5 flex h-8 w-8 items-center justify-center rounded-full bg-white border-2 border-gray-200 shadow-sm">
                                <span class="w-2.5 h-2.5 bg-gray-200 rounded-full"></span>
                            </span>
                            <div class="p-6 bg-white border border-gray-150 rounded-3xl hover:shadow-sm transition-all duration-300 flex items-center justify-between opacity-70">
                                <div class="space-y-1.5">
                                    <h4 class="font-extrabold text-gray-800 text-sm">Technical Editing</h4>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                                        <i class="ph ph-calendar"></i>
                                        <span>Not Started</span>
                                    </div>
                                </div>
                                <span class="bg-gray-400 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Pending</span>
                            </div>
                        </div>

                        <!-- Milestone 10: Language Editing (Pending) -->
                        <div class="relative">
                            <span class="absolute -left-[57px] top-1.5 flex h-8 w-8 items-center justify-center rounded-full bg-white border-2 border-gray-200 shadow-sm">
                                <span class="w-2.5 h-2.5 bg-gray-200 rounded-full"></span>
                            </span>
                            <div class="p-6 bg-white border border-gray-150 rounded-3xl hover:shadow-sm transition-all duration-300 flex items-center justify-between opacity-70">
                                <div class="space-y-1.5">
                                    <h4 class="font-extrabold text-gray-800 text-sm">Language Editing</h4>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                                        <i class="ph ph-calendar"></i>
                                        <span>Not Started</span>
                                    </div>
                                </div>
                                <span class="bg-gray-400 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Pending</span>
                            </div>
                        </div>

                        <!-- Milestone 11: Final Manuscript Approval (Pending) -->
                        <div class="relative">
                            <span class="absolute -left-[57px] top-1.5 flex h-8 w-8 items-center justify-center rounded-full bg-white border-2 border-gray-200 shadow-sm">
                                <span class="w-2.5 h-2.5 bg-gray-200 rounded-full"></span>
                            </span>
                            <div class="p-6 bg-white border border-gray-150 rounded-3xl hover:shadow-sm transition-all duration-300 flex items-center justify-between opacity-70">
                                <div class="space-y-1.5">
                                    <h4 class="font-extrabold text-gray-800 text-sm">Final Manuscript Approval</h4>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                                        <i class="ph ph-calendar"></i>
                                        <span>Not Started</span>
                                    </div>
                                </div>
                                <span class="bg-gray-400 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Pending</span>
                            </div>
                        </div>

                        <!-- Milestone 12: Certificate of Authentic Authorship (Pending) -->
                        <div class="relative">
                            <span class="absolute -left-[57px] top-1.5 flex h-8 w-8 items-center justify-center rounded-full bg-white border-2 border-gray-200 shadow-sm">
                                <span class="w-2.5 h-2.5 bg-gray-200 rounded-full"></span>
                            </span>
                            <div class="p-6 bg-white border border-gray-150 rounded-3xl hover:shadow-sm transition-all duration-300 flex items-center justify-between opacity-70">
                                <div class="space-y-1.5">
                                    <h4 class="font-extrabold text-gray-800 text-sm">Certificate of Authentic Authorship</h4>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                                        <i class="ph ph-calendar"></i>
                                        <span>Not Started</span>
                                    </div>
                                </div>
                                <span class="bg-gray-400 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Pending</span>
                            </div>
                        </div>
                        --}}
                    </div>

                    <!-- Bottom Buttons -->
                    <div class="flex items-center gap-3 pt-4">
                        <button type="button" class="px-6 py-3.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-2xl flex items-center gap-2 shadow-md shadow-emerald-700/10 hover:shadow-lg transition-all duration-300 font-sans">
                            Update Progress
                        </button>
                        <button type="button" class="px-6 py-3.5 border border-gray-200 text-gray-600 hover:text-gray-800 text-xs font-bold rounded-2xl flex items-center gap-2 shadow-sm transition-all duration-300 bg-white font-sans">
                            Download Timeline
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB 5: DEFENSE SCHEDULING VIEW -->
            <div x-show="activeTab === 'defenses'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title & Top Button Section -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-extrabold font-heading text-gray-800 tracking-tight">Defense Scheduling</h1>
                        <p class="text-sm text-gray-500 font-light mt-1">Manage and schedule research defense presentations</p>
                    </div>
                    <button 
                        type="button" 
                        @click="
                            formDefense = { id: null, type: 'Proposal Defense', title: '', student: '', date: '', time: '', duration: '2 hours', venue: '', adviser: '', panelists: [], status: 'Pending', notes: '', generateNotice: true };
                            showScheduleModal = true;
                        "
                        class="px-6 py-3.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-2xl flex items-center gap-2 shadow-md shadow-emerald-700/10 hover:shadow-lg transition-all duration-300 font-sans"
                    >
                        <i class="ph ph-plus text-base"></i> Schedule Defense
                    </button>
                </div>

                <!-- Row of 4 statistics cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Card 1: Total Scheduled -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-emerald-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Scheduled</span>
                            <span class="text-3xl font-extrabold text-gray-800 font-heading" x-text="defensesList.length">3</span>
                        </div>
                        <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                            <i class="ph ph-calendar"></i>
                        </div>
                    </div>

                    <!-- Card 2: This Week -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-blue-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">This Week</span>
                            <span class="text-3xl font-extrabold text-gray-800 font-heading" x-text="defensesList.filter(d => d.status === 'Scheduled').length">2</span>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 text-xl">
                            <i class="ph ph-clock"></i>
                        </div>
                    </div>

                    <!-- Card 3: Pending -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-amber-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Pending</span>
                            <span class="text-3xl font-extrabold text-gray-800 font-heading" x-text="defensesList.filter(d => d.status === 'Pending').length">1</span>
                        </div>
                        <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 text-xl">
                            <i class="ph ph-clock"></i>
                        </div>
                    </div>

                    <!-- Card 4: Completed -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-gray-400 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Completed</span>
                            <span class="text-3xl font-extrabold text-gray-800 font-heading" x-text="defensesList.filter(d => d.status === 'Completed').length">0</span>
                        </div>
                        <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center text-gray-400 text-xl">
                            <i class="ph ph-calendar"></i>
                        </div>
                    </div>
                </div>

                <!-- Filters panel -->
                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-gray-100 flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2 text-gray-400 mr-2">
                        <i class="ph ph-funnel text-lg"></i>
                    </div>
                    <select x-model="defenseFilterType" class="px-4 py-2.5 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300">
                        <option>All Defense Types</option>
                        <option>Proposal Defense</option>
                        <option>Final Defense</option>
                    </select>
                    <select x-model="defenseFilterStatus" class="px-4 py-2.5 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300">
                        <option>All Status</option>
                        <option>Scheduled</option>
                        <option>Pending</option>
                        <option>Completed</option>
                    </select>
                </div>

                <!-- Defenses Schedule Cards list -->
                <div class="space-y-6">
                    <template x-for="defense in defensesList.filter(d => (defenseFilterType === 'All Defense Types' || d.type === defenseFilterType) && (defenseFilterStatus === 'All Status' || d.status === defenseFilterStatus))" :key="defense.id">
                        <div class="bg-white rounded-[2rem] p-8 shadow-sm border-l-4 border-l-emerald-500 border border-gray-100 hover:shadow-md transition-all duration-300 space-y-6">
                            <div class="flex items-start justify-between">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-base font-extrabold text-gray-800 font-heading" x-text="defense.type"></h3>
                                        <span 
                                            :class="defense.status === 'Scheduled' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : (defense.status === 'Pending' ? 'bg-amber-50 text-amber-700 border-amber-100' : 'bg-gray-50 text-gray-700 border-gray-250')"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider" 
                                            x-text="defense.status"
                                        ></span>
                                    </div>
                                    <h4 class="text-sm font-bold text-gray-700 leading-snug" x-text="defense.title"></h4>
                                    <p class="text-xs text-gray-400 font-medium">Student: <span x-text="defense.student"></span></p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <!-- View button -->
                                    <button 
                                        type="button" 
                                        @click="
                                            selectedDefense = defense;
                                            showViewModal = true;
                                        "
                                        class="w-8 h-8 rounded-full hover:bg-blue-50 text-blue-600 flex items-center justify-center transition-colors"
                                    >
                                        <i class="ph ph-eye text-lg"></i>
                                    </button>
                                    <!-- Edit button -->
                                    <button 
                                        type="button" 
                                        @click="
                                            formDefense = JSON.parse(JSON.stringify(defense));
                                            showEditModal = true;
                                        "
                                        class="w-8 h-8 rounded-full hover:bg-emerald-50 text-[#0fa47b] flex items-center justify-center transition-colors"
                                    >
                                        <i class="ph ph-pencil-simple text-lg"></i>
                                    </button>
                                    <!-- Delete button -->
                                    <button 
                                        type="button" 
                                        @click="
                                            if(confirm('Are you sure you want to delete this defense presentation?')) {
                                                defensesList = defensesList.filter(d => d.id !== defense.id);
                                            }
                                        "
                                        class="w-8 h-8 rounded-full hover:bg-red-50 text-red-600 flex items-center justify-center transition-colors"
                                    >
                                        <i class="ph ph-trash text-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2 text-xs font-semibold text-gray-500">
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-calendar text-base text-gray-400"></i>
                                    <span x-text="defense.date"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-clock text-base text-gray-400"></i>
                                    <span x-text="defense.time + ' (' + defense.duration + ')'"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-map-pin text-base text-gray-400"></i>
                                    <span x-text="defense.venue"></span>
                                </div>
                            </div>

                            <!-- Panel Members -->
                            <div class="space-y-2 pt-2 border-t border-gray-100">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Panel Members</span>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="panelist in defense.panelists">
                                        <span class="px-3 py-1.5 rounded-2xl bg-gray-50 border border-gray-150 text-xs text-gray-600 font-semibold" x-text="panelist"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- TAB 6: RESEARCH REPOSITORY VIEW -->
            <div x-show="activeTab === 'repository'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Breadcrumbs & Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-semibold text-gray-400 mb-1">
                            <span>Dashboard</span>
                            <i class="ph ph-caret-right text-[10px]"></i>
                            <span class="text-gray-500">Research Repository</span>
                        </div>
                        <h1 class="text-3xl font-extrabold font-heading text-gray-800 tracking-tight">Research Repository</h1>
                        <p class="text-sm text-gray-500 font-light mt-1">Manage, upload, and track all your research files.</p>
                    </div>
                    <button
                        type="button"
                        disabled
                        title="Administrator uploads are not currently available"
                        class="px-6 py-3.5 bg-[#0e5c3a] text-white text-xs font-bold rounded-2xl flex items-center gap-2 shadow-md shadow-emerald-700/10 font-sans disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <i class="ph ph-upload-simple text-base"></i> Upload Document
                    </button>
                </div>

                <!-- Row of 4 statistics cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total Files -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Files</span>
                            <span class="text-3xl font-extrabold text-gray-800 font-heading" x-text="repositoryList.length"></span>
                        </div>
                        <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                            <i class="ph ph-file-text"></i>
                        </div>
                    </div>

                    <!-- Approved -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Approved</span>
                            <span class="text-3xl font-extrabold text-gray-800 font-heading" x-text="repositoryList.filter(r => r.status === 'Approved').length"></span>
                        </div>
                        <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                            <i class="ph ph-check-circle"></i>
                        </div>
                    </div>

                    <!-- Pending Review -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Pending Review</span>
                            <span class="text-3xl font-extrabold text-gray-800 font-heading" x-text="repositoryList.filter(r => r.status === 'Pending Review').length"></span>
                        </div>
                        <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 text-xl">
                            <i class="ph ph-clock"></i>
                        </div>
                    </div>

                    <!-- For Evaluation -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">For Evaluation</span>
                            <span class="text-3xl font-extrabold text-gray-800 font-heading" x-text="repositoryList.filter(r => r.status === 'For Evaluation').length"></span>
                        </div>
                        <div class="w-12 h-12 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-600 text-xl">
                            <i class="ph ph-clipboard-text"></i>
                        </div>
                    </div>
                </div>

                <!-- Search & Filters -->
                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-gray-100 flex flex-wrap items-center gap-4">
                    <div class="flex-grow relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                            <i class="ph ph-magnifying-glass text-lg"></i>
                        </span>
                        <input 
                            type="text" 
                            x-model="repositorySearch"
                            placeholder="Search documents or researcher name..." 
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300"
                        >
                    </div>
                    <select x-model="repositoryFilter" class="px-4 py-2.5 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300">
                        <option>All Status</option>
                        <option>Approved</option>
                        <option>Pending Review</option>
                        <option>For Evaluation</option>
                        <option>Reviewed</option>
                    </select>
                </div>

                <!-- Repository Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <template x-for="doc in repositoryList.filter(r => (repositoryFilter === 'All Status' || r.status === repositoryFilter) && (r.title.toLowerCase().includes(repositorySearch.toLowerCase()) || r.author.toLowerCase().includes(repositorySearch.toLowerCase())))" :key="doc.id">
                        <div class="bg-white rounded-[2rem] p-6 border border-gray-100 hover:shadow-lg hover:border-emerald-100 transition-all duration-300 flex flex-col justify-between space-y-4">
                            <!-- Card Header -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold" :class="doc.formatColor">
                                        <i class="ph ph-file-pdf text-lg" x-show="doc.type === 'PDF'"></i>
                                        <i class="ph ph-file-doc text-lg" x-show="doc.type === 'DOCX'"></i>
                                    </div>
                                    <span class="text-[10px] font-bold text-gray-500 uppercase" x-text="doc.type"></span>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border" :class="doc.statusClass" x-text="doc.status"></span>
                            </div>

                            <!-- Title & Subtitle -->
                            <div class="space-y-1">
                                <span class="text-[10px] font-extrabold text-[#eebc3f] tracking-wider block" x-text="doc.label"></span>
                                <h3 class="text-sm font-bold text-gray-800 leading-snug hover:text-[#0e5c3a] transition-colors cursor-pointer" x-text="doc.title"></h3>
                                <p class="text-xs text-gray-400 font-light leading-relaxed line-clamp-2" x-text="doc.description"></p>
                            </div>

                            <!-- Footer Details -->
                            <div class="flex items-center justify-between pt-3 border-t border-gray-50 text-[10px] text-gray-400 font-bold">
                                <span x-text="doc.size"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-200"></span>
                                <span x-text="doc.date"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-200"></span>
                                <span x-text="doc.author" class="text-gray-500"></span>
                            </div>

                            <!-- Action Buttons -->
                            <div class="grid grid-cols-2 gap-2 pt-2">
                                <a :href="doc.viewUrl" class="px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-[#0e5c3a] text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 transition-all">
                                    <i class="ph ph-eye text-sm"></i> View
                                </a>
                                <a :href="doc.downloadUrl" class="px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 transition-all">
                                    <i class="ph ph-download-simple text-sm"></i> Download
                                </a>
                            </div>
                        </div>
                    </template>
                    <div x-show="repositoryList.length === 0" class="md:col-span-3 bg-white rounded-[2rem] p-12 border border-gray-100 text-center text-sm text-gray-500">
                        No research documents found.
                    </div>
                </div>
            </div>

            <!-- TAB 7: PROPOSAL MANAGEMENT VIEW -->
            <div x-show="activeTab === 'forms'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Header -->
                <div>
                    <h1 class="text-3xl font-extrabold font-heading text-gray-800 tracking-tight">Proposal Management</h1>
                    <p class="text-sm text-gray-500 font-light mt-1">Manage research proposals and approvals</p>
                </div>

                <!-- Row of 4 statistics cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Approved -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-emerald-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Approved</span>
                            <span class="text-2xl font-extrabold text-gray-800 font-heading" x-text="proposalsList.filter(p => p.status === 'Approved').length"></span>
                        </div>
                        <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 text-lg">
                            <i class="ph ph-check-circle"></i>
                        </div>
                    </div>

                    <!-- Pending -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-amber-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Pending</span>
                            <span class="text-2xl font-extrabold text-gray-800 font-heading" x-text="proposalsList.filter(p => ['Pending', 'Submitted', 'Under Review'].includes(p.status)).length"></span>
                        </div>
                        <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center text-amber-600 text-lg">
                            <i class="ph ph-clock"></i>
                        </div>
                    </div>

                    <!-- Revisions -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-red-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Revisions</span>
                            <span class="text-2xl font-extrabold text-gray-800 font-heading" x-text="proposalsList.filter(p => p.status.includes('Revision')).length"></span>
                        </div>
                        <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center text-red-600 text-lg">
                            <i class="ph ph-x-circle"></i>
                        </div>
                    </div>

                    <!-- Total Proposals -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-blue-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Total Proposals</span>
                            <span class="text-2xl font-extrabold text-gray-800 font-heading" x-text="proposalsList.length"></span>
                        </div>
                        <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 text-lg">
                            <i class="ph ph-file-text"></i>
                        </div>
                    </div>
                </div>

                <!-- Main Proposal Section -->
                <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100 space-y-6">
                    <h3 class="text-base font-extrabold text-gray-800 font-heading">Research Proposal</h3>
                    
                    <template x-for="prop in proposalsList" :key="prop.id">
                        <div class="p-6 bg-emerald-50/30 border border-emerald-100 rounded-3xl space-y-6">
                            <div class="flex items-start justify-between">
                                <div class="space-y-1.5">
                                    <h4 class="text-base font-bold text-[#0e5c3a] leading-snug" x-text="prop.title"></h4>
                                    <p class="text-xs text-gray-400 font-medium">Proposal ID: <span x-text="prop.id"></span></p>
                                    <p class="text-xs text-gray-400 font-medium">Submitted: <span x-text="prop.submitted"></span></p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#0fa47b] text-white uppercase tracking-wider" x-text="prop.status"></span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-emerald-100/50 text-xs">
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Reviewed by</span>
                                    <span class="font-bold text-gray-700" x-text="prop.reviewer"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Approval Date</span>
                                    <span class="font-bold text-gray-700" x-text="prop.approvalDate"></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 pt-2">
                                <a x-show="prop.viewUrl" :href="prop.viewUrl" class="px-5 py-3 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-2xl shadow-sm transition-all duration-300">
                                    View Proposal
                                </a>
                                <a x-show="prop.downloadUrl" :href="prop.downloadUrl" class="px-5 py-3 border border-gray-200 hover:bg-gray-50 text-gray-600 text-xs font-bold rounded-2xl shadow-sm transition-all duration-300 bg-white">
                                    Download PDF
                                </a>
                            </div>
                        </div>
                    </template>
                    <div x-show="proposalsList.length === 0" class="p-10 bg-gray-50/60 border border-gray-100 rounded-3xl text-center text-sm text-gray-500">
                        No research proposals found.
                    </div>
                </div>
            </div>

            <!-- TAB 8: REPORTS & ANALYTICS VIEW -->
            <div x-show="activeTab === 'reports'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-extrabold font-heading text-gray-800 tracking-tight">Analytics & Reports</h1>
                        <p class="text-sm text-gray-500 font-light mt-1">Research statistics and performance metrics</p>
                    </div>
                    <button 
                        type="button" 
                        @click="alert('Exporting PDF Report')"
                        class="px-6 py-3.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-2xl flex items-center gap-2 shadow-md shadow-emerald-700/10 hover:shadow-lg transition-all duration-300 font-sans"
                    >
                        <i class="ph ph-download text-base"></i> Export Report
                    </button>
                </div>

                <!-- Row of 4 statistics cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total Research -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Research</span>
                            <span class="text-2xl font-extrabold text-gray-800 font-heading block">{{ $totalResearchCount }}</span>
                            <span class="text-[10px] font-bold text-emerald-600 tracking-wide block">Database total</span>
                        </div>
                        <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                            <i class="ph ph-chart-bar"></i>
                        </div>
                    </div>

                    <!-- Completed -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Completed</span>
                            <span class="text-2xl font-extrabold text-gray-800 font-heading block">{{ $completedResearchCount }}</span>
                            <span class="text-[10px] font-bold text-blue-500 tracking-wide block">{{ $researchCompletionRate }}% completion rate</span>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 text-xl">
                            <i class="ph ph-trend-up"></i>
                        </div>
                    </div>

                    <!-- In Progress -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">In Progress</span>
                            <span class="text-2xl font-extrabold text-gray-800 font-heading block">{{ $activeResearchCount }}</span>
                            <span class="text-[10px] font-bold text-amber-500 tracking-wide block">{{ $researchInProgressRate }}% ongoing</span>
                        </div>
                        <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 text-xl">
                            <i class="ph ph-chart-pie-slice"></i>
                        </div>
                    </div>

                    <!-- Avg Duration -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Avg Duration</span>
                            <span class="text-2xl font-extrabold text-gray-800 font-heading block">{{ $averageResearchMonths ?? '—' }}</span>
                            <span class="text-[10px] font-bold text-purple-500 tracking-wide block">months</span>
                        </div>
                        <div class="w-12 h-12 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-600 text-xl">
                            <i class="ph ph-hourglass-high"></i>
                        </div>
                    </div>
                </div>

                <!-- Grid of Charts -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Research by Program -->
                    <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100 space-y-6">
                        <h3 class="text-base font-extrabold text-gray-800 font-heading">Research by Program</h3>
                        
                        <div class="space-y-4">
                            @forelse ($researchByProgram as $program)
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-bold">
                                    <span class="text-gray-600">{{ $program['name'] }}</span>
                                    <span class="text-gray-800">{{ $program['count'] }}</span>
                                </div>
                                <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-[#0e5c3a] rounded-full" x-init="$el.style.width = @js($program['percentage']) + '%'"></div>
                                </div>
                            </div>
                            @empty
                                <p class="py-10 text-center text-sm text-gray-500">No research program data found.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Monthly Submissions Bar Chart -->
                    <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100 space-y-6">
                        <h3 class="text-base font-extrabold text-gray-800 font-heading">Monthly Submissions</h3>
                        
                        <div class="flex items-end justify-between h-48 pt-4">
                            <!-- Jan -->
                            <div class="flex flex-col items-center flex-1 h-full justify-end group">
                                <div class="w-6 bg-[#0e5c3a] rounded-t-lg transition-all duration-300 group-hover:bg-[#0a4a2e]" x-init="$el.style.height = @js($monthlyResearchSubmissions[0]['percentage']) + '%'" title="{{ $monthlyResearchSubmissions[0]['count'] }} submissions"></div>
                                <span class="text-[9px] font-bold text-gray-400 mt-2">Jan</span>
                            </div>
                            <!-- Feb -->
                            <div class="flex flex-col items-center flex-1 h-full justify-end group">
                                <div class="w-6 bg-[#0e5c3a] rounded-t-lg transition-all duration-300 group-hover:bg-[#0a4a2e]" x-init="$el.style.height = @js($monthlyResearchSubmissions[1]['percentage']) + '%'" title="{{ $monthlyResearchSubmissions[1]['count'] }} submissions"></div>
                                <span class="text-[9px] font-bold text-gray-400 mt-2">Feb</span>
                            </div>
                            <!-- Mar -->
                            <div class="flex flex-col items-center flex-1 h-full justify-end group">
                                <div class="w-6 bg-[#0e5c3a] rounded-t-lg transition-all duration-300 group-hover:bg-[#0a4a2e]" x-init="$el.style.height = @js($monthlyResearchSubmissions[2]['percentage']) + '%'" title="{{ $monthlyResearchSubmissions[2]['count'] }} submissions"></div>
                                <span class="text-[9px] font-bold text-gray-400 mt-2">Mar</span>
                            </div>
                            <!-- Apr -->
                            <div class="flex flex-col items-center flex-1 h-full justify-end group">
                                <div class="w-6 bg-[#0e5c3a] rounded-t-lg transition-all duration-300 group-hover:bg-[#0a4a2e]" x-init="$el.style.height = @js($monthlyResearchSubmissions[3]['percentage']) + '%'" title="{{ $monthlyResearchSubmissions[3]['count'] }} submissions"></div>
                                <span class="text-[9px] font-bold text-gray-400 mt-2">Apr</span>
                            </div>
                            <!-- May -->
                            <div class="flex flex-col items-center flex-1 h-full justify-end group">
                                <div class="w-6 bg-[#0e5c3a] rounded-t-lg transition-all duration-300 group-hover:bg-[#0a4a2e]" x-init="$el.style.height = @js($monthlyResearchSubmissions[4]['percentage']) + '%'" title="{{ $monthlyResearchSubmissions[4]['count'] }} submissions"></div>
                                <span class="text-[9px] font-bold text-gray-400 mt-2">May</span>
                            </div>
                            <!-- Jun -->
                            <div class="flex flex-col items-center flex-1 h-full justify-end group">
                                <div class="w-6 bg-[#0e5c3a] rounded-t-lg transition-all duration-300 group-hover:bg-[#0a4a2e]" x-init="$el.style.height = @js($monthlyResearchSubmissions[5]['percentage']) + '%'" title="{{ $monthlyResearchSubmissions[5]['count'] }} submissions"></div>
                                <span class="text-[9px] font-bold text-gray-400 mt-2">Jun</span>
                            </div>
                            <!-- Jul -->
                            <div class="flex flex-col items-center flex-1 h-full justify-end group">
                                <div class="w-6 bg-[#0e5c3a] rounded-t-lg transition-all duration-300 group-hover:bg-[#0a4a2e]" x-init="$el.style.height = @js($monthlyResearchSubmissions[6]['percentage']) + '%'" title="{{ $monthlyResearchSubmissions[6]['count'] }} submissions"></div>
                                <span class="text-[9px] font-bold text-gray-400 mt-2">Jul</span>
                            </div>
                            <!-- Aug -->
                            <div class="flex flex-col items-center flex-1 h-full justify-end group">
                                <div class="w-6 bg-[#0e5c3a] rounded-t-lg transition-all duration-300 group-hover:bg-[#0a4a2e]" x-init="$el.style.height = @js($monthlyResearchSubmissions[7]['percentage']) + '%'" title="{{ $monthlyResearchSubmissions[7]['count'] }} submissions"></div>
                                <span class="text-[9px] font-bold text-gray-400 mt-2">Aug</span>
                            </div>
                            <!-- Sep -->
                            <div class="flex flex-col items-center flex-1 h-full justify-end group">
                                <div class="w-6 bg-[#0e5c3a] rounded-t-lg transition-all duration-300 group-hover:bg-[#0a4a2e]" x-init="$el.style.height = @js($monthlyResearchSubmissions[8]['percentage']) + '%'" title="{{ $monthlyResearchSubmissions[8]['count'] }} submissions"></div>
                                <span class="text-[9px] font-bold text-gray-400 mt-2">Sep</span>
                            </div>
                            <!-- Oct -->
                            <div class="flex flex-col items-center flex-1 h-full justify-end group">
                                <div class="w-6 bg-[#0e5c3a] rounded-t-lg transition-all duration-300 group-hover:bg-[#0a4a2e]" x-init="$el.style.height = @js($monthlyResearchSubmissions[9]['percentage']) + '%'" title="{{ $monthlyResearchSubmissions[9]['count'] }} submissions"></div>
                                <span class="text-[9px] font-bold text-gray-400 mt-2">Oct</span>
                            </div>
                            <!-- Nov -->
                            <div class="flex flex-col items-center flex-1 h-full justify-end group">
                                <div class="w-6 bg-[#0e5c3a] rounded-t-lg transition-all duration-300 group-hover:bg-[#0a4a2e]" x-init="$el.style.height = @js($monthlyResearchSubmissions[10]['percentage']) + '%'" title="{{ $monthlyResearchSubmissions[10]['count'] }} submissions"></div>
                                <span class="text-[9px] font-bold text-gray-400 mt-2">Nov</span>
                            </div>
                            <!-- Dec -->
                            <div class="flex flex-col items-center flex-1 h-full justify-end group">
                                <div class="w-6 bg-[#0e5c3a] rounded-t-lg transition-all duration-300 group-hover:bg-[#0a4a2e]" x-init="$el.style.height = @js($monthlyResearchSubmissions[11]['percentage']) + '%'" title="{{ $monthlyResearchSubmissions[11]['count'] }} submissions"></div>
                                <span class="text-[9px] font-bold text-gray-400 mt-2">Dec</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 9: AUDIT LOGS VIEW -->
            <div x-show="activeTab === 'audit'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Header -->
                <div>
                    <h1 class="text-3xl font-extrabold font-heading text-gray-800 tracking-tight">Research Revision Tracker</h1>
                    <p class="text-sm text-gray-500 font-light mt-1">Track and manage document revisions</p>
                </div>

                <!-- Row of 3 statistics cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Pending Revisions -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-amber-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Pending Revisions</span>
                            <span class="text-2xl font-extrabold text-gray-800 font-heading block">{{ $revisionStats['pending'] }}</span>
                        </div>
                        <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center text-amber-600 text-lg">
                            <i class="ph ph-clock"></i>
                        </div>
                    </div>

                    <!-- Completed -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-emerald-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Completed</span>
                            <span class="text-2xl font-extrabold text-gray-800 font-heading block">{{ $revisionStats['completed'] }}</span>
                        </div>
                        <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 text-lg">
                            <i class="ph ph-check-circle"></i>
                        </div>
                    </div>

                    <!-- Overdue -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border-l-4 border-l-red-500 border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Overdue</span>
                            <span class="text-2xl font-extrabold text-gray-800 font-heading block">{{ $revisionStats['overdue'] }}</span>
                        </div>
                        <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center text-red-600 text-lg">
                            <i class="ph ph-warning"></i>
                        </div>
                    </div>
                </div>

                <!-- Revision History -->
                <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100 space-y-6">
                    <h3 class="text-base font-extrabold text-gray-800 font-heading">Revision History</h3>
                    
                    <div class="space-y-4">
                        @forelse ($revisionHistory as $revision)
                            @php($resolved = $revision['statusValue'] === 'resolved')
                            <div @class([
                                'p-6 border-l-4 border border-gray-100 rounded-2xl flex items-center justify-between',
                                'bg-emerald-50/20 border-l-emerald-500' => $resolved,
                                'bg-amber-50/20 border-l-amber-500' => ! $resolved,
                            ])>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-extrabold text-gray-800">Revision #{{ $revision['id'] }}</span>
                                        <span @class([
                                            'px-2 py-0.5 rounded-full text-[9px] font-bold border uppercase tracking-wider',
                                            'bg-emerald-100 text-emerald-800 border-emerald-200' => $resolved,
                                            'bg-amber-100 text-amber-800 border-amber-200' => ! $resolved,
                                        ])>{{ $revision['status'] }}</span>
                                    </div>
                                    <p class="text-xs font-semibold text-gray-700">{{ $revision['title'] }}</p>
                                    <p class="text-[10px] text-gray-400 font-bold">{{ $revision['date'] }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="p-10 bg-gray-50/60 border border-gray-100 rounded-2xl text-center text-sm text-gray-500">
                                No revision records found.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- SCHEDULE NEW DEFENSE MODAL -->
            <div 
                x-show="showScheduleModal" 
                x-cloak 
                class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none"
            >
                <div @click="showScheduleModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="relative w-full max-w-xl mx-auto my-6 z-10 px-4">
                    <div class="relative flex flex-col w-full bg-white border border-gray-150 rounded-[2rem] shadow-2xl overflow-hidden max-h-[90vh]">
                        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-800">Schedule New Defense</h3>
                            <button @click="showScheduleModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="ph ph-x text-xl"></i>
                            </button>
                        </div>
                        <div class="p-6 overflow-y-auto space-y-6">
                            <div class="space-y-4">
                                <h4 class="text-xs font-bold text-[#0e5c3a] flex items-center gap-2 uppercase tracking-wider">
                                    <i class="ph ph-file-text text-base"></i> Research Information
                                </h4>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-gray-600 uppercase block">Research Title *</label>
                                    <input type="text" x-model="formDefense.title" placeholder="Enter research title" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-gray-600 uppercase block">Student Researcher Name *</label>
                                        <input type="text" x-model="formDefense.student" placeholder="Enter student name" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-gray-600 uppercase block">Defense Type *</label>
                                        <select x-model="formDefense.type" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                            <option>Proposal Defense</option>
                                            <option>Final Defense</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-4 pt-4 border-t border-gray-100">
                                <h4 class="text-xs font-bold text-[#0e5c3a] flex items-center gap-2 uppercase tracking-wider">
                                    <i class="ph ph-calendar text-base"></i> Schedule Details
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-gray-600 uppercase block">Date *</label>
                                        <input type="date" x-model="formDefense.date" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-gray-600 uppercase block">Time *</label>
                                        <input type="time" x-model="formDefense.time" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-gray-600 uppercase block">Duration (hours) *</label>
                                        <select x-model="formDefense.duration" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                            <option>1 hour</option>
                                            <option>1.5 hours</option>
                                            <option>2 hours</option>
                                            <option>2.5 hours</option>
                                            <option>3 hours</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-gray-600 uppercase block">Venue / Room *</label>
                                        <input type="text" x-model="formDefense.venue" placeholder="e.g., Room 405" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-4 pt-4 border-t border-gray-100">
                                <h4 class="text-xs font-bold text-[#0e5c3a] flex items-center gap-2 uppercase tracking-wider">
                                    <i class="ph ph-users text-base"></i> Panel Members
                                </h4>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-gray-600 uppercase block">Research Adviser *</label>
                                    <select x-model="formDefense.adviser" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                        <option value="">Select Adviser</option>
                                        <template x-for="adviser in adviserOptions" :key="adviser">
                                            <option :value="adviser" x-text="adviser"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="space-y-3">
                                    <label class="text-xs font-bold text-gray-600 uppercase block">Assigned Panelists</label>
                                    <div class="flex gap-2">
                                        <select x-model="panelistInput" class="flex-grow px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                            <option value="">Select Panelist to Add</option>
                                            <template x-for="panelist in panelistOptions" :key="panelist">
                                                <option :value="panelist" x-text="panelist"></option>
                                            </template>
                                        </select>
                                        <button 
                                            type="button" 
                                            @click="
                                                if(panelistInput && !formDefense.panelists.includes(panelistInput)) {
                                                    formDefense.panelists.push(panelistInput);
                                                    panelistInput = '';
                                                }
                                            "
                                            class="px-4 py-3 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-2xl transition-all"
                                        >
                                            Add
                                        </button>
                                    </div>
                                    <div class="flex flex-wrap gap-2 pt-1">
                                        <template x-for="(panelist, idx) in formDefense.panelists" :key="idx">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-[#0e5c3a] border border-emerald-100">
                                                <span x-text="panelist"></span>
                                                <button type="button" @click="formDefense.panelists.splice(idx, 1)" class="hover:text-red-500 font-bold text-sm">
                                                    <i class="ph ph-x"></i>
                                                </button>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-4 pt-4 border-t border-gray-100">
                                <h4 class="text-xs font-bold text-[#0e5c3a] flex items-center gap-2 uppercase tracking-wider">
                                    Additional Details
                                </h4>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-gray-600 uppercase block">Status</label>
                                    <select x-model="formDefense.status" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                        <option>Pending</option>
                                        <option>Scheduled</option>
                                        <option>Completed</option>
                                    </select>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-gray-600 uppercase block">Notes / Remarks</label>
                                    <textarea x-model="formDefense.notes" placeholder="Add any additional notes or special instructions..." rows="3" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all"></textarea>
                                </div>
                                <div class="flex items-center gap-2 pt-2">
                                    <input type="checkbox" x-model="formDefense.generateNotice" id="generateNotice" class="rounded border-gray-300 text-[#0e5c3a] focus:ring-[#0e5c3a]">
                                    <label for="generateNotice" class="text-xs font-bold text-gray-600">Generate Defense Notice and Send Notifications</label>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button type="button" @click="showScheduleModal = false" class="px-5 py-3 border border-gray-200 text-gray-500 hover:text-gray-700 text-xs font-bold rounded-2xl transition-all">
                                Cancel
                            </button>
                            <button 
                                type="button" 
                                @click="
                                    if(formDefense.title && formDefense.student) {
                                        const newId = Math.max(...defensesList.map(d => d.id), 0) + 1;
                                        defensesList.push({
                                            id: newId,
                                            type: formDefense.type,
                                            title: formDefense.title,
                                            student: formDefense.student,
                                            date: formDefense.date || '2026-05-25',
                                            time: formDefense.time || '09:00',
                                            duration: formDefense.duration,
                                            venue: formDefense.venue || 'TBD',
                                            adviser: formDefense.adviser || 'TBD',
                                            panelists: [...formDefense.panelists],
                                            status: formDefense.status,
                                            notes: formDefense.notes,
                                            generateNotice: formDefense.generateNotice
                                        });
                                        showScheduleModal = false;
                                    } else {
                                        alert('Please fill out Title and Student Researcher Name.');
                                    }
                                "
                                class="px-5 py-3 bg-[#00a86b] hover:bg-[#008f5a] text-white text-xs font-bold rounded-2xl flex items-center gap-2 transition-all"
                            >
                                <i class="ph ph-floppy-disk text-base"></i> Save Schedule
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EDIT DEFENSE SCHEDULE MODAL -->
            <div 
                x-show="showEditModal" 
                x-cloak 
                class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none"
            >
                <div @click="showEditModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="relative w-full max-w-xl mx-auto my-6 z-10 px-4">
                    <div class="relative flex flex-col w-full bg-white border border-gray-150 rounded-[2rem] shadow-2xl overflow-hidden max-h-[90vh]">
                        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-800">Edit Defense Schedule</h3>
                            <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="ph ph-x text-xl"></i>
                            </button>
                        </div>
                        <div class="p-6 overflow-y-auto space-y-6">
                            <div class="space-y-4">
                                <h4 class="text-xs font-bold text-[#0e5c3a] flex items-center gap-2 uppercase tracking-wider">
                                    <i class="ph ph-file-text text-base"></i> Research Information
                                </h4>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-gray-600 uppercase block">Research Title *</label>
                                    <input type="text" x-model="formDefense.title" placeholder="Enter research title" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-gray-600 uppercase block">Student Researcher Name *</label>
                                        <input type="text" x-model="formDefense.student" placeholder="Enter student name" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-gray-600 uppercase block">Defense Type *</label>
                                        <select x-model="formDefense.type" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                            <option>Proposal Defense</option>
                                            <option>Final Defense</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-4 pt-4 border-t border-gray-100">
                                <h4 class="text-xs font-bold text-[#0e5c3a] flex items-center gap-2 uppercase tracking-wider">
                                    <i class="ph ph-calendar text-base"></i> Schedule Details
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-gray-600 uppercase block">Date *</label>
                                        <input type="date" x-model="formDefense.date" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-gray-600 uppercase block">Time *</label>
                                        <input type="time" x-model="formDefense.time" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-gray-600 uppercase block">Duration (hours) *</label>
                                        <select x-model="formDefense.duration" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                            <option>1 hour</option>
                                            <option>1.5 hours</option>
                                            <option>2 hours</option>
                                            <option>2.5 hours</option>
                                            <option>3 hours</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-gray-600 uppercase block">Venue / Room *</label>
                                        <input type="text" x-model="formDefense.venue" placeholder="e.g., Room 405" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-4 pt-4 border-t border-gray-100">
                                <h4 class="text-xs font-bold text-[#0e5c3a] flex items-center gap-2 uppercase tracking-wider">
                                    <i class="ph ph-users text-base"></i> Panel Members
                                </h4>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-gray-600 uppercase block">Research Adviser *</label>
                                    <select x-model="formDefense.adviser" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                        <option value="">Select Adviser</option>
                                        <template x-for="adviser in adviserOptions" :key="adviser">
                                            <option :value="adviser" x-text="adviser"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="space-y-3">
                                    <label class="text-xs font-bold text-gray-600 uppercase block">Assigned Panelists</label>
                                    <div class="flex gap-2">
                                        <select x-model="panelistInput" class="flex-grow px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                            <option value="">Select Panelist to Add</option>
                                            <template x-for="panelist in panelistOptions" :key="panelist">
                                                <option :value="panelist" x-text="panelist"></option>
                                            </template>
                                        </select>
                                        <button 
                                            type="button" 
                                            @click="
                                                if(panelistInput && !formDefense.panelists.includes(panelistInput)) {
                                                    formDefense.panelists.push(panelistInput);
                                                    panelistInput = '';
                                                }
                                            "
                                            class="px-4 py-3 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-2xl transition-all"
                                        >
                                            Add
                                        </button>
                                    </div>
                                    <div class="flex flex-wrap gap-2 pt-1">
                                        <template x-for="(panelist, idx) in formDefense.panelists" :key="idx">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-[#0e5c3a] border border-emerald-100">
                                                <span x-text="panelist"></span>
                                                <button type="button" @click="formDefense.panelists.splice(idx, 1)" class="hover:text-red-500 font-bold text-sm">
                                                    <i class="ph ph-x"></i>
                                                </button>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-4 pt-4 border-t border-gray-100">
                                <h4 class="text-xs font-bold text-[#0e5c3a] flex items-center gap-2 uppercase tracking-wider">
                                    Additional Details
                                </h4>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-gray-600 uppercase block">Status</label>
                                    <select x-model="formDefense.status" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                        <option>Pending</option>
                                        <option>Scheduled</option>
                                        <option>Completed</option>
                                    </select>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-gray-600 uppercase block">Notes / Remarks</label>
                                    <textarea x-model="formDefense.notes" placeholder="Add any additional notes or special instructions..." rows="3" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all"></textarea>
                                </div>
                                <div class="flex items-center gap-2 pt-2">
                                    <input type="checkbox" x-model="formDefense.generateNotice" id="editGenerateNotice" class="rounded border-gray-300 text-[#0e5c3a] focus:ring-[#0e5c3a]">
                                    <label for="editGenerateNotice" class="text-xs font-bold text-gray-600">Generate Defense Notice and Send Notifications</label>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button type="button" @click="showEditModal = false" class="px-5 py-3 border border-gray-200 text-gray-500 hover:text-gray-700 text-xs font-bold rounded-2xl transition-all">
                                Cancel
                            </button>
                            <button 
                                type="button" 
                                @click="
                                    if(formDefense.title && formDefense.student) {
                                        const idx = defensesList.findIndex(d => d.id === formDefense.id);
                                        if (idx !== -1) {
                                            defensesList[idx] = { ...formDefense };
                                        }
                                        showEditModal = false;
                                    } else {
                                        alert('Please fill out Title and Student Researcher Name.');
                                    }
                                "
                                class="px-5 py-3 bg-[#00a86b] hover:bg-[#008f5a] text-white text-xs font-bold rounded-2xl flex items-center gap-2 transition-all"
                            >
                                <i class="ph ph-floppy-disk text-base"></i> Save Schedule
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DEFENSE DETAILS VIEW MODAL -->
            <div 
                x-show="showViewModal" 
                x-cloak 
                class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none"
            >
                <div @click="showViewModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="relative w-full max-w-xl mx-auto my-6 z-10 px-4">
                    <div class="relative flex flex-col w-full bg-white border border-gray-150 rounded-[2rem] shadow-2xl overflow-hidden">
                        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-800">Defense Details</h3>
                            <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="ph ph-x text-xl"></i>
                            </button>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="p-6 bg-[#e6f7f0] border border-emerald-100 rounded-3xl space-y-3">
                                <h4 class="text-base font-extrabold text-[#0e5c3a]" x-text="selectedDefense ? selectedDefense.title : ''"></h4>
                                <p class="text-xs text-gray-500 font-bold" x-text="selectedDefense ? selectedDefense.student : ''"></p>
                                <div class="flex items-center gap-2 pt-1">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#0fa47b] text-white uppercase tracking-wider" x-text="selectedDefense ? selectedDefense.type : ''"></span>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white text-gray-500 border border-gray-200 uppercase tracking-wider" x-text="selectedDefense ? selectedDefense.status : ''"></span>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-4 bg-gray-50/50 border border-gray-150 rounded-2xl space-y-1">
                                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Date</span>
                                        <p class="text-xs font-bold text-gray-700" x-text="selectedDefense ? selectedDefense.date : ''"></p>
                                    </div>
                                    <div class="p-4 bg-gray-50/50 border border-gray-150 rounded-2xl space-y-1">
                                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Time</span>
                                        <p class="text-xs font-bold text-gray-700" x-text="selectedDefense ? selectedDefense.time : ''"></p>
                                    </div>
                                </div>
                                <div class="p-4 bg-gray-50/50 border border-gray-150 rounded-2xl space-y-1">
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Venue</span>
                                    <p class="text-xs font-bold text-gray-700" x-text="selectedDefense ? selectedDefense.venue : ''"></p>
                                </div>
                                <div class="p-4 bg-blue-50/40 border border-blue-100 text-blue-800 rounded-2xl space-y-1">
                                    <span class="text-[9px] font-bold text-blue-400 uppercase tracking-wider">Research Adviser</span>
                                    <p class="text-xs font-bold" x-text="selectedDefense ? selectedDefense.adviser : ''"></p>
                                </div>
                                <div class="space-y-2">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Panel Members</span>
                                    <div class="space-y-2">
                                        <template x-for="panelist in (selectedDefense ? selectedDefense.panelists : [])">
                                            <div class="p-3 bg-white border border-gray-150 rounded-2xl text-xs text-gray-700 font-bold" x-text="panelist"></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button 
                                type="button" 
                                @click="
                                    formDefense = JSON.parse(JSON.stringify(selectedDefense));
                                    showEditModal = true;
                                    showViewModal = false;
                                "
                                class="px-5 py-3 bg-[#0fa47b] hover:bg-[#0a825e] text-white text-xs font-bold rounded-2xl transition-all"
                            >
                                Edit Schedule
                            </button>
                            <button type="button" @click="showViewModal = false" class="px-5 py-3 border border-gray-200 text-gray-500 hover:text-gray-700 text-xs font-bold rounded-2xl transition-all bg-white">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
