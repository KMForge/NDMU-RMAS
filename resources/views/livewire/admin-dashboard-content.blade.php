{{-- Livewire UI rendered by the AdminDashboard component. --}}
@php
    $pendingUsersCount = $pendingApprovalCount ?? ($pendingUsersCount ?? (isset($pendingStudents) ? $pendingStudents->count() : 0));
@endphp
<div
    class="min-h-screen flex font-sans bg-[#f4f7f6]"
    data-portal-shell
    x-data="{
    activeTab: $wire.entangle('tab'),
    userManagementTab: $wire.entangle('userManagementTab'),
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
    x-init="
        $watch('activeTab', () => $nextTick(() => window.dispatchEvent(new CustomEvent('portal:layout-changed'))));
        $watch('userManagementTab', () => $nextTick(() => window.dispatchEvent(new CustomEvent('portal:layout-changed'))));
    "
    @staff-account-created.window="activeTab = 'users'; userManagementTab = 'all-users'"
    x-on:role-editor-opened.window="activeTab = 'permissions'; $nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))"
    x-on:role-editor-closed.window="activeTab = 'permissions'; $nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))"
    x-on:role-assignment-opened.window="activeTab = 'assign-roles'; $nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))"
    x-on:role-assignment-closed.window="activeTab = 'users'; userManagementTab = 'all-users'; $nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))"
>
    <style>[x-cloak] { display: none !important; }</style>
    <!-- Left Sidebar: Navigation -->
    <aside id="admin-primary-navigation" data-portal-sidebar class="fixed inset-y-0 left-0 w-72 bg-gradient-to-b from-[#09472d] via-[#0e5c3a] to-[#073622] text-white flex flex-col justify-between z-20 border-r border-emerald-800/40 shadow-2xl overflow-y-auto">
        <div class="flex-shrink-0">
            <!-- Brand Logo Header -->
            <div class="p-6 pb-4 flex items-center gap-3.5">
                <div class="p-2 bg-gradient-to-br from-white/15 to-white/5 rounded-2xl border border-white/20 shadow-lg backdrop-blur-md">
                    <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="NDMU Logo" width="96" height="96" class="h-10 w-auto drop-shadow-sm">
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-heading font-black text-xl text-white tracking-tight">NDMU</span>
                    <span class="text-[9px] font-black text-[#eebc3f] tracking-[0.16em] uppercase mt-1">Research Management</span>
                </div>
            </div>

            <!-- Designer Decorative Underline under Logo -->
            <div class="px-6 my-2 flex items-center justify-center gap-2">
                <div class="h-px flex-1 bg-gradient-to-r from-transparent via-[#eebc3f]/60 to-[#eebc3f]"></div>
                <div class="h-1 w-8 rounded-full bg-gradient-to-r from-[#eebc3f] to-[#ffd76f] shadow-[0_0_8px_rgba(238,188,63,0.7)]"></div>
                <div class="h-px flex-1 bg-gradient-to-l from-transparent via-[#eebc3f]/60 to-[#eebc3f]"></div>
            </div>

            <!-- Floating Profile Card -->
            <div class="px-5 py-3">
                <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/[0.06] border border-white/10 shadow-inner backdrop-blur-xs hover:bg-white/[0.09] transition-all">
                    <div class="relative w-10 h-10 rounded-xl bg-gradient-to-tr from-[#eebc3f] to-[#ffd76f] text-[#09472d] font-black flex items-center justify-center text-lg flex-shrink-0 shadow-md">
                        {{ mb_strtoupper(mb_substr($administrator?->name ?? 'A', 0, 1)) }}
                        <span class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500 border-2 border-[#09472d]"></span>
                        </span>
                    </div>
                    <div class="flex flex-col leading-tight overflow-hidden min-w-0">
                        <span class="font-bold text-xs text-white truncate">{{ $administrator?->name ?? 'Administrator' }}</span>
                        <span class="text-[10px] text-white/70 font-medium mt-0.5 truncate">System Administrator</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-grow px-5 py-3 space-y-6">
            <div class="space-y-1">
                <div class="flex items-center gap-2 px-3 mb-2.5">
                    <span class="w-1 h-3 rounded-full bg-[#eebc3f]"></span>
                    <span class="text-[10px] font-black tracking-[0.18em] text-emerald-300/80 uppercase">Navigation</span>
                </div>
                
                <!-- Dashboard Link -->
                <button
                   type="button"
                   @click="activeTab = 'dashboard'"
                   :class="activeTab === 'dashboard' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-squares-four text-lg transition-transform group-hover:scale-110"></i>
                        <span>Dashboard</span>
                    </div>
                    <span x-show="activeTab === 'dashboard'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </button>
                
                <!-- User Management Link -->
                <button
                   type="button"
                   @click="activeTab = 'users'"
                   :class="['users', 'assign-roles'].includes(activeTab) ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-users text-lg transition-transform group-hover:scale-110"></i>
                        <span>User Management</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['users'] ?? 0" label="student registrations awaiting email verification" />
                        <span x-show="['users', 'assign-roles'].includes(activeTab)" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </button>

                <button
                   type="button"
                   @click="activeTab = 'permissions'"
                   :class="activeTab === 'permissions' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-shield-check text-lg transition-transform group-hover:scale-110"></i>
                        <span>Roles &amp; Permissions</span>
                    </div>
                    <span x-show="activeTab === 'permissions'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </button>

                <button
                   type="button"
                   @click="activeTab = 'research'"
                   :class="activeTab === 'research' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-book-open text-lg transition-transform group-hover:scale-110"></i>
                        <span>Research Management</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['research'] ?? 0" label="active research studies" />
                        <span x-show="activeTab === 'research'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </button>

                <button
                   type="button"
                   @click="activeTab = 'defenses'"
                   :class="activeTab === 'defenses' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-calendar text-lg transition-transform group-hover:scale-110"></i>
                        <span>Defense Scheduling</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['defenses'] ?? 0" label="scheduled defenses" />
                        <span x-show="activeTab === 'defenses'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </button>

                <button 
                    type="button"
                    @click="activeTab = 'repository'"
                    :class="activeTab === 'repository' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-folder text-lg transition-transform group-hover:scale-110"></i>
                        <span>Research Repository</span>
                    </div>
                    <span x-show="activeTab === 'repository'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </button>

                <button 
                    type="button"
                    @click="activeTab = 'forms'"
                    :class="activeTab === 'forms' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-text text-lg transition-transform group-hover:scale-110"></i>
                        <span>Forms Management</span>
                    </div>
                    <span x-show="activeTab === 'forms'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </button>

                @can('reports.view')
                <a href="{{ route('admin.reports.index') }}"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold group"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chart-line-up text-lg transition-transform group-hover:scale-110"></i>
                        <span>Reports & Analytics</span>
                    </div>
                </a>
                @endcan

                @can('audit-logs.view')
                <button 
                    type="button"
                    @click="activeTab = 'audit'"
                    :class="activeTab === 'audit' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-list-bullets text-lg transition-transform group-hover:scale-110"></i>
                        <span>Audit Logs</span>
                    </div>
                    <span x-show="activeTab === 'audit'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </button>
                @endcan
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="flex-shrink-0 px-5 pb-5 mt-auto">
            <!-- Decorative Separator -->
            <div class="relative flex items-center justify-center my-3">
                <div class="w-full h-px bg-gradient-to-r from-transparent via-white/15 to-transparent"></div>
            </div>

            <div class="space-y-1">
                <button
                    type="button"
                    @click="switchTab('notifications')"
                    :class="activeTab === 'notifications' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md' : 'text-white/85 hover:text-white hover:bg-white/15 font-semibold'"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-bell text-lg"></i>
                        <span>Notifications</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['notifications'] ?? 0" label="unread notifications" />
                        <span x-show="activeTab === 'notifications'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </button>

                <button 
                    type="button"
                    @click="activeTab = 'settings'"
                    :class="activeTab === 'settings' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md' : 'text-white/85 hover:text-white hover:bg-white/15 font-semibold'"
                    class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-gear text-lg"></i>
                        <span>System Settings</span>
                    </div>
                    <span x-show="activeTab === 'settings'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </button>
                
                <!-- Real Logout Form -->
                <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden" data-confirm-logout>
                    @csrf
                </form>
                <a href="#" 
                   onclick="event.preventDefault(); document.getElementById('logout-form').requestSubmit();"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-white/70 hover:text-rose-200 hover:bg-rose-500/20 border border-transparent hover:border-rose-500/30 font-semibold text-[13px] transition-all duration-200 cursor-pointer">
                    <i class="ph ph-sign-out text-lg"></i>
                    <span>Logout</span>
                </a>
            </div>
            <div class="flex items-center justify-center gap-2 text-[9px] text-white/40 text-center font-medium mt-4">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400/60"></span>
                <span>NDMU-RMAS © {{ now()->year }} · v1.0</span>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div data-portal-content class="flex-1 pl-72 flex flex-col min-h-screen">
        <!-- Top Header Navbar -->
        <header data-portal-header class="h-20 bg-white/85 backdrop-blur-md border-b border-slate-200/80 px-8 flex items-center justify-between sticky top-0 z-40 flex-shrink-0 transition-all">
            <!-- Search bar -->
            <div data-portal-primary-search class="relative w-96">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                    <i class="ph ph-magnifying-glass text-base"></i>
                </span>
                <input
                    type="text"
                    placeholder="Search research, documents, or tasks..."
                    class="w-full pl-10 pr-14 py-2.5 bg-slate-100/80 border border-slate-200/60 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-[#0e5c3a] focus:ring-2 focus:ring-[#0e5c3a]/10 transition-all duration-200 shadow-2xs"
                >
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <kbd class="px-1.5 py-0.5 text-[10px] font-bold text-slate-400 bg-white border border-slate-200 rounded-md shadow-2xs">Ctrl K</kbd>
                </div>
            </div>

            <!-- Profile Info and Notification Icon -->
            <div class="flex items-center gap-4">
                <x-workspace-switcher current="admin" />
                <x-notification-dropdown />

                <!-- User profile badge -->
                <div class="flex items-center gap-3 pl-2 border-l border-slate-200">
                    <div class="w-8 h-8 rounded-full bg-[#0e5c3a] text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                        {{ mb_strtoupper(mb_substr($administrator?->name ?? 'A', 0, 1)) }}
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="font-bold text-xs text-slate-800">{{ $administrator?->name ?? 'Administrator' }}</span>
                        <span class="text-[9px] font-bold text-slate-400 mt-0.5">Management Portal</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dynamic Content Body -->
        <main data-portal-main class="flex-grow px-10 py-8 w-full">
            <x-portal-feature-banner class="mb-8" :sections="[
                'users' => ['eyebrow' => 'NDMU-RMAS Administration', 'title' => 'User Management', 'description' => 'Manage accounts, monitor institutional email verification, and assign reusable roles.', 'icon' => 'ph-users-three'],
                'research' => ['eyebrow' => 'NDMU-RMAS Administration', 'title' => 'Research Management', 'description' => 'Oversee research records, assignments, and approval activity.', 'icon' => 'ph-book-open'],
                'defenses' => ['eyebrow' => 'NDMU-RMAS Administration', 'title' => 'Defense Scheduling', 'description' => 'Coordinate defense requests, schedules, rooms, and panels.', 'icon' => 'ph-calendar-check'],
                'repository' => ['eyebrow' => 'NDMU-RMAS Administration', 'title' => 'Research Repository', 'description' => 'Administer secure research records and document access.', 'icon' => 'ph-folder-open'],
                'forms' => ['eyebrow' => 'NDMU-RMAS Administration', 'title' => 'Forms Management', 'description' => 'Manage official form availability, records, and workflow status.', 'icon' => 'ph-file-text'],
                'reports' => ['eyebrow' => 'NDMU-RMAS Administration', 'title' => 'Reports & Analytics', 'description' => 'Review operational metrics and research-system outcomes.', 'icon' => 'ph-chart-bar'],
                'audit' => ['eyebrow' => 'NDMU-RMAS Administration', 'title' => 'Audit Logs', 'description' => 'Review security-relevant and administrative activity.', 'icon' => 'ph-list-magnifying-glass'],
            ]" />
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
            <div x-show="activeTab === 'dashboard'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Rich Branded Command Hub & Quick Action Header -->
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#073823] via-[#0e5c3a] to-[#0a462c] p-6 md:p-8 text-white shadow-xl shadow-emerald-950/20 border border-emerald-600/30">
                    <!-- Ambient Glow & Watermark Logo -->
                    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-[#eebc3f]/15 blur-3xl"></div>
                    <div class="pointer-events-none absolute -left-12 -bottom-20 h-48 w-48 rounded-full bg-emerald-400/15 blur-2xl"></div>
                    <div class="pointer-events-none absolute right-6 top-1/2 -translate-y-1/2 opacity-[0.08]">
                        <img src="{{ asset('images/ndmu_logo.png') }}" alt="" class="h-36 md:h-44 w-auto object-contain">
                    </div>

                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/25 backdrop-blur-md border border-[#eebc3f]/30 text-[#eebc3f] font-black text-[10px] uppercase tracking-[0.16em]">
                                    <span class="w-2 h-2 rounded-full bg-[#eebc3f] animate-pulse"></span>
                                    Academic Year {{ now()->year }}-{{ now()->year + 1 }}
                                </span>
                                <span class="text-white/40 text-xs">•</span>
                                <span class="text-emerald-200 text-xs font-semibold tracking-wide">System Administration Portal</span>
                            </div>
                            <h1 class="text-2xl md:text-3xl font-black font-heading text-white tracking-tight drop-shadow-xs">
                                Welcome back, {{ auth()->user()->displayFirstName() }}!
                            </h1>
                            <p class="text-xs md:text-sm text-emerald-100/85 max-w-2xl leading-relaxed">
                                System infrastructure management, user account provisioning, role security enforcement, and real-time audit surveillance
                            </p>
                        </div>

                        <!-- Quick Action Buttons -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <button
                                type="button"
                                @click="activeTab = 'users'"
                                class="px-4.5 py-2.5 bg-gradient-to-r from-[#eebc3f] to-[#f4c542] hover:brightness-105 text-[#073823] text-xs font-black rounded-xl flex items-center gap-2 shadow-md shadow-amber-950/20 transition-all cursor-pointer"
                            >
                                <i class="ph ph-users text-base"></i>
                                <span>User Accounts</span>
                                @if ($pendingUsersCount > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-950 text-[#eebc3f]">
                                        {{ $pendingUsersCount }}
                                    </span>
                                @endif
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'roles'"
                                class="px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/15 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer"
                            >
                                <i class="ph ph-shield-check text-base text-blue-300"></i>
                                <span>Role Permissions</span>
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'audit'"
                                class="px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/15 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer"
                            >
                                <i class="ph ph-list-bullets text-base text-[#eebc3f]"></i>
                                <span>Audit Trail</span>
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'settings'"
                                class="px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/15 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer"
                            >
                                <i class="ph ph-gear text-base text-slate-300"></i>
                                <span>Settings</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modern Vibrant 4-KPI Metric Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <!-- KPI 1: Total Users -->
                    <div
                        @click="activeTab = 'users'"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 via-emerald-400 to-teal-400 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-emerald-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-users"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-[#0e5c3a] text-white shadow-md shadow-emerald-700/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 relative">
                                    <i class="ph ph-users"></i>
                                    @if ($pendingUsersCount > 0)
                                        <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-amber-500 rounded-full border-2 border-white animate-pulse"></span>
                                    @endif
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-[#0e5c3a] border border-emerald-100 group-hover:bg-[#0e5c3a] group-hover:text-white transition-all">
                                    <span>Manage</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Total Accounts</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ $totalUsersCount }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>{{ $pendingUsersCount > 0 ? $pendingUsersCount . ' Pending approval' : 'All accounts verified' }}</span>
                                    <span class="font-bold text-[#0e5c3a]">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 2: Active Research -->
                    <div
                        @click="activeTab = 'academic-years'"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-cyan-400 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-blue-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-book-open"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-700 text-white shadow-md shadow-blue-700/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                                    <i class="ph ph-book-open"></i>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100 group-hover:bg-blue-600 group-hover:text-white transition-all">
                                    <span>Studies</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Active Research</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ $activeResearchCount }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>Ongoing University Studies</span>
                                    <span class="font-bold text-blue-600">In Progress</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 3: Completed Research -->
                    <div
                        @click="activeTab = 'academic-years'"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-500 via-purple-600 to-pink-500 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-purple-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-folder-star"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-800 text-white shadow-md shadow-purple-700/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                                    <i class="ph ph-folder-star"></i>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-100 group-hover:bg-purple-600 group-hover:text-white transition-all">
                                    <span>Repository</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Completed Research</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ $completedResearchCount }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>Institutional Repository</span>
                                    <span class="font-bold text-purple-600">Archived</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4: Recent Activity -->
                    <div
                        @click="activeTab = 'audit'"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-400 via-orange-500 to-amber-500 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-amber-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-lightning"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-600 text-white shadow-md shadow-amber-600/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                                    <i class="ph ph-lightning"></i>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 group-hover:bg-amber-500 group-hover:text-white transition-all">
                                    <span>Audit</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Recent Activity</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ count($recentActivities) }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>Audit Logs Recorded</span>
                                    <span class="font-bold text-amber-600">Surveillance</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                    <!-- Lower Section: Recent Activity & User Role Distribution -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Recent Activity Card -->
                        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-5 pb-4 border-b border-gray-100">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-[#0e5c3a] to-[#0a4a2e] text-white flex items-center justify-center text-xl shadow-xs">
                                            <i class="ph ph-activity font-bold"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-base font-extrabold text-gray-900 font-heading">Recent Activity Feed</h3>
                                            <p class="text-xs text-gray-400 font-medium">Real-time system actions and account registrations</p>
                                        </div>
                                    </div>
                                    <button @click="activeTab = 'audit'" class="px-3.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/60 text-[#0e5c3a] text-xs font-extrabold rounded-xl flex items-center gap-1.5 transition-all cursor-pointer">
                                        <span>View Audit Trail</span>
                                        <i class="ph ph-arrow-right font-bold"></i>
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    @forelse($recentActivities as $activity)
                                        @php
                                            $actorName = $activity['actor'] ?? 'System';
                                            $actorEmail = $activity['email'] ?? '';
                                            $eventLabel = strtoupper(str_replace(['.', '_', '-'], ' ', $activity['event'] ?? 'system.event'));
                                            $initial = strtoupper(substr($actorName, 0, 1));
                                            $details = $activity['text'];
                                        @endphp
                                        <div class="p-4 bg-slate-50/80 hover:bg-white border-l-4 border-l-[#0e5c3a] border-y border-r border-slate-200/80 hover:border-emerald-300 rounded-2xl transition-all duration-200 flex items-center justify-between gap-4 shadow-2xs hover:shadow-md group">
                                            <div class="flex items-center gap-3.5 min-w-0">
                                                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#0e5c3a] to-[#0a4a2e] text-white font-black flex items-center justify-center text-xs shrink-0 shadow-2xs group-hover:scale-105 transition-transform">
                                                    {{ $initial }}
                                                </div>
                                                <div class="space-y-0.5 min-w-0">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="px-2 py-0.5 bg-emerald-100/80 border border-emerald-200 text-emerald-800 text-[10px] font-black rounded-md tracking-wider">
                                                            {{ $eventLabel }}
                                                        </span>
                                                        <span class="text-xs font-bold text-slate-800 truncate">{{ $actorName }}</span>
                                                        @if($actorEmail)
                                                            <span class="text-[11px] text-slate-400 font-mono truncate">({{ $actorEmail }})</span>
                                                        @endif
                                                    </div>
                                                    <p class="text-[11px] text-slate-500 font-medium truncate">{{ $details }}</p>
                                                </div>
                                            </div>
                                            <span class="px-3 py-1 bg-white border border-slate-200 text-slate-500 rounded-xl text-[10px] font-bold shrink-0 shadow-2xs flex items-center gap-1.5">
                                                <i class="ph ph-clock text-xs text-slate-400"></i>
                                                <span>{{ $activity['time'] }}</span>
                                            </span>
                                        </div>
                                    @empty
                                        <div class="p-10 bg-slate-50/60 border border-slate-200/80 rounded-2xl text-center text-xs font-bold text-slate-400">
                                            No recent activity logged yet.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <!-- System Statistics Card (Enterprise Role Distribution) -->
                        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-5 pb-4 border-b border-gray-100">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex items-center justify-center text-xl shadow-xs">
                                            <i class="ph ph-chart-pie-slice font-bold"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-base font-extrabold text-gray-900 font-heading">User Role Distribution</h3>
                                            <p class="text-xs text-gray-400 font-medium">Breakdown of account roles across NDMU</p>
                                        </div>
                                    </div>
                                    <span class="px-3 py-1.5 bg-blue-50 text-blue-700 border border-blue-100 rounded-xl text-xs font-bold">
                                        {{ $totalUsersCount }} Accounts
                                    </span>
                                </div>

                                <div class="space-y-4">
                                    <!-- Student Researchers -->
                                    <div class="p-4 bg-gradient-to-r from-emerald-50/70 to-teal-50/70 rounded-2xl border border-emerald-200/80 space-y-2.5 shadow-2xs">
                                        <div class="flex items-center justify-between text-xs font-black text-slate-800">
                                            <span class="flex items-center gap-2">
                                                <span class="w-7 h-7 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-xs shadow-xs font-bold">
                                                    <i class="ph ph-student"></i>
                                                </span>
                                                <span class="font-extrabold">Student Researchers</span>
                                            </span>
                                            <span class="text-slate-900 font-black text-sm">{{ $studentCount }} <span class="text-[10px] text-emerald-800 font-extrabold bg-white px-2.5 py-0.5 rounded-md ml-1 border border-emerald-200 shadow-2xs">{{ number_format($totalUsersCount > 0 ? ($studentCount / $totalUsersCount) * 100 : 0, 1) }}%</span></span>
                                        </div>
                                        <div class="w-full h-3 bg-white/80 rounded-full overflow-hidden p-0.5 border border-emerald-200/60">
                                            @php
                                                $studentPercent = $totalUsersCount > 0 ? ($studentCount / $totalUsersCount) * 100 : 0;
                                            @endphp
                                            <div class="h-full bg-gradient-to-r from-emerald-500 to-[#0e5c3a] rounded-full transition-all duration-500 shadow-xs" x-init="$el.style.width = @js(max(0, min(100, $studentPercent))) + '%'"></div>
                                        </div>
                                    </div>

                                    <!-- Advisers -->
                                    <div class="p-4 bg-gradient-to-r from-blue-50/70 to-indigo-50/70 rounded-2xl border border-blue-200/80 space-y-2.5 shadow-2xs">
                                        <div class="flex items-center justify-between text-xs font-black text-slate-800">
                                            <span class="flex items-center gap-2">
                                                <span class="w-7 h-7 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xs shadow-xs font-bold">
                                                    <i class="ph ph-user-gear"></i>
                                                </span>
                                                <span class="font-extrabold">Research Advisers</span>
                                            </span>
                                            <span class="text-slate-900 font-black text-sm">{{ $adviserCount }} <span class="text-[10px] text-blue-800 font-extrabold bg-white px-2.5 py-0.5 rounded-md ml-1 border border-blue-200 shadow-2xs">{{ number_format($totalUsersCount > 0 ? ($adviserCount / $totalUsersCount) * 100 : 0, 1) }}%</span></span>
                                        </div>
                                        <div class="w-full h-3 bg-white/80 rounded-full overflow-hidden p-0.5 border border-blue-200/60">
                                            @php
                                                $adviserPercent = $totalUsersCount > 0 ? ($adviserCount / $totalUsersCount) * 100 : 0;
                                            @endphp
                                            <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full transition-all duration-500 shadow-xs" x-init="$el.style.width = @js(max(0, min(100, $adviserPercent))) + '%'"></div>
                                        </div>
                                    </div>

                                    <!-- Panelists -->
                                    <div class="p-4 bg-gradient-to-r from-purple-50/70 to-pink-50/70 rounded-2xl border border-purple-200/80 space-y-2.5 shadow-2xs">
                                        <div class="flex items-center justify-between text-xs font-black text-slate-800">
                                            <span class="flex items-center gap-2">
                                                <span class="w-7 h-7 rounded-xl bg-purple-600 text-white flex items-center justify-center text-xs shadow-xs font-bold">
                                                    <i class="ph ph-gavel"></i>
                                                </span>
                                                <span class="font-extrabold">Panelists</span>
                                            </span>
                                            <span class="text-slate-900 font-black text-sm">{{ $panelistCount }} <span class="text-[10px] text-purple-800 font-extrabold bg-white px-2.5 py-0.5 rounded-md ml-1 border border-purple-200 shadow-2xs">{{ number_format($totalUsersCount > 0 ? ($panelistCount / $totalUsersCount) * 100 : 0, 1) }}%</span></span>
                                        </div>
                                        <div class="w-full h-3 bg-white/80 rounded-full overflow-hidden p-0.5 border border-purple-200/60">
                                            @php
                                                $panelistPercent = $totalUsersCount > 0 ? ($panelistCount / $totalUsersCount) * 100 : 0;
                                            @endphp
                                            <div class="h-full bg-gradient-to-r from-purple-500 to-pink-600 rounded-full transition-all duration-500 shadow-xs" x-init="$el.style.width = @js(max(0, min(100, $panelistPercent))) + '%'"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Actions Grid Section -->
                    <section class="bg-white rounded-3xl p-8 shadow-xs border border-slate-200/80 space-y-6">
                        <div class="flex items-center justify-between gap-4 pb-4 border-b border-gray-100">
                            <div>
                                <h2 class="text-lg font-black font-heading text-gray-900 flex items-center gap-2">
                                    <i class="ph ph-warning-circle text-amber-500 text-xl"></i>
                                    <span>Pending System Actions</span>
                                </h2>
                                <p class="text-xs text-gray-500 mt-0.5">Items requiring administrative verification or user authorization</p>
                            </div>
                            <span class="px-4 py-1.5 rounded-full bg-amber-100 text-amber-900 text-xs font-black border border-amber-200">
                                {{ collect($pendingActions)->sum('count') }} Pending Items
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                            @foreach ($pendingActions as $action)
                                @php
                                    $actionStyle = match ($action['tone']) {
                                        'purple' => 'border-purple-200/80 bg-purple-50/50 hover:bg-purple-50 hover:border-purple-300 text-purple-900',
                                        'blue' => 'border-blue-200/80 bg-blue-50/50 hover:bg-blue-50 hover:border-blue-300 text-blue-900',
                                        'emerald' => 'border-emerald-200/80 bg-emerald-50/50 hover:bg-emerald-50 hover:border-emerald-300 text-emerald-900',
                                        'red' => 'border-red-200/80 bg-red-50/50 hover:bg-red-50 hover:border-red-300 text-red-900',
                                        'orange' => 'border-orange-200/80 bg-orange-50/50 hover:bg-orange-50 hover:border-orange-300 text-orange-900',
                                        default => 'border-amber-200/80 bg-amber-50/50 hover:bg-amber-50 hover:border-amber-300 text-amber-900',
                                    };
                                @endphp
                                <button
                                    type="button"
                                    data-tab="{{ $action['tab'] }}"
                                    data-subtab="{{ $action['subtab'] }}"
                                    @click="if ($el.dataset.tab) { activeTab = $el.dataset.tab; if ($el.dataset.subtab) userManagementTab = $el.dataset.subtab; }"
                                    @disabled($action['tab'] === null)
                                    class="p-5 rounded-2xl border text-left flex items-center justify-between gap-4 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 cursor-pointer disabled:cursor-default {{ $actionStyle }}"
                                >
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <span class="w-11 h-11 rounded-2xl bg-white shadow-2xs flex items-center justify-center shrink-0 border border-black/5">
                                            <i class="ph {{ $action['icon'] }} text-xl"></i>
                                        </span>
                                        <span class="text-xs font-black truncate leading-snug">{{ $action['label'] }}</span>
                                    </div>
                                    <span class="text-2xl font-black font-heading shrink-0">{{ $action['count'] }}</span>
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
            <div x-show="activeTab === 'users'" x-cloak class="min-w-0 space-y-8 animate-fade-in">
                    <!-- Section Action Header -->
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-2xs relative overflow-hidden">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#eebc3f]"></div>
                        <div class="flex min-w-0 items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#073823] to-[#0e5c3a] text-[#eebc3f] flex items-center justify-center text-2xl shadow-xs shrink-0">
                                <i class="ph ph-users-three"></i>
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-xl sm:text-2xl font-black font-heading text-slate-900 flex flex-wrap items-center gap-2">
                                    <span>User Accounts & Provisioning</span>
                                    <span class="rounded-full bg-emerald-50 border border-emerald-200 px-3 py-0.5 text-[10px] font-black uppercase text-[#0e5c3a]">
                                        {{ $totalUsersCount }} Total
                                    </span>
                                </h2>
                                <p class="text-xs text-slate-500 font-medium mt-0.5">Oversee registered accounts, approve student registrations, edit user details, and provision staff accounts.</p>
                            </div>
                        </div>
                        <div class="flex w-full items-center gap-3 md:w-auto">
                            <button
                                type="button"
                                @click="userManagementTab = 'create-user'"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-xs font-black text-white shadow-md transition-all hover:bg-[#073823] cursor-pointer sm:w-auto"
                            >
                                <i class="ph ph-user-plus text-base"></i>
                                <span>Add Staff Account</span>
                            </button>
                        </div>
                    </div>

                    <!-- Row of 4 statistics cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                        <!-- Card 1: Total Users -->
                        <div class="group bg-white rounded-3xl p-6 shadow-2xs hover:shadow-lg border border-slate-200/80 hover:border-emerald-300 transition-all duration-300 relative overflow-hidden flex items-center justify-between">
                            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-400 opacity-70 group-hover:opacity-100 transition-opacity"></div>
                            <div class="space-y-1">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Total Users</span>
                                <span class="text-3xl font-black text-slate-900 font-heading tracking-tight">{{ $totalUsersCount }}</span>
                                <span class="text-[10px] font-bold text-emerald-700 block">Registered in system</span>
                            </div>
                            <div class="w-12 h-12 bg-emerald-50 text-[#0e5c3a] rounded-2xl flex items-center justify-center text-2xl shadow-2xs border border-emerald-100 group-hover:scale-110 transition-transform">
                                <i class="ph ph-users"></i>
                            </div>
                        </div>

                        <!-- Card 2: Awaiting Email Verification -->
                        <div class="group bg-white rounded-3xl p-6 shadow-2xs hover:shadow-lg border border-slate-200/80 hover:border-amber-300 transition-all duration-300 relative overflow-hidden flex items-center justify-between">
                            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 to-yellow-400 opacity-70 group-hover:opacity-100 transition-opacity"></div>
                            <div class="space-y-1">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Awaiting Verification</span>
                                <span class="text-3xl font-black text-slate-900 font-heading tracking-tight">{{ $pendingApprovalCount }}</span>
                                <span class="text-[10px] font-bold text-amber-600 block">Awaiting verification</span>
                            </div>
                            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-2xl shadow-2xs border border-amber-100 group-hover:scale-110 transition-transform" :class="{{ $pendingApprovalCount }} > 0 ? 'animate-pulse' : ''">
                                <i class="ph ph-clock"></i>
                            </div>
                        </div>

                        <!-- Card 3: Active Accounts -->
                        <div class="group bg-white rounded-3xl p-6 shadow-2xs hover:shadow-lg border border-slate-200/80 hover:border-emerald-300 transition-all duration-300 relative overflow-hidden flex items-center justify-between">
                            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#0e5c3a] opacity-70 group-hover:opacity-100 transition-opacity"></div>
                            <div class="space-y-1">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Active Accounts</span>
                                <span class="text-3xl font-black text-slate-900 font-heading tracking-tight">{{ $activeAccountsCount }}</span>
                                <span class="text-[10px] font-bold text-[#0e5c3a] block">Verified & authorized</span>
                            </div>
                            <div class="w-12 h-12 bg-emerald-50 text-[#0e5c3a] rounded-2xl flex items-center justify-center text-2xl shadow-2xs border border-emerald-100 group-hover:scale-110 transition-transform">
                                <i class="ph ph-check-circle"></i>
                            </div>
                        </div>

                        <!-- Card 4: Without Roles -->
                        <div class="group bg-white rounded-3xl p-6 shadow-2xs hover:shadow-lg border border-slate-200/80 hover:border-blue-300 transition-all duration-300 relative overflow-hidden flex items-center justify-between">
                            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-400 opacity-70 group-hover:opacity-100 transition-opacity"></div>
                            <div class="space-y-1">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Without Roles</span>
                                <span class="text-3xl font-black text-slate-900 font-heading tracking-tight">{{ $withoutRolesCount }}</span>
                                <span class="text-[10px] font-bold text-blue-600 block">Awaiting role assignment</span>
                            </div>
                            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-2xl shadow-2xs border border-blue-100 group-hover:scale-110 transition-transform">
                                <i class="ph ph-shield-warning"></i>
                            </div>
                        </div>
                    </div>

                        <!-- Inner Navigation Tabs Container -->
                        <div class="min-w-0 bg-white rounded-3xl shadow-2xs border border-slate-200/80 overflow-hidden relative">
                            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#eebc3f]"></div>
                            <div class="flex flex-nowrap overflow-x-auto border-b border-slate-100 px-4 sm:px-8 pt-6 bg-white gap-5 sm:gap-6" aria-label="User management sections">
                                <!-- All Users Tab Button -->
                                <button 
                                    type="button"
                                    @click="userManagementTab = 'all-users'"
                                    :class="userManagementTab === 'all-users' ? 'border-[#0e5c3a] text-[#0e5c3a]' : 'border-transparent text-slate-500 hover:text-slate-900'"
                                    class="shrink-0 pb-4 border-b-2 text-xs sm:text-sm font-black flex items-center gap-2 transition-all duration-200 focus:outline-none -mb-px cursor-pointer">
                                    <i class="ph ph-users text-base"></i>
                                    <span>All Users</span>
                                    <span
                                        :class="userManagementTab === 'all-users' ? 'bg-[#0e5c3a] text-white' : 'bg-slate-100 text-slate-600'"
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-black transition-all duration-200"
                                    >
                                        {{ $totalUsersCount }}
                                    </span>
                                </button>
    
                                <!-- Pending Students Tab Button -->
                                <button 
                                    type="button"
                                    @click="userManagementTab = 'pending-students'"
                                    :class="userManagementTab === 'pending-students' ? 'border-[#0e5c3a] text-[#0e5c3a]' : 'border-transparent text-slate-500 hover:text-slate-900'"
                                    class="shrink-0 pb-4 border-b-2 text-xs sm:text-sm font-black flex items-center gap-2 transition-all duration-200 focus:outline-none -mb-px cursor-pointer">
                                    <i class="ph ph-clock text-base"></i>
                                    <span>Awaiting Verification</span>
                                    <span
                                        :class="userManagementTab === 'pending-students' ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-black transition-all duration-200"
                                    >
                                        {{ $pendingApprovalCount }}
                                    </span>
                                </button>
    
                                <!-- Create User Tab Button -->
                                <button 
                                    type="button"
                                    @click="userManagementTab = 'create-user'"
                                    :class="userManagementTab === 'create-user' ? 'border-[#0e5c3a] text-[#0e5c3a]' : 'border-transparent text-slate-500 hover:text-slate-900'"
                                    class="shrink-0 pb-4 border-b-2 text-xs sm:text-sm font-black flex items-center gap-2 transition-all duration-200 focus:outline-none -mb-px cursor-pointer">
                                    <i class="ph ph-user-plus text-base"></i>
                                    <span>Create User</span>
                                </button>
                            </div>

                        <!-- SUB-TAB CONTENT PANEL -->
                        <div class="min-w-0 p-4 sm:p-6">
                            <!-- All Users Panel -->
                            <div x-show="userManagementTab === 'all-users'" x-cloak class="min-w-0 space-y-6">

                                    <!-- Department Overview Stat Cards -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3.5">
                                        @foreach ($departmentStats as $dKey => $dStat)
                                            @php
                                                $isSelected = ($selectedDepartment === $dKey);
                                                $cardBg = match($dKey) {
                                                    'CSD' => 'from-emerald-900 to-teal-950 border-emerald-500/40 text-white',
                                                    'EECE' => 'from-blue-900 to-indigo-950 border-blue-500/40 text-white',
                                                    'CED' => 'from-amber-900 to-orange-950 border-amber-500/40 text-white',
                                                    'AD' => 'from-purple-900 to-fuchsia-950 border-purple-500/40 text-white',
                                                    default => 'from-slate-800 to-slate-900 border-slate-600/40 text-white',
                                                };
                                                $icon = match($dKey) {
                                                    'CSD' => 'ph-laptop',
                                                    'EECE' => 'ph-cpu',
                                                    'CED' => 'ph-hard-hat',
                                                    'AD' => 'ph-compass-tool',
                                                    default => 'ph-buildings',
                                                };
                                            @endphp
                                            <button 
                                                type="button"
                                                wire:click="setDepartmentFilter('{{ $isSelected ? 'all' : $dKey }}')"
                                                class="text-left rounded-2xl p-4 bg-gradient-to-br {{ $cardBg }} border transition-all duration-300 hover:scale-[1.02] hover:shadow-lg cursor-pointer relative overflow-hidden group {{ $isSelected ? 'ring-3 ring-[#eebc3f] shadow-xl' : 'opacity-90 hover:opacity-100' }}"
                                            >
                                                <div class="flex items-center justify-between gap-2 mb-2">
                                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-white/15 text-[10px] font-black tracking-wider uppercase backdrop-blur-sm">
                                                        <i class="ph {{ $icon }} text-xs text-[#eebc3f]"></i>
                                                        {{ $dStat['code'] }}
                                                    </span>
                                                    <span class="text-base font-black text-[#eebc3f]">
                                                        {{ $dStat['total'] }} <span class="text-[10px] font-normal text-white/70">users</span>
                                                    </span>
                                                </div>
                                                <div class="text-xs font-black truncate text-white leading-tight mb-1" title="{{ $dStat['name'] }}">
                                                    {{ $dStat['name'] }}
                                                </div>
                                                <div class="text-[10px] text-white/70 truncate flex items-center gap-1 mb-1.5" title="Coordinator: {{ $dStat['coordinator'] }}">
                                                    <i class="ph ph-user-circle-gear text-[#eebc3f]"></i>
                                                    <span class="truncate">{{ $dStat['coordinator'] }}</span>
                                                </div>
                                                <div class="flex items-center gap-2 pt-1.5 border-t border-white/10 text-[9px] font-semibold text-white/80">
                                                    <span>{{ $dStat['faculty_count'] }} Faculty</span>
                                                    <span>•</span>
                                                    <span>{{ $dStat['student_count'] }} Students</span>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>

                                    <!-- Department Filter Tabs -->
                                    <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3 pt-2">
                                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 mr-1 flex items-center gap-1">
                                            <i class="ph ph-funnel text-xs"></i> Dept:
                                        </span>
                                        <button
                                            type="button"
                                            wire:click="setDepartmentFilter('all')"
                                            class="px-3 py-1.5 rounded-xl text-xs font-black transition-all cursor-pointer {{ $selectedDepartment === 'all' ? 'bg-[#0e5c3a] text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                                        >
                                            All CEAC ({{ $totalUsersCount }})
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="setDepartmentFilter('CSD')"
                                            class="px-3 py-1.5 rounded-xl text-xs font-black transition-all cursor-pointer {{ $selectedDepartment === 'CSD' ? 'bg-emerald-800 text-white shadow-sm' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}"
                                        >
                                            💻 Computer Studies (CSD)
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="setDepartmentFilter('EECE')"
                                            class="px-3 py-1.5 rounded-xl text-xs font-black transition-all cursor-pointer {{ $selectedDepartment === 'EECE' ? 'bg-blue-800 text-white shadow-sm' : 'bg-blue-50 text-blue-800 hover:bg-blue-100' }}"
                                        >
                                            ⚡ EECE
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="setDepartmentFilter('CED')"
                                            class="px-3 py-1.5 rounded-xl text-xs font-black transition-all cursor-pointer {{ $selectedDepartment === 'CED' ? 'bg-amber-800 text-white shadow-sm' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' }}"
                                        >
                                            🏗️ Civil Engineering
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="setDepartmentFilter('AD')"
                                            class="px-3 py-1.5 rounded-xl text-xs font-black transition-all cursor-pointer {{ $selectedDepartment === 'AD' ? 'bg-purple-800 text-white shadow-sm' : 'bg-purple-50 text-purple-800 hover:bg-purple-100' }}"
                                        >
                                            🏛️ Architecture
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="setDepartmentFilter('institutional')"
                                            class="px-3 py-1.5 rounded-xl text-xs font-black transition-all cursor-pointer {{ $selectedDepartment === 'institutional' ? 'bg-slate-800 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                                        >
                                            🎓 College Admin
                                        </button>
                                    </div>

                                    <!-- User Type Segment Pills & Search Bar -->
                                    <div class="flex flex-col lg:flex-row gap-4 items-center justify-between">
                                        <!-- User Type Pills -->
                                        <div class="flex flex-wrap items-center gap-1.5 w-full lg:w-auto">
                                            <button
                                                type="button"
                                                wire:click="setUserTypeFilter('all')"
                                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer {{ $selectedUserType === 'all' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                                            >
                                                All Types
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="setUserTypeFilter('faculty')"
                                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer {{ $selectedUserType === 'faculty' ? 'bg-teal-700 text-white' : 'bg-teal-50 text-teal-700 hover:bg-teal-100' }}"
                                            >
                                                Faculty & Advisers
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="setUserTypeFilter('student')"
                                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer {{ $selectedUserType === 'student' ? 'bg-indigo-700 text-white' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100' }}"
                                            >
                                                Students
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="setUserTypeFilter('staff')"
                                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer {{ $selectedUserType === 'staff' ? 'bg-emerald-700 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}"
                                            >
                                                Admin & Staff
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="setUserTypeFilter('pending')"
                                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer {{ $selectedUserType === 'pending' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}"
                                            >
                                                Awaiting Email Verification ({{ $pendingApprovalCount }})
                                            </button>
                                        </div>

                                        <!-- Search & Role Dropdown -->
                                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto lg:justify-end">
                                            <!-- Search input -->
                                            <div class="relative w-full sm:w-64">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                                                    <i class="ph ph-magnifying-glass text-base"></i>
                                                </span>
                                                <input 
                                                    wire:model.live.debounce.300ms="searchQuery"
                                                    type="text" 
                                                    placeholder="Search name, email, ID..." 
                                                    class="w-full pl-9 pr-3 py-2 bg-white border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-[#0e5c3a] focus:ring-2 focus:ring-[#0e5c3a]/10 transition-all"
                                                >
                                            </div>

                                            <!-- Role Filter -->
                                            <select 
                                                wire:model.live="selectedRole"
                                                class="w-full sm:w-auto px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-[#0e5c3a] focus:ring-2 focus:ring-[#0e5c3a]/10 transition-all pr-8"
                                            >
                                                <option value="">All Roles</option>
                                                <option value="__without_roles__">Without Roles</option>
                                                @foreach ($rolesList as $filterRole)
                                                    <option value="{{ $filterRole['name'] }}">{{ $filterRole['label'] }}</option>
                                                @endforeach
                                            </select>

                                            <!-- Refresh Button -->
                                            <button 
                                                type="button"
                                                wire:click="refreshUserManagement"
                                                wire:loading.attr="disabled"
                                                wire:target="refreshUserManagement"
                                                class="p-2 bg-white hover:bg-gray-50 border border-gray-200 text-gray-600 hover:text-gray-800 text-xs font-semibold rounded-xl flex items-center gap-1 shadow-xs transition-all cursor-pointer"
                                                title="Refresh Users"
                                            >
                                                <i class="ph ph-arrows-counter-clockwise"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Table Container -->
                                    <div class="space-y-2">
                                        <p class="text-[11px] font-semibold text-slate-500 lg:hidden">
                                            Swipe sideways inside the table to see every field and account action.
                                        </p>
                                    <div id="user-management-table" data-responsive-table-container tabindex="0" aria-label="User accounts table. Scroll horizontally for all columns." class="responsive-data-table max-w-full overflow-x-auto rounded-2xl border border-gray-100 shadow-xs">
                                        <table data-responsive-table class="min-w-[1120px] w-full border-collapse text-left text-sm text-gray-500">
                                            <thead class="bg-gradient-to-r from-[#0e5c3a] to-[#0a4a2e] text-white text-[10px] font-black uppercase tracking-wider shadow-xs">
                                                <tr>
                                                    <th scope="col" class="px-6 py-4">Name & ID</th>
                                                    <th scope="col" class="px-6 py-4">Email</th>
                                                    <th scope="col" class="px-6 py-4">Type</th>
                                                    <th scope="col" class="px-6 py-4">Department & Program</th>
                                                    <th scope="col" class="px-6 py-4">Role(s)</th>
                                                    <th scope="col" class="px-6 py-4">Status</th>
                                                    <th scope="col" class="px-6 py-4">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 bg-white">
                                                @forelse($usersList as $user)
                                                    @php
                                                        $status = $user->status instanceof \App\Enums\AccountStatus ? $user->status->value : $user->status;
                                                        $statusBadgeClass = match($status) {
                                                            'active' => 'bg-emerald-100 text-emerald-800',
                                                            'pending' => 'bg-amber-100 text-amber-800',
                                                            'rejected' => 'bg-red-100 text-red-800',
                                                            default => 'bg-gray-100 text-gray-800',
                                                        };
                                                        $deptCode = match(true) {
                                                            str_contains((string)$user->department, 'Computer') || in_array($user->program, ['BSCS', 'BSIT', 'BLIS']) => 'CSD',
                                                            str_contains((string)$user->department, 'Electrical') || in_array($user->program, ['BSEE', 'BSECE', 'BSCPE', 'BSCpE']) => 'EECE',
                                                            str_contains((string)$user->department, 'Civil') || in_array($user->program, ['BSCE']) => 'CED',
                                                            str_contains((string)$user->department, 'Architecture') || in_array($user->program, ['BSARCH', 'BSArch']) => 'AD',
                                                            default => 'ADMIN',
                                                        };
                                                        $deptBadgeClass = match($deptCode) {
                                                            'CSD' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                                            'EECE' => 'bg-blue-50 text-blue-800 border-blue-200',
                                                            'CED' => 'bg-amber-50 text-amber-800 border-amber-200',
                                                            'AD' => 'bg-purple-50 text-purple-800 border-purple-200',
                                                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                                                        };
                                                    @endphp
                                                    <tr class="group transition-all duration-200 hover:bg-emerald-50/60 hover:shadow-[inset_4px_0_0_#0e5c3a]">
                                                        <!-- Name & ID -->
                                                        <td class="px-6 py-4">
                                                            <div class="flex items-center gap-3">
                                                                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#0e5c3a]/15 to-[#eebc3f]/20 text-[#0e5c3a] font-black text-sm flex items-center justify-center shadow-2xs">
                                                                    {{ substr($user->name, 0, 1) }}
                                                                </div>
                                                                <div>
                                                                    <div class="font-black text-slate-900 leading-tight">{{ $user->name }}</div>
                                                                    <div class="text-[11px] text-slate-400 font-semibold mt-0.5">
                                                                        @if ($user->student_id)
                                                                            <span class="font-mono text-slate-600">ID: {{ $user->student_id }}</span>
                                                                        @elseif ($user->employee_id)
                                                                            <span class="font-mono text-slate-600">Emp: {{ $user->employee_id }}</span>
                                                                        @else
                                                                            <span class="text-slate-400">#{{ $user->id }}</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        <!-- Email -->
                                                        <td class="px-6 py-4 text-xs font-semibold text-slate-600 font-mono">{{ $user->email }}</td>

                                                        <!-- Type -->
                                                        <td class="px-6 py-4">
                                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-700">
                                                                {{ $user->user_type->label() }}
                                                            </span>
                                                        </td>

                                                        <!-- Department & Program -->
                                                        <td class="px-6 py-4">
                                                            <div class="space-y-1">
                                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-black border {{ $deptBadgeClass }}">
                                                                    {{ $deptCode }}
                                                                </span>
                                                                <div class="text-xs font-semibold text-slate-700 truncate max-w-[170px]" title="{{ $user->department ?? $user->program }}">
                                                                    {{ $user->program ?: ($user->department ?: 'Institutional') }}
                                                                </div>
                                                            </div>
                                                        </td>

                                                        <!-- Role Badges -->
                                                        <td class="px-6 py-4">
                                                            <div class="flex flex-wrap gap-1 max-w-[220px]">
                                                                @forelse ($user->roles as $assignedRole)
                                                                    @php
                                                                        $isCoordinator = ($assignedRole->name === 'program-coordinator');
                                                                        $roleBadgeClass = match($assignedRole->name) {
                                                                            'program-coordinator' => 'bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-2xs font-black',
                                                                            'system-administrator' => 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold',
                                                                            'college-dean' => 'bg-indigo-50 text-indigo-700 border-indigo-200 font-bold',
                                                                            'research-facilitator' => 'bg-sky-50 text-sky-700 border-sky-200 font-bold',
                                                                            'research-adviser', 'thesis-adviser' => 'bg-teal-50 text-teal-700 border-teal-200 font-bold',
                                                                            'panelist' => 'bg-purple-50 text-purple-700 border-purple-200 font-bold',
                                                                            default => 'bg-gray-50 text-gray-700 border-gray-200 font-medium',
                                                                        };
                                                                        $roleLabel = $assignedRole->display_name ?: str($assignedRole->name)->replace('-', ' ')->title();
                                                                    @endphp
                                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] border {{ $roleBadgeClass }}">
                                                                        @if ($isCoordinator)
                                                                            <i class="ph ph-star-fill text-[10px] text-white"></i>
                                                                        @endif
                                                                        <span>{{ $roleLabel }}</span>
                                                                    </span>
                                                                @empty
                                                                    <span class="text-xs text-gray-400">No role</span>
                                                                @endforelse
                                                            </div>
                                                        </td>

                                                        <!-- Status Badge -->
                                                        <td class="px-6 py-4">
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $statusBadgeClass }}">
                                                                {{ ucfirst($status) }}
                                                            </span>
                                                        </td>

                                                        <!-- Actions -->
                                                        <td class="min-w-[240px] whitespace-nowrap px-6 py-4">
                                                            @if (auth()->id() === $user->id)
                                                                <span class="text-xs font-bold text-gray-400">Current account</span>
                                                            @else
                                                                <div class="flex flex-nowrap items-center gap-1.5">
                                                                    <!-- Assign Button -->
                                                                    <button 
                                                                        type="button" 
                                                                        wire:click="openRoleAssignment({{ $user->id }}, 'assign')" 
                                                                        class="shrink-0 px-2.5 py-1.5 rounded-xl border border-blue-200 bg-blue-50 text-xs font-bold text-blue-700 hover:bg-blue-100 transition-colors cursor-pointer flex items-center gap-1"
                                                                        title="Assign new roles"
                                                                    >
                                                                        <i class="ph ph-plus-circle text-xs"></i> Assign
                                                                    </button>

                                                                    <!-- Edit Button -->
                                                                    <button 
                                                                        type="button" 
                                                                        wire:click="openRoleAssignment({{ $user->id }}, 'edit')" 
                                                                        class="shrink-0 px-2.5 py-1.5 rounded-xl border border-indigo-200 bg-indigo-50 text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition-colors cursor-pointer flex items-center gap-1"
                                                                        title="Edit or unselect roles"
                                                                    >
                                                                        <i class="ph ph-pencil-simple text-xs"></i> Edit
                                                                    </button>

                                                                    <!-- Disable / Enable Button -->
                                                                    @if ($status === 'active')
                                                                        <button
                                                                            type="button"
                                                                            wire:click="suspendUser({{ $user->id }})"
                                                                            wire:confirm="Disable this account? The user will no longer be able to sign in. Active adviser and panel duties will be transferred automatically to eligible faculty; the action will be cancelled if no safe replacement is available."
                                                                            wire:loading.attr="disabled"
                                                                            wire:target="suspendUser({{ $user->id }})"
                                                                            class="shrink-0 px-2.5 py-1.5 rounded-xl border border-amber-200 bg-amber-50 text-xs font-bold text-amber-700 hover:bg-amber-100 disabled:opacity-50 transition-colors cursor-pointer flex items-center gap-1"
                                                                            title="Disable account"
                                                                        >
                                                                            <i class="ph ph-prohibit text-xs"></i> Disable
                                                                        </button>
                                                                    @else
                                                                        <button
                                                                            type="button"
                                                                            wire:click="activateUser({{ $user->id }})"
                                                                            wire:confirm="Enable this account and allow the user to sign in again?"
                                                                            wire:loading.attr="disabled"
                                                                            wire:target="activateUser({{ $user->id }})"
                                                                            class="shrink-0 px-2.5 py-1.5 rounded-xl bg-[#0e5c3a] text-xs font-bold text-white hover:bg-[#0a4a2e] disabled:opacity-50 transition-colors cursor-pointer flex items-center gap-1"
                                                                            title="Enable account"
                                                                        >
                                                                            <i class="ph ph-check-circle text-xs"></i> Enable
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="7" class="px-6 py-12 text-center text-gray-400 font-light">
                                                            No users found matching your department or filter criteria.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    </div>

                                    <!-- Pagination Links -->
                                    <div class="mt-4">
                                        {{ $usersList->links('partials.pagination', ['scrollTo' => false]) }}
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

                                            <div class="flex items-center gap-2 flex-shrink-0 self-end md:self-center px-4 py-2.5 rounded-2xl border border-amber-200 bg-white text-xs font-bold text-amber-700">
                                                <i class="ph ph-envelope-simple text-base"></i>
                                                Waiting for student verification
                                            </div>
                                        </div>
                                    @empty
                                        <div class="py-16 text-center text-gray-400 font-light">
                                            <i class="ph ph-users-three text-5xl mb-3 text-gray-300 block"></i>
                                            No student registrations are awaiting email verification.
                                        </div>
                                    @endforelse
                            </div>

                            <!-- Create User Form Panel -->
                            <div x-show="userManagementTab === 'create-user'" x-cloak class="max-w-xl mx-auto py-4">
                                    <!-- Alert badge -->
                                    <div class="mb-6 p-4 bg-sky-50 border border-sky-100 text-sky-800 rounded-2xl flex gap-3 text-xs leading-relaxed">
                                        <i class="ph ph-info text-lg text-sky-600 flex-shrink-0"></i>
                                        <div>
                                            Creates a <strong>faculty account without operational access</strong>. After creation, use Assign Role to grant only the responsibilities this person currently needs.
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

                                        <!-- College Scope -->
                                        <div class="space-y-1.5">
                                            <label for="college_scope_display" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">College Scope</label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                                                    <i class="ph ph-buildings text-lg"></i>
                                                </span>
                                                <input
                                                    type="text"
                                                    id="college_scope_display"
                                                    value="{{ config('academic.college.name') }}"
                                                    readonly
                                                    aria-readonly="true"
                                                    class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm text-gray-500 font-medium focus:outline-none cursor-default"
                                                >
                                            </div>
                                        </div>

                                        <!-- Department & College Dean Option -->
                                        <div class="space-y-2 p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80">
                                            <div class="flex items-center justify-between gap-2">
                                                <label for="new_department" class="text-xs font-bold text-gray-700 uppercase tracking-wider block">
                                                    Department <span class="text-emerald-700">*</span>
                                                </label>
                                                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                                    <input 
                                                        type="checkbox" 
                                                        id="toggle_college_dean"
                                                        wire:model.live="isCollegeDean" 
                                                        class="w-4 h-4 text-[#0e5c3a] border-gray-300 rounded focus:ring-[#0e5c3a]/20 cursor-pointer"
                                                    >
                                                    <span class="text-xs font-semibold text-slate-700">College Dean (College Level)</span>
                                                </label>
                                            </div>

                                            @if ($isCollegeDean)
                                                <div class="p-3 bg-indigo-50 border border-indigo-100 rounded-xl flex items-center gap-2.5 text-xs text-indigo-900 leading-relaxed">
                                                    <i class="ph ph-seal-check text-base text-indigo-600 shrink-0"></i>
                                                    <div>
                                                        <strong>College Dean:</strong> Registered at the College level with college-wide oversight across all departments.
                                                    </div>
                                                </div>
                                            @else
                                                <div class="relative">
                                                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                                                        <i class="ph ph-briefcase text-lg"></i>
                                                    </span>
                                                    <select
                                                        id="new_department"
                                                        wire:model="department"
                                                        class="w-full pl-11 pr-10 py-3.5 bg-white border @error('department') border-red-300 focus:border-red-500 focus:ring-red-500/5 @else border-gray-200 focus:border-[#0e5c3a] focus:ring-[#0e5c3a]/5 @enderror rounded-2xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-4 transition-all duration-300 cursor-pointer appearance-none"
                                                    >
                                                        <option value="">-- Select Academic Department --</option>
                                                        @foreach ($this->departmentOptions() as $deptCode => $deptLabel)
                                                            <option value="{{ $deptCode }}">{{ $deptLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                    <span class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 pointer-events-none">
                                                        <i class="ph ph-caret-down text-base"></i>
                                                    </span>
                                                </div>
                                                <p class="text-[11px] text-gray-500">Choose the academic department this faculty member belongs to, or check College Dean if college-level.</p>
                                                @error('department') <span class="text-xs font-bold text-red-500 block mt-1">{{ $message }}</span> @enderror
                                            @endif
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

            @include('admin.assign-roles')

            @include('admin.roles-permissions')

            <!-- Legacy visual prototype retained temporarily but no longer reachable. -->
            <div x-show="activeTab === 'legacy-permissions'" x-cloak class="space-y-8 animate-fade-in">
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
                <div class="min-w-0 bg-white rounded-[2rem] shadow-sm border border-gray-100 p-4 sm:p-6 space-y-6">
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
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto md:justify-end">
                            <!-- Role Filter -->
                            <select 
                                x-model="permissionsRole"
                                class="w-full sm:w-auto px-4 py-2.5 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300 appearance-none pr-10 relative"
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
                    <div class="space-y-2">
                        <p class="text-[11px] font-semibold text-slate-500 lg:hidden">
                            Swipe sideways inside the table to reach permission controls.
                        </p>
                    <div data-responsive-table-container tabindex="0" aria-label="Staff permissions table. Scroll horizontally for all columns." class="responsive-data-table max-w-full overflow-x-auto rounded-2xl border border-gray-100">
                        <table data-responsive-table class="min-w-[900px] w-full border-collapse text-left text-sm text-gray-500">
                            <thead class="bg-gradient-to-r from-[#0e5c3a] to-[#0a4a2e] text-white text-[10px] font-black uppercase tracking-wider shadow-xs">
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
                                    <tr class="group hover:bg-gray-50/50 transition-colors duration-200">
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
                                            <p class="text-[10px] text-gray-400 font-light">Manage accounts and monitor student email verification</p>
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
                <!-- Section Action Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-2xs relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#eebc3f]"></div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#073823] to-[#0e5c3a] text-[#eebc3f] flex items-center justify-center text-2xl shadow-xs shrink-0">
                            <i class="ph ph-book-open"></i>
                        </div>
                        <div>
                            <h2 class="text-xl sm:text-2xl font-black font-heading text-slate-900 flex items-center gap-2">
                                <span>Research Lifecycle & Milestones</span>
                                <span class="rounded-full bg-emerald-50 border border-emerald-200 px-3 py-0.5 text-[10px] font-black uppercase text-[#0e5c3a]">
                                    {{ $researchLifecycle['progress'] }}% Complete
                                </span>
                            </h2>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">Track research progress through all 13 milestone deliverables and panel reviews.</p>
                        </div>
                    </div>
                </div>

                <!-- Overall Progress Card -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-2xs border border-slate-200/80 space-y-6 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="space-y-1">
                            <h3 class="text-lg font-black text-slate-900">Overall Progress</h3>
                            <p class="text-xs text-slate-500 font-medium">{{ $researchLifecycle['title'] ?? 'Institutional research studies' }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-3xl font-black text-[#0e5c3a] font-heading">{{ $researchLifecycle['progress'] }}%</span>
                            <span class="text-[10px] text-slate-400 font-black uppercase tracking-wider">Overall</span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full h-4 bg-slate-100 rounded-full overflow-hidden border border-slate-200/60 p-0.5">
                        <div class="h-full bg-gradient-to-r from-[#073823] to-[#0e5c3a] rounded-full transition-all duration-500" x-init="$el.style.width = @js($researchLifecycle['progress']) + '%'"></div>
                    </div>

                    <!-- Counts -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                        <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-100 text-center">
                            <span class="text-2xl font-black text-[#0e5c3a] font-heading block">{{ $researchLifecycle['completed'] }}</span>
                            <span class="text-[10px] text-emerald-800 font-black uppercase tracking-wider mt-1 block">Completed</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-100 text-center">
                            <span class="text-2xl font-black text-amber-600 font-heading block">{{ $researchLifecycle['in_progress'] }}</span>
                            <span class="text-[10px] text-amber-800 font-black uppercase tracking-wider mt-1 block">In Progress</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-center">
                            <span class="text-2xl font-black text-slate-500 font-heading block">{{ $researchLifecycle['pending'] }}</span>
                            <span class="text-[10px] text-slate-500 font-black uppercase tracking-wider mt-1 block">Pending</span>
                        </div>
                    </div>
                </div>

                <!-- Research Milestones Container -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-2xs border border-slate-200/80 space-y-6 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#eebc3f]"></div>
                    <h3 class="text-lg font-black text-slate-900">Research Milestones</h3>

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
                <!-- Section Action Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-2xs relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#eebc3f]"></div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#073823] to-[#0e5c3a] text-[#eebc3f] flex items-center justify-center text-2xl shadow-xs shrink-0">
                            <i class="ph ph-calendar-check"></i>
                        </div>
                        <div>
                            <h2 class="text-xl sm:text-2xl font-black font-heading text-slate-900 flex items-center gap-2">
                                <span>Defense Scheduling & Management</span>
                                <span class="rounded-full bg-emerald-50 border border-emerald-200 px-3 py-0.5 text-[10px] font-black uppercase text-[#0e5c3a]" x-text="defensesList.length + ' Scheduled'">
                                </span>
                            </h2>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">Coordinate defense requests, time slots, venues, and assigned faculty evaluation panels.</p>
                        </div>
                    </div>
                    <button 
                        type="button" 
                        @click="
                            formDefense = { id: null, type: 'Proposal Defense', title: '', student: '', date: '', time: '', duration: '2 hours', venue: '', adviser: '', panelists: [], status: 'Pending', notes: '', generateNotice: true };
                            showScheduleModal = true;
                        "
                        class="px-5 py-2.5 bg-[#0e5c3a] hover:bg-[#073823] text-white text-xs font-black rounded-xl flex items-center gap-2 shadow-md transition-all cursor-pointer"
                    >
                        <i class="ph ph-plus-circle text-base"></i> Schedule Defense
                    </button>
                </div>

                <!-- Row of 4 statistics cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                    <!-- Card 1: Total Scheduled -->
                    <div class="group bg-white rounded-3xl p-6 shadow-2xs hover:shadow-lg border border-slate-200/80 hover:border-emerald-300 transition-all duration-300 relative overflow-hidden flex items-center justify-between">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-400 opacity-70 group-hover:opacity-100 transition-opacity"></div>
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Total Scheduled</span>
                            <span class="text-3xl font-black text-slate-900 font-heading tracking-tight" x-text="defensesList.length">3</span>
                            <span class="text-[10px] font-bold text-emerald-700 block">Defense presentations</span>
                        </div>
                        <div class="w-12 h-12 bg-emerald-50 text-[#0e5c3a] rounded-2xl flex items-center justify-center text-2xl shadow-2xs border border-emerald-100 group-hover:scale-110 transition-transform">
                            <i class="ph ph-calendar"></i>
                        </div>
                    </div>

                    <!-- Card 2: This Week -->
                    <div class="group bg-white rounded-3xl p-6 shadow-2xs hover:shadow-lg border border-slate-200/80 hover:border-blue-300 transition-all duration-300 relative overflow-hidden flex items-center justify-between">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-400 opacity-70 group-hover:opacity-100 transition-opacity"></div>
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">This Week</span>
                            <span class="text-3xl font-black text-slate-900 font-heading tracking-tight" x-text="defensesList.filter(d => d.status === 'Scheduled').length">2</span>
                            <span class="text-[10px] font-bold text-blue-600 block">Upcoming defenses</span>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-2xl shadow-2xs border border-blue-100 group-hover:scale-110 transition-transform">
                            <i class="ph ph-clock"></i>
                        </div>
                    </div>

                    <!-- Card 3: Pending -->
                    <div class="group bg-white rounded-3xl p-6 shadow-2xs hover:shadow-lg border border-slate-200/80 hover:border-amber-300 transition-all duration-300 relative overflow-hidden flex items-center justify-between">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 to-yellow-400 opacity-70 group-hover:opacity-100 transition-opacity"></div>
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Pending</span>
                            <span class="text-3xl font-black text-slate-900 font-heading tracking-tight" x-text="defensesList.filter(d => d.status === 'Pending').length">1</span>
                            <span class="text-[10px] font-bold text-amber-600 block">Awaiting panel confirmation</span>
                        </div>
                        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-2xl shadow-2xs border border-amber-100 group-hover:scale-110 transition-transform">
                            <i class="ph ph-hourglass"></i>
                        </div>
                    </div>

                    <!-- Card 4: Completed -->
                    <div class="group bg-white rounded-3xl p-6 shadow-2xs hover:shadow-lg border border-slate-200/80 hover:border-slate-300 transition-all duration-300 relative overflow-hidden flex items-center justify-between">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-slate-400 to-slate-500 opacity-70 group-hover:opacity-100 transition-opacity"></div>
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Completed</span>
                            <span class="text-3xl font-black text-slate-900 font-heading tracking-tight" x-text="defensesList.filter(d => d.status === 'Completed').length">0</span>
                            <span class="text-[10px] font-bold text-slate-500 block">Evaluated & resolved</span>
                        </div>
                        <div class="w-12 h-12 bg-slate-50 text-slate-500 rounded-2xl flex items-center justify-center text-2xl shadow-2xs border border-slate-200 group-hover:scale-110 transition-transform">
                            <i class="ph ph-check-circle"></i>
                        </div>
                    </div>
                </div>

                <!-- Filters panel -->
                <div class="bg-white rounded-3xl p-6 shadow-2xs border border-slate-200/80 flex flex-wrap items-center gap-4 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#eebc3f]"></div>
                    <div class="flex items-center gap-2 text-slate-400 mr-2">
                        <i class="ph ph-funnel text-lg text-[#0e5c3a]"></i>
                        <span class="text-xs font-bold text-slate-700">Filter Defenses:</span>
                    </div>
                    <select x-model="defenseFilterType" class="px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs font-bold text-slate-700 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300 cursor-pointer">
                        <option>All Defense Types</option>
                        <option>Proposal Defense</option>
                        <option>Final Defense</option>
                    </select>
                    <select x-model="defenseFilterStatus" class="px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs font-bold text-slate-700 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300 cursor-pointer">
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
                <x-student-section-heading title="Research Repository" description="Browse documents according to your explicit repository permissions." />
                <x-document-repository :documents="$repositoryDocuments" :filters="$repositoryFilters" :stats="$repositoryStats" :stage-options="$repositoryStageOptions" :status-options="$repositoryStatusOptions" />
                @if (false)
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
                @endif
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
                <!-- Section Action Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-2xs relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#eebc3f]"></div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#073823] to-[#0e5c3a] text-[#eebc3f] flex items-center justify-center text-2xl shadow-xs shrink-0">
                            <i class="ph ph-chart-bar"></i>
                        </div>
                        <div>
                            <h2 class="text-xl sm:text-2xl font-black font-heading text-slate-900 flex items-center gap-2">
                                <span>Institutional Analytics & Reports</span>
                                <span class="rounded-full bg-emerald-50 border border-emerald-200 px-3 py-0.5 text-[10px] font-black uppercase text-[#0e5c3a]">
                                    {{ $totalResearchCount }} Studies
                                </span>
                            </h2>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">Review university-wide research throughput, program distribution, and completion rates.</p>
                        </div>
                    </div>
                    <button 
                        type="button" 
                        @click="alert('Exporting PDF Report')"
                        class="px-5 py-2.5 bg-[#0e5c3a] hover:bg-[#073823] text-white text-xs font-black rounded-xl flex items-center gap-2 shadow-md transition-all cursor-pointer"
                    >
                        <i class="ph ph-download-simple text-base"></i>
                        <span>Export PDF Report</span>
                    </button>
                </div>

                <!-- Row of 4 statistics cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                    <!-- Total Research -->
                    <div class="group bg-white rounded-3xl p-6 shadow-2xs hover:shadow-lg border border-slate-200/80 hover:border-emerald-300 transition-all duration-300 relative overflow-hidden flex items-center justify-between">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-400 opacity-70 group-hover:opacity-100 transition-opacity"></div>
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Total Research</span>
                            <span class="text-3xl font-black text-slate-900 font-heading tracking-tight">{{ $totalResearchCount }}</span>
                            <span class="text-[10px] font-bold text-emerald-700 block">Database total</span>
                        </div>
                        <div class="w-12 h-12 bg-emerald-50 text-[#0e5c3a] rounded-2xl flex items-center justify-center text-2xl shadow-2xs border border-emerald-100 group-hover:scale-110 transition-transform">
                            <i class="ph ph-chart-bar"></i>
                        </div>
                    </div>

                    <!-- Completed -->
                    <div class="group bg-white rounded-3xl p-6 shadow-2xs hover:shadow-lg border border-slate-200/80 hover:border-blue-300 transition-all duration-300 relative overflow-hidden flex items-center justify-between">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-400 opacity-70 group-hover:opacity-100 transition-opacity"></div>
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Completed</span>
                            <span class="text-3xl font-black text-slate-900 font-heading tracking-tight">{{ $completedResearchCount }}</span>
                            <span class="text-[10px] font-bold text-blue-600 block">{{ $researchCompletionRate }}% completion</span>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-2xl shadow-2xs border border-blue-100 group-hover:scale-110 transition-transform">
                            <i class="ph ph-trend-up"></i>
                        </div>
                    </div>

                    <!-- In Progress -->
                    <div class="group bg-white rounded-3xl p-6 shadow-2xs hover:shadow-lg border border-slate-200/80 hover:border-amber-300 transition-all duration-300 relative overflow-hidden flex items-center justify-between">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 to-yellow-400 opacity-70 group-hover:opacity-100 transition-opacity"></div>
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">In Progress</span>
                            <span class="text-3xl font-black text-slate-900 font-heading tracking-tight">{{ $activeResearchCount }}</span>
                            <span class="text-[10px] font-bold text-amber-600 block">{{ $researchInProgressRate }}% ongoing</span>
                        </div>
                        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-2xl shadow-2xs border border-amber-100 group-hover:scale-110 transition-transform">
                            <i class="ph ph-chart-pie-slice"></i>
                        </div>
                    </div>

                    <!-- Avg Duration -->
                    <div class="group bg-white rounded-3xl p-6 shadow-2xs hover:shadow-lg border border-slate-200/80 hover:border-purple-300 transition-all duration-300 relative overflow-hidden flex items-center justify-between">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-500 to-violet-400 opacity-70 group-hover:opacity-100 transition-opacity"></div>
                        <div class="space-y-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Avg Duration</span>
                            <span class="text-3xl font-black text-slate-900 font-heading tracking-tight">{{ $averageResearchMonths ?? '—' }}</span>
                            <span class="text-[10px] font-bold text-purple-600 block">months to completion</span>
                        </div>
                        <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center text-2xl shadow-2xs border border-purple-100 group-hover:scale-110 transition-transform">
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
            @can('audit-logs.view')
            <div x-show="activeTab === 'audit'" x-cloak class="space-y-8 animate-fade-in">
                @include('admin.audit-logs')
            </div>
            @endcan

            <div class="hidden" aria-hidden="true">
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

            <!-- TAB 10: SYSTEM SETTINGS -->
            <div x-show="activeTab === 'settings'" x-cloak class="space-y-8 animate-fade-in">
                <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 class="text-3xl font-extrabold font-heading text-gray-800 tracking-tight">System Settings</h1>
                        <p class="mt-1 text-sm font-light text-gray-500">Manage protected institutional and academic configuration.</p>
                    </div>
                    @if ($systemSettingsUpdatedAt)
                        <p class="text-xs font-semibold text-gray-400">Last saved {{ \Illuminate\Support\Carbon::parse($systemSettingsUpdatedAt)->diffForHumans() }}</p>
                    @endif
                </div>

                @if ($successMessage)
                    <div class="flex items-center justify-between rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-900 shadow-sm animate-fade-in">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600 text-white text-base">
                                <i class="ph ph-check-bold"></i>
                            </div>
                            <span>{{ $successMessage }}</span>
                        </div>
                        <button type="button" wire:click="$set('successMessage', null)" class="text-emerald-700 hover:text-emerald-900">
                            <i class="ph ph-x text-lg"></i>
                        </button>
                    </div>
                @endif

                <form wire:submit="saveSystemSettings" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                        <section class="rounded-[2rem] border border-gray-100 bg-white p-7 shadow-sm">
                            <div class="mb-6 flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-[#0e5c3a]"><i class="ph ph-buildings"></i></div>
                                <div>
                                    <h2 class="font-heading text-lg font-extrabold text-gray-800">Institutional Identity</h2>
                                    <p class="text-xs text-gray-500">Names and contact information shown by the system.</p>
                                </div>
                            </div>

                            <div class="space-y-5">
                                <div>
                                    <label for="settings-system-name" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-600">System Name</label>
                                    <input id="settings-system-name" type="text" wire:model="settingsSystemName" maxlength="150" class="w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:outline-none focus:ring-4 focus:ring-[#0e5c3a]/5">
                                    @error('settingsSystemName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-600">College</label>
                                    <input type="text" value="{{ config('academic.college.name') }}" readonly class="w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-500">
                                    <p class="mt-1 text-[11px] text-gray-400">NDMU-RMAS is scoped to this single college.</p>
                                </div>
                                <div>
                                    <label for="settings-support-email" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-600">Support Email</label>
                                    <input id="settings-support-email" type="email" wire:model="settingsSupportEmail" maxlength="255" class="w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:outline-none focus:ring-4 focus:ring-[#0e5c3a]/5">
                                    @error('settingsSupportEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        <section class="rounded-[2rem] border border-gray-100 bg-white p-7 shadow-sm">
                            <div class="mb-6 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-xl text-amber-600"><i class="ph ph-calendar-dots"></i></div>
                                    <div>
                                        <h2 class="font-heading text-lg font-extrabold text-gray-800">Current Academic Cycle</h2>
                                        <p class="text-xs text-gray-500">Select the year and term used by active research workflows.</p>
                                    </div>
                                </div>
                                <button type="button" wire:click="openAcademicYearModal" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-bold text-gray-700 transition hover:bg-gray-100 hover:text-gray-900">
                                    <i class="ph ph-plus-circle text-sm text-[#0e5c3a]"></i>
                                    <span>New Academic Year</span>
                                </button>
                            </div>

                            <div class="space-y-5">
                                <div>
                                    <label for="settings-academic-year" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-600">Academic Year</label>
                                    <select id="settings-academic-year" wire:model.live="settingsAcademicYearId" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm focus:border-[#0e5c3a] focus:outline-none focus:ring-4 focus:ring-[#0e5c3a]/5">
                                        <option value="">No active academic year</option>
                                        @foreach ($academicYears as $academicYear)
                                            <option value="{{ $academicYear->id }}">{{ $academicYear->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('settingsAcademicYearId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="settings-academic-term" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-600">Academic Term</label>
                                    <select id="settings-academic-term" wire:model="settingsAcademicTermId" @disabled($settingsAcademicYearId === null) class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm disabled:cursor-not-allowed disabled:bg-gray-50 focus:border-[#0e5c3a] focus:outline-none focus:ring-4 focus:ring-[#0e5c3a]/5">
                                        <option value="">Select a term</option>
                                        @foreach ($academicYears->firstWhere('id', $settingsAcademicYearId)?->terms ?? collect() as $academicTerm)
                                            <option value="{{ $academicTerm->id }}">{{ $academicTerm->name }} · {{ $academicTerm->starts_at->format('M j') }}–{{ $academicTerm->ends_at->format('M j, Y') }}</option>
                                        @endforeach
                                    </select>
                                    @error('settingsAcademicTermId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                @if ($academicYears->isEmpty())
                                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-800 space-y-3">
                                        <p>No academic years exist yet. Seed default terms or create a new academic year before selecting the active cycle.</p>
                                        <div class="flex gap-2">
                                            <button type="button" wire:click="seedAcademicCycle" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-amber-700 transition">
                                                <i class="ph ph-sparkle"></i> Seed Default Academic Cycle
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </section>
                    </div>

                    <section class="rounded-[2rem] border border-gray-100 bg-white p-7 shadow-sm">
                        <div class="mb-6 flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-xl text-blue-600"><i class="ph ph-sliders-horizontal"></i></div>
                            <div>
                                <h2 class="font-heading text-lg font-extrabold text-gray-800">Platform Controls</h2>
                                <p class="text-xs text-gray-500">Control user-facing services without changing source code.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-2xl border border-gray-200 p-5">
                                <span><span class="block text-sm font-bold text-gray-800">Student Registration</span><span class="mt-1 block text-xs leading-5 text-gray-500">Allow new student registration requests.</span></span>
                                <input type="checkbox" wire:model="settingsStudentRegistrationEnabled" class="mt-1 h-5 w-5 rounded border-gray-300 text-[#0e5c3a] focus:ring-[#0e5c3a]">
                            </label>
                            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-2xl border border-gray-200 p-5">
                                <span><span class="block text-sm font-bold text-gray-800">Email Notifications</span><span class="mt-1 block text-xs leading-5 text-gray-500">Allow workflow notifications to be delivered by email.</span></span>
                                <input type="checkbox" wire:model="settingsEmailNotificationsEnabled" class="mt-1 h-5 w-5 rounded border-gray-300 text-[#0e5c3a] focus:ring-[#0e5c3a]">
                            </label>
                            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-2xl border border-gray-200 p-5 lg:col-span-2">
                                <span class="pr-3">
                                    <span class="block text-sm font-bold text-gray-800">Enable CAPTCHA (Cloudflare Turnstile)</span>
                                    <span class="mt-1 block text-xs leading-5 text-gray-500">Require bot verification on login and student registration. You may turn this off during trusted local testing; keep it enabled when the public site is online.</span>
                                    @if (! config('services.turnstile.site_key') || ! config('services.turnstile.secret_key'))
                                        <span class="mt-2 block text-xs font-semibold text-amber-700">Turnstile keys are incomplete in the environment. Add both keys before enabling public protection.</span>
                                    @endif
                                </span>
                                <span class="relative mt-1 inline-flex shrink-0 items-center">
                                    <input type="checkbox" wire:model="settingsTurnstileEnabled" class="peer sr-only" aria-label="Enable Cloudflare Turnstile CAPTCHA">
                                    <span class="h-7 w-12 rounded-full bg-gray-300 transition peer-checked:bg-[#0e5c3a] peer-focus-visible:ring-4 peer-focus-visible:ring-[#0e5c3a]/20"></span>
                                    <span class="pointer-events-none absolute left-1 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                                </span>
                            </label>
                        </div>

                        <div class="mt-5">
                            <label for="settings-maintenance-notice" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-600">Maintenance Notice</label>
                            <textarea id="settings-maintenance-notice" wire:model="settingsMaintenanceNotice" maxlength="500" rows="3" placeholder="Leave blank when there is no maintenance announcement." class="w-full resize-none rounded-2xl border border-gray-200 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:outline-none focus:ring-4 focus:ring-[#0e5c3a]/5"></textarea>
                            @error('settingsMaintenanceNotice') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </section>

                    <div class="flex justify-end">
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveSystemSettings" class="inline-flex items-center gap-2 rounded-2xl bg-[#0e5c3a] px-6 py-3 text-sm font-bold text-white shadow-md transition hover:bg-[#0a4a2e] disabled:cursor-wait disabled:opacity-60">
                            <i class="ph ph-floppy-disk"></i>
                            <span wire:loading.remove wire:target="saveSystemSettings">Save System Settings</span>
                            <span wire:loading wire:target="saveSystemSettings">Saving…</span>
                        </button>
                    </div>
                </form>
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

            <!-- CREATE ACADEMIC YEAR MODAL -->
            @if ($showAcademicYearModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none">
                <div wire:click="closeAcademicYearModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="relative w-full max-w-md mx-auto my-6 z-10 px-4">
                    <div class="relative flex flex-col w-full bg-white border border-gray-150 rounded-[2rem] shadow-2xl overflow-hidden">
                        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-800">Create Academic Year</h3>
                            <button type="button" wire:click="closeAcademicYearModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="ph ph-x text-xl"></i>
                            </button>
                        </div>
                        <form wire:submit="createAcademicYear" class="p-6 space-y-4">
                            <div>
                                <label for="new-academic-year-name" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-600">Academic Year Name *</label>
                                <input id="new-academic-year-name" type="text" wire:model="newAcademicYearName" placeholder="e.g., 2026–2027" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                @error('newAcademicYearName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="new-academic-year-start" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-600">Start Date *</label>
                                    <input id="new-academic-year-start" type="date" wire:model="newAcademicYearStartDate" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                    @error('newAcademicYearStartDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="new-academic-year-end" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-600">End Date *</label>
                                    <input id="new-academic-year-end" type="date" wire:model="newAcademicYearEndDate" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all">
                                    @error('newAcademicYearEndDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-500">Creating an Academic Year automatically initializes standard First and Second Semester terms.</p>
                            <div class="pt-4 flex items-center justify-end gap-3 border-t border-gray-100">
                                <button type="button" wire:click="closeAcademicYearModal" class="px-5 py-2.5 border border-gray-200 text-gray-500 hover:text-gray-700 text-xs font-bold rounded-2xl transition-all">
                                    Cancel
                                </button>
                                <button type="submit" class="px-5 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-2xl flex items-center gap-2 transition-all">
                                    <i class="ph ph-plus-circle text-base"></i> Create Academic Year
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            <!-- TAB: Notifications Center -->
            <div x-show="activeTab === 'notifications'" x-cloak class="space-y-8 animate-fade-in">
                <x-notifications.center
                    :notifications="auth()->user()->notifications()->latest()->paginate(20)"
                    :unread-count="auth()->user()->unreadNotifications()->count()"
                    :filter="request()->query('notification_filter', 'all')"
                    :dashboard-route="route('admin.dashboard')"
                />
            </div>
        </main>
    </div>
</div>
