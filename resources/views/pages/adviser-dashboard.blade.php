@extends('layouts.blank')

@php
    $allowedTabs = ['dashboard', 'classes', 'consultation', 'docreview', 'revisions', 'repository', 'forms', 'notifications', 'settings', 'researchers', 'monitoring', 'endorsement', 'evaluations'];
    $initialTab = $activeDashboardTab ?? (in_array(request()->query('tab'), $allowedTabs, true) ? request()->query('tab') : 'dashboard');
    $showClassModal = $errors->hasAny(['class', 'creation_token', 'name', 'description', 'max_students']);
    $officialFormPhases = $officialFormPhases ?? [];
    $officialForms = $officialForms ?? [];
    $officialFormsByPhase = collect($officialForms)->groupBy('phase', preserveKeys: true);
    $requestedOfficialForm = request()->query('form');
    $initialOfficialForm = is_string($requestedOfficialForm) && array_key_exists($requestedOfficialForm, $officialForms)
        ? $requestedOfficialForm
        : array_key_first($officialForms);
    $initialFormPhase = $initialOfficialForm === null
        ? array_key_first($officialFormPhases)
        : $officialForms[$initialOfficialForm]['phase'];

    $pendingReqs = $pendingAdviserRequests ?? collect();
    $groupsAssigned = $assignedGroups ?? collect();
    $pendingCount = $pendingReqs->count();
    $assignedCount = $groupsAssigned->count();
@endphp

@section('content')
<style>
    [x-cloak] { display: none !important; }
    /* Subtle scrollbar for sidebar */
    aside::-webkit-scrollbar {
        width: 4px;
    }
    aside::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.02);
    }
    aside::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }
    aside::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.2);
    }
</style>

<div class="min-h-screen flex font-sans bg-[#f4f7f6]" x-data="{ 
    activeTab: @js($initialTab),
    activeFormPhase: @js($initialFormPhase),
    activeOfficialForm: @js($initialOfficialForm),
    officialForms: @js($officialForms),
    formsExpanded: @js($initialTab === 'forms'),
    dashboardUrl: @js(route('adviser.dashboard')),
    persistTabTimer: null,
    queuePersistTab(tab) {
        window.clearTimeout(this.persistTabTimer);
        this.persistTabTimer = window.setTimeout(() => this.persistTab(tab), 0);
    },
    persistTab(tab) {
        const url = new URL(this.dashboardUrl, window.location.origin);
        url.searchParams.set('tab', tab);
        if (tab === 'forms' && this.activeOfficialForm) {
            url.searchParams.set('form', this.activeOfficialForm);
        }

        if (`${url.pathname}${url.search}` === `${window.location.pathname}${window.location.search}`) return;

        window.Livewire?.navigate
            ? window.Livewire.navigate(url.toString())
            : window.location.assign(url.toString());
    },
    notificationsFilter: 'all',
    showClassModal: @js($showClassModal),
    showConsultationModal: false,
    showRepositoryUploadModal: @js($errors->has('document') && $initialTab === 'repository'),
    selectedNotification: null,
    notifications: @js($adviserNotifications),
    assignedResearchers: @js($adviserOverviewAdvisees),
    adviserEvaluations: @js($adviserEvaluations ?? []),
    selectedEvaluation: null,
    evaluationSearch: '',
    evaluationFilter: 'all',
    confirmingAcceptId: null,
    confirmingDeclineId: null
}"
    x-init="
        $watch('activeTab', (tab, previousTab) => {
            if (tab !== previousTab) queuePersistTab(tab);
        });
        $watch('activeOfficialForm', (form, previousForm) => {
            if (activeTab === 'forms' && form !== previousForm) queuePersistTab('forms');
        });
    "
>
    <!-- SIDEBAR NAV -->
    <aside class="fixed inset-y-0 left-0 w-72 bg-gradient-to-b from-[#09472d] via-[#0e5c3a] to-[#073622] text-white flex flex-col justify-between z-20 border-r border-emerald-800/40 shadow-2xl overflow-y-auto">
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
                        {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($adviser->name, 0, 1)) }}
                        <span class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500 border-2 border-[#09472d]"></span>
                        </span>
                    </div>
                    <div class="flex flex-col leading-tight overflow-hidden min-w-0">
                        <span class="font-bold text-xs text-white truncate">{{ $adviser->name }}</span>
                        <span class="text-[10px] text-white/70 font-medium mt-0.5 truncate">Research Adviser</span>
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

                <!-- Dashboard -->
                <a 
                   href="{{ route('adviser.dashboard', ['tab' => 'dashboard']) }}"
                   wire:navigate
                   :class="activeTab === 'dashboard' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-squares-four text-lg transition-transform group-hover:scale-110"></i>
                        <span>Dashboard</span>
                    </div>
                    <span x-show="activeTab === 'dashboard'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </a>
                
                <!-- My Classes -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'classes']) }}"
                   wire:navigate
                   :class="activeTab === 'classes' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-book text-lg transition-transform group-hover:scale-110"></i>
                        <span>My Classes</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['classes'] ?? 0" label="adviser invitations requiring attention" />
                        <span x-show="activeTab === 'classes'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </a>

                <!-- Assigned Researchers -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'researchers']) }}"
                   wire:navigate
                   :class="activeTab === 'researchers' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-users-three text-lg transition-transform group-hover:scale-110"></i>
                        <span>Assigned Researchers</span>
                    </div>
                    <span x-show="activeTab === 'researchers'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </a>

                <!-- Research Monitoring -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'monitoring']) }}"
                   wire:navigate
                   :class="activeTab === 'monitoring' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chart-line-up text-lg transition-transform group-hover:scale-110"></i>
                        <span>Research Monitoring</span>
                    </div>
                    <span x-show="activeTab === 'monitoring'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </a>

                <!-- Document Review -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'docreview']) }}"
                   wire:navigate
                   :class="activeTab === 'docreview' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-text text-lg transition-transform group-hover:scale-110"></i>
                        <span>Document Review</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['docreview'] ?? 0" label="documents awaiting review" />
                        <span x-show="activeTab === 'docreview'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </a>

                <!-- Consultations -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'consultation']) }}"
                   wire:navigate
                   :class="activeTab === 'consultation' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chats-teardrop text-lg transition-transform group-hover:scale-110"></i>
                        <span>Consultations</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['consultation'] ?? 0" label="consultations requiring attention" />
                        <span x-show="activeTab === 'consultation'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </a>

                <!-- Revisions -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'revisions']) }}"
                   wire:navigate
                   :class="activeTab === 'revisions' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-arrows-counter-clockwise text-lg transition-transform group-hover:scale-110"></i>
                        <span>Revision Tracker</span>
                    </div>
                    <span x-show="activeTab === 'revisions'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </a>

                <!-- Defense Endorsement -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'endorsement']) }}"
                   wire:navigate
                   :class="activeTab === 'endorsement' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-seal-check text-lg transition-transform group-hover:scale-110"></i>
                        <span>Defense Endorsement</span>
                    </div>
                    <span x-show="activeTab === 'endorsement'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </a>

                <!-- Evaluation Records -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'evaluations']) }}"
                   wire:navigate
                   :class="activeTab === 'evaluations' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-clipboard-text text-lg transition-transform group-hover:scale-110"></i>
                        <span>Evaluation Records</span>
                    </div>
                    <span x-show="activeTab === 'evaluations'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </a>

                <!-- Research Repository -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'repository']) }}"
                   wire:navigate
                   :class="activeTab === 'repository' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-archive text-lg transition-transform group-hover:scale-110"></i>
                        <span>Research Repository</span>
                    </div>
                    <span x-show="activeTab === 'repository'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </a>

                <!-- Pending Form Approvals Queue -->
                <a
                   href="{{ route('official-forms.workspace.index') }}"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-check-square-offset text-lg text-amber-300 transition-transform group-hover:scale-110"></i>
                        <span>Pending Form Approvals</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['forms'] ?? 0" label="forms awaiting approval" />
                    </div>
                </a>
            </div>

            <!-- Official Forms Section -->
            <div class="space-y-1.5 pt-4">
                <div class="flex items-center gap-2 px-3 mb-2.5">
                    <span class="w-1 h-3 rounded-full bg-[#eebc3f]"></span>
                    <span class="text-[10px] font-black tracking-[0.18em] text-emerald-300/80 uppercase">Official Forms</span>
                </div>

                <button
                    type="button"
                    @click="formsExpanded = ! formsExpanded; activeTab = 'forms'"
                    :class="activeTab === 'forms' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                    :aria-expanded="formsExpanded"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-[13px] transition-all duration-200 text-left cursor-pointer group"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-pdf text-lg transition-transform group-hover:scale-110"></i>
                        <span>Official Forms</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['forms'] ?? 0" label="official form actions requiring attention" />
                        <i class="ph ph-caret-right text-xs transition-transform duration-200" :class="formsExpanded && 'rotate-90'"></i>
                    </div>
                </button>

                <div x-show="formsExpanded" x-cloak x-transition class="mt-1 space-y-0.5">
                    @foreach ($officialFormPhases as $phase => $label)
                        @php
                            $phaseForms = $officialFormsByPhase->get($phase, collect());
                        @endphp
                        <div>
                            <button
                                type="button"
                                @click="activeTab = 'forms'; activeFormPhase = activeFormPhase === '{{ $phase }}' ? null : '{{ $phase }}'"
                                class="w-full flex items-center justify-between gap-2 py-2 pl-4 pr-3 rounded-xl text-white/70 hover:text-white hover:bg-white/10 transition-colors duration-200 text-[11px] font-semibold text-left"
                            >
                                <span class="flex min-w-0 items-start gap-2">
                                    <i class="ph ph-caret-right mt-0.5 shrink-0 text-[10px] transition-transform duration-200" :class="activeFormPhase === '{{ $phase }}' && 'rotate-90'"></i>
                                    <span class="leading-4">{{ $label }}</span>
                                </span>
                                <span class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#eebc3f]/20 px-1.5 text-[9px] font-bold text-[#eebc3f]">
                                    {{ $phaseForms->count() }}
                                </span>
                            </button>

                            <div
                                x-show="activeFormPhase === '{{ $phase }}'"
                                x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="mt-0.5 space-y-0.5 pl-2"
                            >
                                @foreach ($phaseForms as $code => $form)
                                    <button
                                        type="button"
                                        @click="activeTab = 'forms'; activeOfficialForm = '{{ $code }}'"
                                        :class="activeOfficialForm === '{{ $code }}' ? 'bg-[#eebc3f] text-[#09472d] ring-1 ring-white font-bold' : 'text-white/75 hover:text-white hover:bg-white/10'"
                                        class="w-full flex items-start gap-2 rounded-xl px-3 py-2 text-left transition-colors duration-200"
                                    >
                                        <i class="ph ph-file-plus mt-0.5 shrink-0 text-sm"></i>
                                        <span class="min-w-0">
                                            <span class="block text-[10px] font-bold">{{ $code }}</span>
                                            <span class="block text-[10px] leading-3.5">{{ $form['title'] }}</span>
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="flex-shrink-0 px-5 pb-5 mt-auto">
            <!-- Decorative Separator -->
            <div class="relative flex items-center justify-center my-3">
                <div class="w-full h-px bg-gradient-to-r from-transparent via-white/15 to-transparent"></div>
            </div>

            <div class="space-y-1">
                <a
                    href="{{ route('adviser.dashboard', ['tab' => 'notifications']) }}"
                    wire:navigate
                    :class="activeTab === 'notifications' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md' : 'text-white/85 hover:text-white hover:bg-white/15 font-semibold'"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer"
                >
                    <span class="flex items-center gap-3">
                        <i class="ph ph-bell text-lg"></i>
                        <span>Notifications</span>
                    </span>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['notifications'] ?? 0" label="unread notifications" />
                        <span x-show="activeTab === 'notifications'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </a>
                <a
                    href="{{ route('adviser.dashboard', ['tab' => 'settings']) }}"
                    wire:navigate
                    :class="activeTab === 'settings' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md' : 'text-white/85 hover:text-white hover:bg-white/15 font-semibold'"
                    class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer"
                >
                    <i class="ph ph-gear text-lg"></i>
                    <span>Settings</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" data-confirm-logout>
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-white/70 hover:text-rose-200 hover:bg-rose-500/20 border border-transparent hover:border-rose-500/30 font-semibold text-[13px] transition-all duration-200 text-left cursor-pointer">
                        <i class="ph ph-sign-out text-lg"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
            <div class="flex items-center justify-center gap-2 text-[9px] text-white/40 text-center font-medium mt-4">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400/60"></span>
                <span>NDMU-RMAS © {{ now()->year }} · v1.0</span>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <main class="flex-1 min-w-0 flex flex-col h-screen overflow-y-auto pl-72">
        <!-- Top Sticky Header -->
        <header class="h-20 bg-white/85 backdrop-blur-md border-b border-slate-200/80 px-8 flex items-center justify-between sticky top-0 z-40 shrink-0 transition-all">
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Research Adviser</span>
                <span class="text-slate-300">/</span>
                <span class="text-xs font-extrabold text-[#0e5c3a] uppercase tracking-wider">Workspace</span>
            </div>

            <div class="flex items-center gap-4">
                <x-workspace-switcher current="adviser" />
                <x-notification-dropdown />
                <div class="flex items-center gap-2.5 pl-2 border-l border-slate-200">
                    <div class="w-8 h-8 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-xs shadow-2xs">
                        {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($adviser->name, 0, 1)) }}
                    </div>
                    <span class="text-xs font-bold text-slate-800">{{ $adviser->name }}</span>
                </div>
            </div>
        </header>

        <div class="p-8 space-y-8 flex-1">
            <!-- Green Hero Banner Component for specific inner tabs -->
            <x-portal-feature-banner class="mb-8" :sections="[
                'classes' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'My Classes Workspace', 'description' => 'Review pending adviser invitations and manage your assigned research groups.', 'icon' => 'ph-chalkboard-teacher'],
                'researchers' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Assigned Researchers', 'description' => 'View students and groups assigned to you for research advising.', 'icon' => 'ph-users-three'],
                'monitoring' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Research Monitoring', 'description' => 'Track advisee progress, milestones, and research activity.', 'icon' => 'ph-chart-line-up'],
                'docreview' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Document Review System', 'description' => 'Review and annotate documents submitted by your assigned researchers.', 'icon' => 'ph-file-text'],
                'consultation' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Consultation Records', 'description' => 'Manage student consultation bookings and record consultation outcomes.', 'icon' => 'ph-chats-teardrop'],
                'revisions' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Revision Tracker', 'description' => 'Monitor requested manuscript revisions and resubmissions.', 'icon' => 'ph-arrows-counter-clockwise'],
                'endorsement' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Defense Endorsement', 'description' => 'Prepare and monitor defense endorsement requests for your advisees.', 'icon' => 'ph-seal-check'],
                'evaluations' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Evaluation Records', 'description' => 'Review defense and research evaluation records for assigned groups.', 'icon' => 'ph-clipboard-text'],
                'repository' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Research Repository', 'description' => 'Browse approved research documents, manuscripts, and archives.', 'icon' => 'ph-archive'],
                'forms' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Official Research Forms', 'description' => 'Open the official forms required for adviser participation across the seven research phases.', 'icon' => 'ph-file-text'],
                'notifications' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Notifications', 'description' => 'Stay updated with real-time research activity alerts and reminders.', 'icon' => 'ph-bell'],
                'settings' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Account Settings', 'description' => 'Manage your profile, security, signature, and account preferences.', 'icon' => 'ph-gear'],
            ]" />

            <!-- Flash Message Alerts -->
            @if (session('adviser_success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-check-circle text-xl text-emerald-600"></i>
                        <span>{{ session('adviser_success') }}</span>
                    </div>
                </div>
            @endif
            @if ($errors->has('adviser_request'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-warning-circle text-xl text-rose-600"></i>
                        <span>{{ $errors->first('adviser_request') }}</span>
                    </div>
                </div>
            @endif

            <!-- TAB: Dashboard Overview -->
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
                                <span class="text-emerald-200 text-xs font-semibold tracking-wide">Research Advising Portal</span>
                            </div>
                            <h1 class="text-2xl md:text-3xl font-black font-heading text-white tracking-tight drop-shadow-xs">
                                Welcome back, {{ $adviser->name }}!
                            </h1>
                            <p class="text-xs md:text-sm text-emerald-100/85 max-w-2xl leading-relaxed">
                                Manage advisee research groups, review submitted manuscript drafts, and facilitate academic consultations
                            </p>
                        </div>

                        <!-- Quick Action Buttons -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <button
                                type="button"
                                @click="activeTab = 'classes'"
                                class="px-4.5 py-2.5 bg-gradient-to-r from-[#eebc3f] to-[#f4c542] hover:brightness-105 text-[#073823] text-xs font-black rounded-xl flex items-center gap-2 shadow-md shadow-amber-950/20 transition-all cursor-pointer"
                            >
                                <i class="ph ph-chalkboard-teacher text-base"></i>
                                <span>My Classes</span>
                                @if (($pendingAdviserRequestsCount ?? 0) > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-950 text-[#eebc3f]">
                                        {{ $pendingAdviserRequestsCount }}
                                    </span>
                                @endif
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'docreview'"
                                class="px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/15 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer"
                            >
                                <i class="ph ph-file-text text-base text-rose-300"></i>
                                <span>Review Queue</span>
                                @if (($pendingDocReviewsCount ?? 0) > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white">
                                        {{ $pendingDocReviewsCount }}
                                    </span>
                                @endif
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'consultation'"
                                class="px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/15 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer"
                            >
                                <i class="ph ph-chats-teardrop text-base text-blue-300"></i>
                                <span>Consultations</span>
                                @if (($pendingConsultationsCount ?? 0) > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-blue-400 text-blue-950">
                                        {{ $pendingConsultationsCount }}
                                    </span>
                                @endif
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'forms'"
                                class="px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/15 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer"
                            >
                                <i class="ph ph-file-pdf text-base text-[#eebc3f]"></i>
                                <span>Official Forms</span>
                            </button>
                        </div>
                    </div>
                </div>

                <x-pending-academic-actions-card :pendingActions="$pendingAcademicActions ?? []" />

                <!-- Modern Vibrant 4-KPI Metric Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <!-- KPI 1: Group Invitations -->
                    <div
                        @click="activeTab = 'classes'"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-400 via-orange-500 to-amber-500 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-amber-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-bell-ringing"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-600 text-white shadow-md shadow-amber-600/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 relative">
                                    <i class="ph ph-bell-ringing"></i>
                                    @if (($pendingAdviserRequestsCount ?? 0) > 0)
                                        <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-rose-500 rounded-full border-2 border-white animate-pulse"></span>
                                    @endif
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 group-hover:bg-amber-500 group-hover:text-white transition-all">
                                    <span>Invitations</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Group Invitations</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ $pendingAdviserRequestsCount ?? 0 }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>{{ ($pendingAdviserRequestsCount ?? 0) > 0 ? 'Action required' : 'No pending requests' }}</span>
                                    <span class="font-bold {{ ($pendingAdviserRequestsCount ?? 0) > 0 ? 'text-amber-600' : 'text-slate-400' }}">{{ ($pendingAdviserRequestsCount ?? 0) > 0 ? 'Pending' : 'Clear' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 2: Assigned Advisee Groups -->
                    <div
                        @click="activeTab = 'classes'"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 via-emerald-400 to-teal-400 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-emerald-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-chalkboard-teacher"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-[#0e5c3a] text-white shadow-md shadow-emerald-700/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                                    <i class="ph ph-chalkboard-teacher"></i>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-[#0e5c3a] border border-emerald-100 group-hover:bg-[#0e5c3a] group-hover:text-white transition-all">
                                    <span>Advisees</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Advisee Groups</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ $assignedCount ?? 0 }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>{{ ($assignedGroups ?? collect())->sum(fn($g) => $g->members->count()) }} Students Total</span>
                                    <span class="font-bold text-[#0e5c3a]">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 3: Document Review Queue -->
                    <div
                        @click="activeTab = 'docreview'"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-rose-500 via-rose-600 to-pink-500 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-rose-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-file-magnifying-glass"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-rose-500 to-pink-700 text-white shadow-md shadow-rose-700/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 relative">
                                    <i class="ph ph-file-magnifying-glass"></i>
                                    @if (($pendingDocReviewsCount ?? 0) > 0)
                                        <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-rose-500 rounded-full border-2 border-white animate-pulse"></span>
                                    @endif
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-100 group-hover:bg-rose-600 group-hover:text-white transition-all">
                                    <span>Reviews</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Review Queue</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ $pendingDocReviewsCount ?? 0 }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>{{ ($pendingDocReviewsCount ?? 0) > 0 ? 'Awaiting review' : 'All caught up' }}</span>
                                    <span class="font-bold {{ ($pendingDocReviewsCount ?? 0) > 0 ? 'text-rose-600' : 'text-slate-400' }}">{{ ($pendingDocReviewsCount ?? 0) > 0 ? 'Action Needed' : 'Reviewed' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4: Consultations -->
                    <div
                        @click="activeTab = 'consultation'"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-cyan-400 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-blue-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-chats-teardrop"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-700 text-white shadow-md shadow-blue-700/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                                    <i class="ph ph-chats-teardrop"></i>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100 group-hover:bg-blue-600 group-hover:text-white transition-all">
                                    <span>Bookings</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Consultations</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ $pendingConsultationsCount ?? 0 }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>{{ ($pendingConsultationsCount ?? 0) > 0 ? 'Pending student bookings' : 'No pending requests' }}</span>
                                    <span class="font-bold text-blue-600">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- === MAIN WORKSPACE GRID === --}}
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">

                    {{-- LEFT: Advisee Group Cards --}}
                    <div class="xl:col-span-8 space-y-5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-1 h-4 rounded-full bg-[#0e5c3a]"></span>
                                <h2 class="font-bold text-base text-slate-900">My Advisee Research Groups</h2>
                            </div>
                            <button type="button" @click="activeTab = 'classes'"
                                class="text-xs text-[#0e5c3a] font-bold hover:underline flex items-center gap-1">
                                View All <i class="ph ph-arrow-right"></i>
                            </button>
                        </div>

                        @if(($assignedGroups ?? collect())->isEmpty() && ($pendingAdviserRequestsCount ?? 0) === 0)
                            <div class="bg-white rounded-2xl p-10 border border-slate-200/60 shadow-xs text-center space-y-3">
                                <div class="w-14 h-14 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-2xl text-slate-300 mx-auto">
                                    <i class="ph ph-chalkboard-teacher"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">No Assigned Groups Yet</p>
                                    <p class="text-xs text-slate-400 mt-1">When a Facilitator invites you to advise a research group, it will appear here.</p>
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach(($assignedGroups ?? collect())->take(4) as $aGroup)
                                    @php
                                        $memberCount = $aGroup->members->count();
                                        $className = $aGroup->researchClass?->name ?? 'Research Class';
                                    @endphp
                                    <div class="group bg-white rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-emerald-300 transition-all duration-200 overflow-hidden">
                                        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                                            <div class="min-w-0">
                                                <p class="text-[10px] font-black uppercase tracking-wider text-emerald-600">{{ $className }}</p>
                                                <h3 class="font-bold text-sm text-slate-900 truncate mt-0.5">{{ $aGroup->name }}</h3>
                                            </div>
                                            <span class="shrink-0 w-9 h-9 rounded-xl bg-emerald-50 text-[#0e5c3a] border border-emerald-100 flex items-center justify-center text-base">
                                                <i class="ph ph-users-three"></i>
                                            </span>
                                        </div>
                                        <div class="px-5 py-3.5 space-y-3">
                                            <div class="flex items-center justify-between text-xs text-slate-500">
                                                <span>{{ $memberCount }} {{ Str::plural('member', $memberCount) }}</span>
                                                <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-100">Active</span>
                                            </div>
                                            <div class="flex gap-2">
                                                <button type="button" @click="activeTab = 'docreview'"
                                                    class="flex-1 py-2 rounded-xl bg-[#0e5c3a] text-white text-xs font-bold hover:bg-[#0a4a2e] transition cursor-pointer">
                                                    Review Docs
                                                </button>
                                                <button type="button" @click="activeTab = 'monitoring'"
                                                    class="flex-1 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition cursor-pointer">
                                                    Monitor
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @if(($pendingAdviserRequestsCount ?? 0) > 0)
                                    <button type="button" @click="activeTab = 'classes'"
                                        class="bg-amber-50 border-2 border-dashed border-amber-300 rounded-2xl p-5 flex flex-col items-center justify-center gap-2 text-center hover:bg-amber-100/70 transition cursor-pointer">
                                        <div class="w-10 h-10 rounded-full bg-amber-100 border border-amber-200 flex items-center justify-center text-amber-700 text-xl">
                                            <i class="ph ph-bell-ringing"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-amber-800">{{ $pendingAdviserRequestsCount }} Pending Invitation{{ $pendingAdviserRequestsCount > 1 ? 's' : '' }}</p>
                                            <p class="text-[10px] text-amber-600 mt-0.5">Tap to review and respond</p>
                                        </div>
                                    </button>
                                @endif
                            </div>

                            {{-- Advisee Research Lifecycle --}}
                            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
                                <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 mb-4">Advisee Research Lifecycle</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    @php
                                        $phases = [
                                            ['icon' => 'ph-scroll', 'label' => 'Proposal & Screening', 'color' => 'text-amber-600 bg-amber-50 border-amber-100'],
                                            ['icon' => 'ph-file-magnifying-glass', 'label' => 'Review & Advising', 'color' => 'text-blue-600 bg-blue-50 border-blue-100'],
                                            ['icon' => 'ph-chats-teardrop', 'label' => 'Defense & Evaluation', 'color' => 'text-purple-600 bg-purple-50 border-purple-100'],
                                            ['icon' => 'ph-archive', 'label' => 'Archiving', 'color' => 'text-emerald-600 bg-emerald-50 border-emerald-100'],
                                        ];
                                    @endphp
                                    @foreach($phases as $i => $phase)
                                        <div class="flex flex-col items-center gap-2 p-3 rounded-xl border border-slate-100 bg-slate-50/50 text-center">
                                            <div class="w-9 h-9 rounded-xl {{ $phase['color'] }} border flex items-center justify-center text-lg">
                                                <i class="ph {{ $phase['icon'] }}"></i>
                                            </div>
                                            <span class="text-[10px] font-bold text-slate-700 leading-tight">{{ $phase['label'] }}</span>
                                            <div class="flex items-center gap-1 text-slate-400">
                                                <span class="h-px w-4 bg-slate-300 {{ $i < 3 ? '' : 'opacity-0' }}"></span>
                                                @if($i < 3)<i class="ph ph-arrow-right text-xs text-slate-300"></i>@endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- RIGHT: Live Feed Column --}}
                    <div class="xl:col-span-4 space-y-5">

                        {{-- Urgent Review Feed --}}
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-warning-circle text-rose-500 text-base"></i>
                                    <h3 class="font-bold text-xs text-slate-900">Urgent Reviews</h3>
                                </div>
                                <button type="button" @click="activeTab = 'docreview'" class="text-[10px] font-bold text-[#0e5c3a] hover:underline cursor-pointer">See all</button>
                            </div>
                            <div class="divide-y divide-slate-50">
                                @if(($pendingDocReviewsCount ?? 0) === 0 && ($pendingConsultationsCount ?? 0) === 0)
                                    <div class="px-5 py-7 text-center">
                                        <i class="ph ph-check-circle text-2xl text-emerald-400"></i>
                                        <p class="text-xs font-semibold text-slate-500 mt-2">All Caught Up!</p>
                                        <p class="text-[10px] text-slate-400 mt-1">No pending items right now.</p>
                                    </div>
                                @else
                                    @if(($pendingDocReviewsCount ?? 0) > 0)
                                        <div class="px-5 py-3.5 flex items-center justify-between gap-3 hover:bg-rose-50/30 transition">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center text-sm flex-shrink-0">
                                                    <i class="ph ph-file-text"></i>
                                                </div>
                                                <div>
                                                    <p class="text-xs font-bold text-slate-800">Document Submissions</p>
                                                    <p class="text-[10px] text-slate-500">{{ $pendingDocReviewsCount }} awaiting review</p>
                                                </div>
                                            </div>
                                            <button type="button" @click="activeTab = 'docreview'"
                                                class="shrink-0 px-2.5 py-1.5 rounded-lg bg-rose-500 text-white text-[10px] font-bold hover:bg-rose-600 cursor-pointer">
                                                Review
                                            </button>
                                        </div>
                                    @endif
                                    @if(($pendingConsultationsCount ?? 0) > 0)
                                        <div class="px-5 py-3.5 flex items-center justify-between gap-3 hover:bg-amber-50/30 transition">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-sm flex-shrink-0">
                                                    <i class="ph ph-chats-teardrop"></i>
                                                </div>
                                                <div>
                                                    <p class="text-xs font-bold text-slate-800">Consultation Requests</p>
                                                    <p class="text-[10px] text-slate-500">{{ $pendingConsultationsCount }} pending</p>
                                                </div>
                                            </div>
                                            <button type="button" @click="activeTab = 'consultation'"
                                                class="shrink-0 px-2.5 py-1.5 rounded-lg bg-amber-500 text-white text-[10px] font-bold hover:bg-amber-600 cursor-pointer">
                                                Respond
                                            </button>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        {{-- Upcoming Defenses --}}
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-calendar-check text-purple-500 text-base"></i>
                                    <h3 class="font-bold text-xs text-slate-900">Upcoming Defenses</h3>
                                </div>
                            </div>
                            <div class="divide-y divide-slate-50">
                                @php
                                    $upcomingDefenses = collect($adviserDefenses ?? [])->filter(fn($d) => isset($d['starts_at']) && \Illuminate\Support\Carbon::parse($d['starts_at'])->isFuture())->take(3);
                                @endphp
                                @if($upcomingDefenses->isEmpty())
                                    <div class="px-5 py-7 text-center">
                                        <i class="ph ph-calendar text-2xl text-slate-300"></i>
                                        <p class="text-xs font-semibold text-slate-500 mt-2">No Upcoming Defenses</p>
                                    </div>
                                @else
                                    @foreach($upcomingDefenses as $def)
                                        <div class="px-5 py-3.5 space-y-1.5">
                                            <div class="flex items-center justify-between gap-2">
                                                <p class="text-xs font-bold text-slate-800 truncate">{{ $def['group_name'] ?? 'Research Group' }}</p>
                                                <span class="shrink-0 px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 text-[9px] font-bold border border-purple-100">
                                                    {{ $def['defense_type_label'] ?? 'Defense' }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 text-[10px] text-slate-500">
                                                <i class="ph ph-calendar-blank text-xs"></i>
                                                <span>{{ isset($def['starts_at']) ? \Illuminate\Support\Carbon::parse($def['starts_at'])->format('M j, Y · g:i A') : 'TBA' }}</span>
                                            </div>
                                            @if(!empty($def['room_name']))
                                                <div class="flex items-center gap-2 text-[10px] text-slate-500">
                                                    <i class="ph ph-map-pin text-xs"></i>
                                                    <span>{{ $def['room_name'] }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- TAB: My Classes Workspace -->
            <div x-show="activeTab === 'classes'" x-cloak class="space-y-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold font-heading text-gray-850">My Classes Workspace</h1>
                        <p class="text-xs text-gray-500 mt-1">Review pending adviser invitations and manage your assigned research groups.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold flex items-center gap-2">
                            <i class="ph ph-bell-ringing text-sm"></i>
                            <span>Pending Requests: {{ $pendingCount }}</span>
                        </span>
                        <span class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2">
                            <i class="ph ph-users-three text-sm"></i>
                            <span>Assigned Groups: {{ $assignedCount }}</span>
                        </span>
                    </div>
                </div>

                <!-- Case 1: Absolutely No Pending Requests AND No Assigned Groups -->
                @if ($pendingCount === 0 && $assignedCount === 0)
                    <div class="bg-white rounded-3xl p-12 border border-gray-100 shadow-sm text-center max-w-lg mx-auto space-y-4">
                        <div class="w-16 h-16 rounded-full bg-gray-50 text-gray-400 text-3xl flex items-center justify-center mx-auto">
                            <i class="ph ph-chalkboard-teacher"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-base">No Research Groups Assigned</h3>
                            <p class="text-xs text-gray-500 mt-1">No research groups have been assigned to you yet. When a Research Facilitator invites you to advise a group, the request will appear here.</p>
                        </div>
                    </div>
                @else
                    <!-- Section 1: Pending Adviser Requests (High Priority if present) -->
                    @if ($pendingCount > 0)
                        <section class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base font-bold text-gray-850">Pending Adviser Requests</h2>
                                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-rose-500 text-white shadow-2xs">{{ $pendingCount }}</span>
                                </div>
                                <span class="text-xs text-amber-700 font-semibold">Action required before assignment is finalized</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach ($pendingReqs as $pReq)
                                    @php
                                        $pGroup = $pReq->group;
                                        $pClass = $pGroup?->researchClass;
                                        $pMembersCount = $pGroup?->members?->count() ?? 0;
                                    @endphp
                                    <div class="bg-white rounded-2xl border-2 border-amber-200 p-6 shadow-sm space-y-5 relative overflow-hidden" x-data="{ confirmingAccept: false, confirmingDecline: false }">
                                        <div class="flex justify-between items-start border-b border-gray-100 pb-4">
                                            <div>
                                                <span class="text-[10px] font-black uppercase tracking-wider text-amber-600">Adviser Invitation</span>
                                                <h3 class="font-bold text-gray-850 text-lg mt-0.5">{{ $pGroup?->name ?? 'Unnamed Group' }}</h3>
                                            </div>
                                            <span class="px-3 py-1 bg-amber-100 text-amber-800 text-[10px] font-extrabold rounded-full uppercase tracking-wider">
                                                Pending
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4 text-xs">
                                            <div>
                                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Research Class</p>
                                                <p class="font-bold text-gray-800 mt-1">{{ $pClass?->name ?? 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Requested By</p>
                                                <p class="font-bold text-gray-800 mt-1">{{ $pReq->requester?->name ?? 'Facilitator' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Members</p>
                                                <p class="font-bold text-gray-800 mt-1">{{ $pMembersCount }} / 4 Students</p>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Request Date</p>
                                                <p class="font-bold text-gray-800 mt-1">{{ $pReq->requested_at?->format('M j, Y') ?? 'Recently' }}</p>
                                            </div>
                                        </div>

                                        <div class="pt-2 flex items-center justify-end gap-3 border-t border-gray-100">
                                            <button type="button" @click="confirmingDecline = true" class="px-4 py-2.5 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-50 transition cursor-pointer">
                                                Decline Request
                                            </button>
                                            <button type="button" @click="confirmingAccept = true" class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-sm transition cursor-pointer">
                                                Accept Assignment
                                            </button>
                                        </div>

                                        <!-- Accept Confirmation Overlay Modal -->
                                        <div x-show="confirmingAccept" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                            <div class="absolute inset-0 bg-black/50" @click="confirmingAccept = false"></div>
                                            <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl p-6 space-y-5 text-left" @click.stop>
                                                <div class="flex items-center gap-3 text-emerald-700">
                                                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-xl font-bold">
                                                        <i class="ph ph-check-circle"></i>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-bold text-lg text-gray-850">Accept Adviser Assignment?</h3>
                                                        <p class="text-xs text-gray-500">Confirm your role for this research group.</p>
                                                    </div>
                                                </div>

                                                <p class="text-xs leading-relaxed text-gray-600">
                                                    You are about to become the official research adviser for <strong>{{ $pGroup?->name }}</strong> in <strong>{{ $pClass?->name }}</strong>. After accepting, this group will appear under "My Assigned Research Groups".
                                                </p>

                                                <div class="flex justify-end gap-3 pt-2">
                                                    <button type="button" @click="confirmingAccept = false" class="px-4 py-2.5 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-50">
                                                        Cancel
                                                    </button>
                                                    <form method="POST" action="{{ route('adviser.group-requests.respond', $pReq) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="decision" value="accept">
                                                        <button type="submit" class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl">
                                                            Accept Assignment
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Decline Confirmation Overlay Modal -->
                                        <div x-show="confirmingDecline" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                            <div class="absolute inset-0 bg-black/50" @click="confirmingDecline = false"></div>
                                            <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl p-6 space-y-5 text-left" @click.stop>
                                                <div class="flex items-center gap-3 text-rose-700">
                                                    <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center text-xl font-bold">
                                                        <i class="ph ph-warning-circle"></i>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-bold text-lg text-gray-850">Decline Adviser Request?</h3>
                                                        <p class="text-xs text-gray-500">Decline invitation for this group.</p>
                                                    </div>
                                                </div>

                                                <p class="text-xs leading-relaxed text-gray-600">
                                                    You will not be assigned as adviser for <strong>{{ $pGroup?->name }}</strong> in <strong>{{ $pClass?->name }}</strong>. The Research Facilitator may send another adviser request later if needed.
                                                </p>

                                                <div class="flex justify-end gap-3 pt-2">
                                                    <button type="button" @click="confirmingDecline = false" class="px-4 py-2.5 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-50">
                                                        Cancel
                                                    </button>
                                                    <form method="POST" action="{{ route('adviser.group-requests.respond', $pReq) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="decision" value="decline">
                                                        <button type="submit" class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl">
                                                            Decline Request
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <!-- Section 2: My Assigned Research Groups -->
                    <section class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-base font-bold text-gray-850">My Assigned Research Groups</h2>
                            <span class="text-xs text-gray-500 font-semibold">Active groups where you are the accepted adviser</span>
                        </div>

                        @if ($assignedCount === 0)
                            <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm text-center text-xs text-gray-500">
                                No accepted research groups yet.
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach ($groupsAssigned as $aGrp)
                                    @php
                                        $aClass = $aGrp->researchClass;
                                        $aMembers = $aGrp->members;
                                    @endphp
                                    <div class="bg-white rounded-2xl border border-gray-150 p-6 shadow-sm space-y-4 border-t-4 border-t-[#0e5c3a]">
                                        <div class="flex justify-between items-start border-b border-gray-100 pb-4">
                                            <div>
                                                <h3 class="font-extrabold text-gray-850 text-base">{{ $aGrp->name }}</h3>
                                                <p class="text-xs font-semibold text-[#0e5c3a] mt-0.5">{{ $aClass?->name ?? 'Research Class' }}</p>
                                            </div>
                                            <span class="px-3 py-1 bg-emerald-50 text-[#0e5c3a] border border-emerald-200 text-[10px] font-black rounded-full uppercase tracking-wider shadow-2xs">
                                                Active Adviser
                                            </span>
                                        </div>

                                        <!-- Research Title Placeholder for future integration -->
                                        <div class="rounded-xl bg-gray-50 p-3.5 border border-gray-100 space-y-1">
                                            <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Research Title</p>
                                            <p class="text-xs font-semibold text-gray-500 italic">No title selected yet</p>
                                        </div>

                                        <!-- Members List -->
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Group Members</span>
                                                <span class="font-bold text-gray-700">Members ({{ $aMembers->count() }}/4)</span>
                                            </div>
                                            @if ($aMembers->isEmpty())
                                                <p class="text-xs text-gray-400 italic">No students assigned to group.</p>
                                            @else
                                                <ul class="space-y-2 border border-gray-150 rounded-xl p-3 bg-white">
                                                    @foreach ($aMembers as $aMb)
                                                        @php
                                                            $isGroupLeader = (int) $aGrp->leader_student_id === (int) $aMb->student_id;
                                                        @endphp
                                                        <li @class([
                                                            'text-xs font-bold text-gray-800 flex items-center justify-between gap-2 rounded-xl px-3 py-2 transition-all',
                                                            'bg-amber-50/80 border border-amber-300 shadow-2xs' => $isGroupLeader,
                                                            'bg-gray-50/60 border border-gray-100' => ! $isGroupLeader,
                                                        ])>
                                                            <div class="flex items-center gap-2.5 min-w-0">
                                                                <i @class([
                                                                    'ph text-base shrink-0',
                                                                    'ph-crown-fill text-amber-500' => $isGroupLeader,
                                                                    'ph-user-circle text-gray-400' => ! $isGroupLeader,
                                                                ])></i>
                                                                <span class="truncate text-xs font-bold text-gray-850">{{ $aMb->student?->name ?? 'Student' }}</span>
                                                            </div>
                                                            @if ($isGroupLeader)
                                                                <span class="shrink-0 inline-flex items-center gap-1 rounded-full border border-amber-300 bg-amber-100 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-amber-900 shadow-2xs">
                                                                    <i class="ph ph-star-fill text-amber-600 text-[10px]"></i>
                                                                    <span>Student Leader</span>
                                                                    <span class="sr-only">Group Leader</span>
                                                                </span>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endif
            </div>

            @include('pages.adviser.partials.consultations')

            <div x-show="activeTab === 'repository'" x-cloak class="space-y-6">
                <x-student-section-heading title="Research Repository" description="Browse documents owned by your currently assigned research groups." />
                @isset($repositoryDocuments)
                    <x-document-repository :documents="$repositoryDocuments" :filters="$repositoryFilters" :stats="$repositoryStats" :stage-options="$repositoryStageOptions" :status-options="$repositoryStatusOptions" />
                @endisset
            </div>

            <!-- TAB: Document Review -->
            <div x-show="activeTab === 'docreview'" x-cloak class="space-y-6" x-data="{ showCorrectionModal: false }">
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-850">Document Review Workstation</h1>
                    <p class="text-sm text-gray-500 mt-1">Annotate findings, resolve issues, and record authoritative review decisions for your assigned research groups.</p>
                </div>

                @if (session('document_review_success'))
                    <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-800 text-sm font-bold flex items-center gap-3">
                        <i class="ph ph-check-circle text-xl text-emerald-600"></i>
                        <span>{{ session('document_review_success') }}</span>
                    </div>
                @endif

                @if ($errors->has('document_review'))
                    <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-rose-800 text-sm font-bold flex items-center gap-3">
                        <i class="ph ph-warning-circle text-xl text-rose-600"></i>
                        <span>{{ $errors->first('document_review') }}</span>
                    </div>
                @endif

                <!-- Stats Bar -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl p-4 border border-gray-150 shadow-2xs">
                        <p class="text-[10px] font-black uppercase tracking-wider text-gray-400">Accepted</p>
                        <p class="text-2xl font-extrabold text-emerald-700 mt-1">{{ $documentReviewStats['approved'] ?? 0 }}</p>
                    </div>
                    <div class="bg-white rounded-2xl p-4 border border-gray-150 shadow-2xs">
                        <p class="text-[10px] font-black uppercase tracking-wider text-gray-400">Revision Requested</p>
                        <p class="text-2xl font-extrabold text-amber-700 mt-1">{{ $documentReviewStats['revisions'] ?? 0 }}</p>
                    </div>
                    <div class="bg-white rounded-2xl p-4 border border-gray-150 shadow-2xs">
                        <p class="text-[10px] font-black uppercase tracking-wider text-gray-400">Total Findings</p>
                        <p class="text-2xl font-extrabold text-blue-700 mt-1">{{ $documentReviewStats['comments'] ?? 0 }}</p>
                    </div>
                    <div class="bg-white rounded-2xl p-4 border border-gray-150 shadow-2xs">
                        <p class="text-[10px] font-black uppercase tracking-wider text-gray-400">Unresolved Critical</p>
                        <p class="text-2xl font-extrabold text-rose-700 mt-1">{{ $documentReviewStats['critical'] ?? 0 }}</p>
                    </div>
                </div>

                <!-- Search & Filters -->
                <form method="GET" action="{{ route('adviser.dashboard') }}" class="bg-white rounded-2xl border border-gray-150 p-4 shadow-2xs flex flex-wrap items-center gap-3">
                    <input type="hidden" name="tab" value="docreview">
                    <div class="flex-1 min-w-[200px]">
                        <input
                            type="text"
                            name="document_search"
                            value="{{ $documentReviewSearch ?? '' }}"
                            placeholder="Search document filename, group, or student..."
                            class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-xs focus:border-[#0e5c3a] focus:outline-none"
                        >
                    </div>

                    <!-- Status Filter -->
                    <select
                        name="document_status"
                        class="rounded-xl border border-gray-200 px-3.5 py-2.5 text-xs font-semibold focus:border-[#0e5c3a] focus:outline-none"
                    >
                        <option value="needs_attention" @selected(($documentReviewStatus ?? '') === 'needs_attention')>Needs Attention</option>
                        <option value="pending" @selected(($documentReviewStatus ?? '') === 'pending')>Pending</option>
                        <option value="under_review" @selected(($documentReviewStatus ?? '') === 'under_review')>Under Review</option>
                        <option value="revision_requested" @selected(($documentReviewStatus ?? '') === 'revision_requested')>Revision Requested</option>
                        <option value="accepted" @selected(($documentReviewStatus ?? '') === 'accepted')>Accepted</option>
                        <option value="rejected" @selected(($documentReviewStatus ?? '') === 'rejected')>Rejected</option>
                        <option value="all" @selected(($documentReviewStatus ?? '') === 'all')>All Statuses</option>
                    </select>

                    <!-- Stage Filter -->
                    <select
                        name="document_stage"
                        class="rounded-xl border border-gray-200 px-3.5 py-2.5 text-xs font-semibold focus:border-[#0e5c3a] focus:outline-none"
                    >
                        <option value="">All Stages</option>
                        @foreach (\App\Enums\DocumentStage::cases() as $stg)
                            <option value="{{ $stg->value }}" @selected(($documentReviewStage ?? '') === $stg->value)>{{ $stg->label() }}</option>
                        @endforeach
                    </select>

                    <!-- Group Filter -->
                    @if (isset($assignedGroupOptions) && $assignedGroupOptions->isNotEmpty())
                        <select
                            name="document_group_id"
                            class="rounded-xl border border-gray-200 px-3.5 py-2.5 text-xs font-semibold focus:border-[#0e5c3a] focus:outline-none"
                        >
                            <option value="">All Assigned Groups</option>
                            @foreach ($assignedGroupOptions as $grpOpt)
                                <option value="{{ $grpOpt['id'] }}" @selected(($documentReviewGroup ?? null) === $grpOpt['id'])>{{ $grpOpt['name'] }}</option>
                            @endforeach
                        </select>
                    @endif

                    <!-- File Type Filter -->
                    <select
                        name="document_file_type"
                        class="rounded-xl border border-gray-200 px-3.5 py-2.5 text-xs font-semibold focus:border-[#0e5c3a] focus:outline-none"
                    >
                        <option value="">All File Types</option>
                        <option value="pdf" @selected(($documentReviewFileType ?? '') === 'pdf')>PDF</option>
                        <option value="docx" @selected(($documentReviewFileType ?? '') === 'docx')>DOCX</option>
                    </select>

                    <!-- Sort Filter -->
                    <select
                        name="document_sort"
                        class="rounded-xl border border-gray-200 px-3.5 py-2.5 text-xs font-semibold focus:border-[#0e5c3a] focus:outline-none"
                    >
                        <option value="newest" @selected(($documentReviewSort ?? 'newest') === 'newest')>Newest First</option>
                        <option value="oldest" @selected(($documentReviewSort ?? '') === 'oldest')>Oldest First</option>
                    </select>

                    <button type="submit" class="rounded-xl bg-[#0e5c3a] px-4 py-2.5 text-xs font-bold text-white hover:bg-[#0a4a2e]">
                        Filter Queue
                    </button>
                </form>

                <!-- Queue Split View -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Left: Submissions Queue List -->
                    <div class="lg:col-span-5 space-y-3">
                        <h2 class="text-xs font-extrabold uppercase tracking-wider text-gray-500">Document Queue</h2>
                        @if (!isset($reviewDocuments) || $reviewDocuments->isEmpty())
                            <div class="bg-white rounded-2xl p-8 border border-gray-150 shadow-2xs text-center text-xs text-gray-500">
                                No documents match the current queue filters.
                            </div>
                        @else
                            <div class="space-y-2.5">
                                @foreach ($reviewDocuments as $qDoc)
                                    @php
                                        $isSelected = isset($selectedReviewDocument) && $selectedReviewDocument->id === $qDoc->id;
                                    @endphp
                                    <a
                                        href="{{ route('adviser.dashboard', ['tab' => 'docreview', 'document_search' => $documentReviewSearch ?? '', 'document_status' => $documentReviewStatus ?? '', 'document_stage' => $documentReviewStage ?? '', 'document_group_id' => $documentReviewGroup ?? '', 'document_file_type' => $documentReviewFileType ?? '', 'document_sort' => $documentReviewSort ?? 'newest', 'document_id' => $qDoc->id]) }}"
                                        class="block rounded-2xl border transition-all p-4 {{ $isSelected ? 'border-[#0e5c3a] bg-emerald-50/50 shadow-sm ring-1 ring-[#0e5c3a]' : 'border-gray-150 bg-white hover:border-gray-300' }}"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <h3 class="font-bold text-xs text-gray-850 truncate">{{ $qDoc->original_filename }}</h3>
                                                <p class="text-[11px] font-semibold text-[#0e5c3a] mt-0.5">{{ $qDoc->researchClassGroup?->name ?? 'Group Submission' }}</p>
                                                <p class="text-[10px] text-gray-400 mt-1">Uploader: {{ $qDoc->user?->name ?? 'Student' }}</p>
                                            </div>
                                            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[9px] font-black uppercase
                                                @if($qDoc->status->value === 'accepted') bg-emerald-100 text-emerald-800
                                                @elseif($qDoc->status->value === 'revision_requested') bg-amber-100 text-amber-800
                                                @elseif($qDoc->status->value === 'rejected') bg-rose-100 text-rose-800
                                                @elseif($qDoc->status->value === 'under_review') bg-blue-100 text-blue-800
                                                @else bg-gray-200 text-gray-700 @endif">
                                                {{ \Illuminate\Support\Str::headline($qDoc->status->value) }}
                                            </span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                            <div class="pt-2">
                                {{ $reviewDocuments->links() }}
                            </div>
                        @endif
                    </div>

                    <!-- Right: Workstation Details & Action Panel -->
                    <div class="lg:col-span-7 space-y-6">
                        @if (!isset($selectedReviewDocument) || $selectedReviewDocument === null)
                            <div class="bg-white rounded-2xl p-12 border border-gray-150 shadow-2xs text-center text-gray-500">
                                <i class="ph ph-file-text text-5xl text-gray-300"></i>
                                <p class="mt-3 text-sm font-semibold">Select a document submission from the left queue to begin reviewing.</p>
                            </div>
                        @else
                            @php
                                $selDoc = $selectedReviewDocument;
                                $unresolvedBlocking = isset($documentReviewComments) && $documentReviewComments->whereNull('resolved_at')->whereIn('severity', ['revision', 'critical'])->count() > 0;
                            @endphp

                            <!-- Selected Document Overview Card -->
                            <div class="bg-white rounded-2xl border border-gray-150 p-6 shadow-2xs space-y-4">
                                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-150 pb-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h2 class="font-extrabold text-lg text-gray-850">{{ $selDoc->original_filename }}</h2>
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-black uppercase text-[#0e5c3a]">
                                                V{{ $selDoc->version_number }} · {{ $selDoc->is_current ? 'CURRENT' : 'VOID' }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Group: <strong>{{ $selDoc->researchClassGroup?->name ?? 'N/A' }}</strong> · Stage: <strong>{{ $selDoc->stageLabel() }}</strong>
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('documents.view', $selDoc) }}" class="rounded-xl bg-[#0e5c3a] px-3.5 py-2 text-xs font-bold text-white hover:bg-[#0a4a2e]">
                                            Inspect / View
                                        </a>
                                        <a href="{{ route('documents.download', $selDoc) }}" class="rounded-xl border border-gray-200 px-3.5 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50">
                                            Download
                                        </a>
                                    </div>
                                </div>

                                <!-- Add Finding Comment Form -->
                                @if ($selDoc->is_current)
                                    <form method="POST" action="{{ route('adviser.documents.comments.store', $selDoc) }}" class="space-y-3 bg-gray-50 p-4 rounded-xl border border-gray-200">
                                        @csrf
                                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-700">Add Finding / Annotate Comment</h4>
                                        <textarea
                                            name="comment"
                                            rows="3"
                                            required
                                            placeholder="Write detailed adviser feedback or revision finding..."
                                            class="w-full rounded-xl border border-gray-200 p-3 text-xs focus:border-[#0e5c3a] focus:outline-none bg-white"
                                        ></textarea>
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div class="flex items-center gap-3">
                                                <div>
                                                    <label class="text-[10px] font-bold text-gray-500 uppercase block mb-1">Severity</label>
                                                    <select name="severity" class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-semibold focus:border-[#0e5c3a] focus:outline-none bg-white">
                                                        <option value="comment">Comment (Informational)</option>
                                                        <option value="revision">Revision Required</option>
                                                        <option value="critical">Critical Blocker</option>
                                                    </select>
                                                </div>
                                                @if (strtolower((string) $selDoc->file_type) === 'pdf')
                                                    <div>
                                                        <label class="text-[10px] font-bold text-gray-500 uppercase block mb-1">Page (PDF Optional)</label>
                                                        <input type="number" name="page_number" min="1" max="1000" placeholder="Page #" class="w-24 rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs focus:border-[#0e5c3a] focus:outline-none bg-white">
                                                    </div>
                                                @endif
                                            </div>
                                            <button type="submit" class="rounded-xl bg-[#0e5c3a] px-4 py-2 text-xs font-bold text-white hover:bg-[#0a4a2e] self-end">
                                                Post Finding
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-xs font-semibold">
                                        This document version is VOID (superseded). New comments and review decisions are disabled.
                                    </div>
                                @endif

                                <!-- Findings List -->
                                @if (isset($documentReviewComments) && $documentReviewComments->isNotEmpty())
                                    <div class="space-y-3 pt-2">
                                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-700">Findings & Annotations</h4>
                                        <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                                            @foreach ($documentReviewComments as $comm)
                                                <div class="rounded-xl border border-gray-200 bg-white p-3.5 text-xs flex flex-col gap-1.5 shadow-2xs">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center gap-2">
                                                            <span class="rounded-md px-2 py-0.5 text-[9px] font-black uppercase
                                                                @if($comm->severity === 'critical') bg-rose-100 text-rose-700 border border-rose-200
                                                                @elseif($comm->severity === 'revision') bg-amber-100 text-amber-800 border border-amber-200
                                                                @else bg-blue-50 text-blue-700 border border-blue-200 @endif">
                                                                {{ strtoupper($comm->severity) }}
                                                            </span>
                                                            @if($comm->page_number)
                                                                <span class="font-bold text-gray-500">Page {{ $comm->page_number }}</span>
                                                            @endif
                                                            <span class="font-bold text-gray-700">{{ $comm->author?->name ?? 'Adviser' }}</span>
                                                        </div>
                                                        @if ($comm->resolved_at)
                                                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                                                Resolved by {{ $comm->resolver?->name ?? 'Adviser' }}
                                                            </span>
                                                        @elseif ($selDoc->is_current)
                                                            <form method="POST" action="{{ route('adviser.documents.comments.resolve', [$selDoc, $comm]) }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit" class="text-[10px] font-bold text-emerald-700 hover:text-emerald-900 border border-emerald-300 rounded px-2 py-0.5 hover:bg-emerald-50">
                                                                    Mark Resolved
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                    <p class="text-gray-800 font-medium leading-relaxed">{{ $comm->comment }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Record Final Review Decision Form -->
                                @if ($selDoc->is_current && !in_array($selDoc->status->value, ['accepted', 'rejected', 'revision_requested'], true))
                                    <div class="space-y-3 pt-3 border-t border-gray-200">
                                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-800">Record Final Review Decision</h4>

                                        @if ($unresolvedBlocking)
                                            <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-bold flex items-center gap-2">
                                                <i class="ph ph-warning"></i>
                                                <span>Document has unresolved revision or critical findings. Accepting this document is blocked server-side until findings are resolved.</span>
                                            </div>
                                        @endif

                                        <form method="POST" action="{{ route('adviser.documents.review', $selDoc) }}" class="space-y-3">
                                            @csrf
                                            @method('PATCH')
                                            <div>
                                                <label class="text-[10px] font-bold text-gray-500 uppercase block mb-1">Decision</label>
                                                <select name="decision" class="w-full rounded-xl border border-gray-200 p-2.5 text-xs font-semibold focus:border-[#0e5c3a] focus:outline-none bg-white">
                                                    <option value="accepted" @disabled($unresolvedBlocking)>Accept Document @if($unresolvedBlocking) (Blocked: Unresolved Findings) @endif</option>
                                                    <option value="revision_requested">Revision Requested (Notes Required)</option>
                                                    <option value="rejected">Reject Document (Notes Required)</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-bold text-gray-500 uppercase block mb-1">Review Notes / Adviser Directive</label>
                                                <textarea name="review_notes" rows="3" placeholder="Provide notes or directives for the research group..." class="w-full rounded-xl border border-gray-200 p-3 text-xs focus:border-[#0e5c3a] focus:outline-none bg-white"></textarea>
                                            </div>
                                            <button type="submit" class="w-full rounded-xl bg-[#0e5c3a] py-3 text-xs font-bold text-white hover:bg-[#0a4a2e]">
                                                Submit Authoritative Decision
                                            </button>
                                        </form>
                                    </div>
                                @elseif ($selDoc->is_current && in_array($selDoc->status->value, ['accepted', 'rejected', 'revision_requested'], true))
                                    <!-- Decision Banner & Correction Button -->
                                    <div class="space-y-3 pt-3 border-t border-gray-200">
                                        <div class="p-4 rounded-xl border flex items-center justify-between gap-3
                                            @if($selDoc->status->value === 'accepted') bg-emerald-50 border-emerald-200 text-emerald-900
                                            @elseif($selDoc->status->value === 'rejected') bg-rose-50 border-rose-200 text-rose-900
                                            @else bg-amber-50 border-amber-200 text-amber-900 @endif">
                                            <div>
                                                <p class="text-[10px] font-black uppercase tracking-wider">Current Final Decision</p>
                                                <p class="text-sm font-extrabold mt-0.5">{{ \Illuminate\Support\Str::headline($selDoc->status->value) }}</p>
                                            </div>
                                            <button type="button" @click="showCorrectionModal = true" class="rounded-xl border border-gray-400 bg-white px-3.5 py-2 text-xs font-bold text-gray-800 shadow-2xs hover:bg-gray-50">
                                                Correct Decision
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                <!-- Decision History Timeline -->
                                @if (isset($selDoc->reviews) && $selDoc->reviews->isNotEmpty())
                                    <div class="space-y-2 pt-3 border-t border-gray-200">
                                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-700">Audit Review History</h4>
                                        <div class="space-y-2">
                                            @foreach ($selDoc->reviews as $rev)
                                                <div class="rounded-xl border border-gray-200 bg-white p-3 text-xs flex flex-col gap-1 {{ $rev->is_superseded ? 'opacity-60 bg-gray-50' : '' }}">
                                                    <div class="flex items-center justify-between">
                                                        <span class="font-bold text-gray-800">
                                                            {{ \Illuminate\Support\Str::headline($rev->decision) }}
                                                            @if($rev->is_superseded)
                                                                <span class="text-[9px] font-black uppercase bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded ml-1">Superseded</span>
                                                            @endif
                                                        </span>
                                                        <span class="text-[10px] text-gray-400">{{ $rev->reviewed_at?->format('M j, Y g:i A') }}</span>
                                                    </div>
                                                    <p class="text-[10px] text-gray-500">Reviewer: {{ $rev->reviewer?->name ?? 'Adviser' }}</p>
                                                    @if($rev->review_notes)
                                                        <p class="text-gray-600 italic">"{{ $rev->review_notes }}"</p>
                                                    @endif
                                                    @if($rev->correction_reason)
                                                        <p class="text-amber-800 font-semibold text-[11px] mt-0.5">Correction Reason: {{ $rev->correction_reason }}</p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Decision Correction Modal -->
                            <div x-show="showCorrectionModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                <div class="absolute inset-0 bg-black/50" @click="showCorrectionModal = false"></div>
                                <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl p-6 space-y-5 text-left" @click.stop>
                                    <div class="flex items-center justify-between border-b border-gray-150 pb-4">
                                        <div>
                                            <h3 class="font-bold text-lg text-gray-850">Correct Review Decision</h3>
                                            <p class="text-xs text-gray-500 mt-0.5">Amend an accidental decision. Original evidence is preserved in audit history.</p>
                                        </div>
                                        <button type="button" @click="showCorrectionModal = false" class="text-gray-400 hover:text-gray-600">
                                            <i class="ph ph-x text-xl"></i>
                                        </button>
                                    </div>

                                    <form method="POST" action="{{ route('adviser.documents.review.correct', $selDoc) }}" class="space-y-4">
                                        @csrf
                                        @method('PATCH')
                                        <div>
                                            <label class="text-[10px] font-bold text-gray-500 uppercase block mb-1">New Corrected Decision</label>
                                            <select name="decision" class="w-full rounded-xl border border-gray-200 p-2.5 text-xs font-semibold focus:border-[#0e5c3a] focus:outline-none bg-white">
                                                <option value="accepted" @disabled($unresolvedBlocking)>Accept Document @if($unresolvedBlocking) (Blocked: Unresolved Findings) @endif</option>
                                                <option value="revision_requested">Revision Requested</option>
                                                <option value="rejected">Reject Document</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-bold text-gray-500 uppercase block mb-1">Mandatory Correction Reason</label>
                                            <textarea name="correction_reason" rows="3" required placeholder="Explain why the prior review decision is being corrected..." class="w-full rounded-xl border border-gray-200 p-3 text-xs focus:border-[#0e5c3a] focus:outline-none bg-white"></textarea>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-bold text-gray-500 uppercase block mb-1">New Review Notes (Optional)</label>
                                            <textarea name="review_notes" rows="2" placeholder="Updated notes for research group..." class="w-full rounded-xl border border-gray-200 p-3 text-xs focus:border-[#0e5c3a] focus:outline-none bg-white"></textarea>
                                        </div>
                                        <div class="flex justify-end gap-3 pt-2">
                                            <button type="button" @click="showCorrectionModal = false" class="px-4 py-2.5 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-50">
                                                Cancel
                                            </button>
                                            <button type="submit" class="px-5 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl">
                                                Save Correction
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- TAB: Assigned Researchers -->
            <div x-show="activeTab === 'researchers'" x-cloak class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-850">Assigned Researchers</h1>
                    <p class="text-sm text-gray-500 mt-1">View students and research groups assigned to your advisership.</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                    <i class="ph ph-users-three text-4xl text-gray-300"></i>
                    <p class="mt-3 text-sm text-gray-500">Assigned researcher backend will be rebuilt in the upcoming adviser workflow phase.</p>
                </div>
            </div>

            <!-- TAB: Research Monitoring -->
            <div x-show="activeTab === 'monitoring'" x-cloak class="space-y-8 animate-fade-in">
                @if (isset($progressGroups))
                    <x-research-progress.facilitator-monitoring
                        :groups="$progressGroups"
                        :search="$progressSearch"
                        :group-status="$progressGroupStatus"
                        :group-id="$progressGroupId ?? null"
                        :all-filter-groups="$allFilterGroups ?? null"
                        :read-only="true"
                        :form-action="route('adviser.dashboard')"
                    />
                @else
                    <div class="rounded-3xl border border-gray-100 bg-white p-12 text-center text-gray-500">
                        Open Research Monitoring from the sidebar to load current advisee progress.
                    </div>
                @endif
            </div>

            <!-- TAB: Defense Endorsement -->
            <div x-show="activeTab === 'endorsement'" x-cloak class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-850">Defense Endorsement</h1>
                    <p class="text-sm text-gray-500 mt-1">Prepare and track endorsement records for proposal or final defense.</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                    <i class="ph ph-seal-check text-4xl text-gray-300"></i>
                    @forelse ($adviserDefenses ?? [] as $defense)
                        <div class="mt-3 p-4 rounded-xl border border-gray-200 bg-white text-left flex justify-between items-center">
                            <div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">{{ data_get($defense, 'defense_type_label', 'Research Defense') }}</span>
                                <h4 class="font-bold text-gray-800 text-sm mt-1">{{ data_get($defense, 'research_title', data_get($defense, 'group_name', 'Research Project')) }}</h4>
                                <p class="text-xs text-gray-500">📅 {{ data_get($defense, 'formatted_date', data_get($defense, 'starts_at', 'TBA')) }} · 📍 {{ data_get($defense, 'room_name', data_get($defense, 'room_code', 'Venue Pending')) }}</p>
                            </div>
                            <span class="text-xs font-semibold text-gray-600">{{ ucfirst(data_get($defense, 'schedule_status', 'scheduled')) }}</span>
                        </div>
                    @empty
                        <p class="mt-3 text-sm text-gray-500">No defense schedules recorded for your advisees.</p>
                    @endforelse
                </div>
            </div>

            <!-- TAB: Evaluation Records -->
            <div x-show="activeTab === 'evaluations'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Section Header & Filter Controls -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black tracking-wider uppercase bg-[#0e5c3a]/10 text-[#0e5c3a] border border-[#0e5c3a]/20">Advisee Defense Records</span>
                        </div>
                        <h1 class="text-2xl font-black font-heading text-gray-850 tracking-tight">Evaluation Records</h1>
                        <p class="text-xs text-gray-500 mt-1">Review defense evaluation rounds, score breakdowns, and panelist recommendations for your assigned research groups.</p>
                    </div>

                    <!-- Search & Filter Controls -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="relative min-w-[220px]">
                            <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input
                                type="text"
                                x-model="evaluationSearch"
                                placeholder="Search group, title, class..."
                                class="w-full pl-9 pr-3 py-2 bg-white border border-gray-200 rounded-xl text-xs text-gray-700 placeholder-gray-400 focus:outline-none focus:border-[#0e5c3a] focus:ring-1 focus:ring-[#0e5c3a] transition-all shadow-2xs"
                            >
                        </div>

                        <!-- Status Filter Pills -->
                        <div class="flex items-center p-1 bg-gray-100/80 rounded-xl border border-gray-200 text-xs font-bold text-gray-600">
                            <button
                                type="button"
                                @click="evaluationFilter = 'all'"
                                :class="evaluationFilter === 'all' ? 'bg-white text-[#0e5c3a] shadow-xs' : 'hover:text-gray-900'"
                                class="px-3 py-1.5 rounded-lg transition-all"
                            >
                                All
                            </button>
                            <button
                                type="button"
                                @click="evaluationFilter = 'released'"
                                :class="evaluationFilter === 'released' ? 'bg-white text-emerald-700 shadow-xs' : 'hover:text-gray-900'"
                                class="px-3 py-1.5 rounded-lg transition-all"
                            >
                                Released
                            </button>
                            <button
                                type="button"
                                @click="evaluationFilter = 'finalized'"
                                :class="evaluationFilter === 'finalized' ? 'bg-white text-blue-700 shadow-xs' : 'hover:text-gray-900'"
                                class="px-3 py-1.5 rounded-lg transition-all"
                            >
                                Finalized
                            </button>
                            <button
                                type="button"
                                @click="evaluationFilter = 'in_progress'"
                                :class="evaluationFilter === 'in_progress' ? 'bg-white text-amber-700 shadow-xs' : 'hover:text-gray-900'"
                                class="px-3 py-1.5 rounded-lg transition-all"
                            >
                                In Progress
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Overview Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <!-- Stat 1: Total Evaluations -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-150 shadow-xs hover:shadow-md transition-all">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Total Evaluations</span>
                                <span class="text-2xl font-black text-gray-850 mt-1 block" x-text="adviserEvaluations.length">0</span>
                            </div>
                            <span class="w-11 h-11 rounded-xl bg-emerald-50 text-[#0e5c3a] border border-emerald-100 flex items-center justify-center text-xl">
                                <i class="ph ph-clipboard-text"></i>
                            </span>
                        </div>
                        <div class="mt-3 flex items-center gap-1.5 text-[11px] text-gray-500">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                            <span>Across all assigned advisees</span>
                        </div>
                    </div>

                    <!-- Stat 2: Released Results -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-150 shadow-xs hover:shadow-md transition-all">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Released Outcomes</span>
                                <span class="text-2xl font-black text-emerald-600 mt-1 block" x-text="adviserEvaluations.filter(e => e.status === 'released').length">0</span>
                            </div>
                            <span class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xl">
                                <i class="ph ph-check-circle"></i>
                            </span>
                        </div>
                        <div class="mt-3 flex items-center gap-1.5 text-[11px] text-gray-500">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>Official results published</span>
                        </div>
                    </div>

                    <!-- Stat 3: Average Advisee Paper Score -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-150 shadow-xs hover:shadow-md transition-all">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Avg. Research Paper</span>
                                <span class="text-2xl font-black text-[#0e5c3a] mt-1 block" x-text="(() => {
                                    const withScore = adviserEvaluations.filter(e => e.summary && e.summary.research_paper_average);
                                    if (withScore.length === 0) return '—';
                                    const sum = withScore.reduce((acc, curr) => acc + parseFloat(curr.summary.research_paper_average), 0);
                                    return (sum / withScore.length).toFixed(1) + '%';
                                })()">—</span>
                            </div>
                            <span class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xl">
                                <i class="ph ph-chart-line-up"></i>
                            </span>
                        </div>
                        <div class="mt-3 flex items-center gap-1.5 text-[11px] text-gray-500">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <span>Standardized rubric average</span>
                        </div>
                    </div>

                    <!-- Stat 4: Advisee Groups -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-150 shadow-xs hover:shadow-md transition-all">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Assigned Groups</span>
                                <span class="text-2xl font-black text-gray-850 mt-1 block">{{ $assignedCount }}</span>
                            </div>
                            <span class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xl">
                                <i class="ph ph-users-three"></i>
                            </span>
                        </div>
                        <div class="mt-3 flex items-center gap-1.5 text-[11px] text-gray-500">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            <span>Active research cohorts</span>
                        </div>
                    </div>
                </div>

                <!-- Evaluation Records Cards / List -->
                <div class="space-y-4">
                    <!-- Template for dynamic filtering in Alpine -->
                    <template x-for="record in adviserEvaluations.filter(e => {
                        const matchesFilter = evaluationFilter === 'all' || e.status === evaluationFilter;
                        const search = evaluationSearch.toLowerCase().trim();
                        const matchesSearch = !search || 
                            (e.group_name && e.group_name.toLowerCase().includes(search)) ||
                            (e.research_title && e.research_title.toLowerCase().includes(search)) ||
                            (e.defense_type && e.defense_type.toLowerCase().includes(search)) ||
                            (e.class_name && e.class_name.toLowerCase().includes(search));
                        return matchesFilter && matchesSearch;
                    })" :key="record.id">
                        <div class="bg-white rounded-2xl border border-gray-150 shadow-xs hover:shadow-md transition-all p-6 space-y-5">
                            <!-- Card Header -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <span
                                        class="px-3 py-1 rounded-full text-[11px] font-black tracking-wider uppercase"
                                        :class="record.defense_type === 'final' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-emerald-50 text-[#0e5c3a] border border-emerald-200'"
                                        x-text="record.defense_type ? record.defense_type.toUpperCase() + ' DEFENSE' : 'DEFENSE EVALUATION'"
                                    ></span>

                                    <span
                                        class="px-3 py-1 rounded-full text-[11px] font-black tracking-wider uppercase"
                                        :class="{
                                            'bg-emerald-100 text-emerald-800 border border-emerald-300': record.status === 'released',
                                            'bg-blue-50 text-blue-700 border border-blue-200': record.status === 'finalized' || record.status === 'complete',
                                            'bg-amber-50 text-amber-700 border border-amber-200': record.status === 'in_progress',
                                            'bg-gray-100 text-gray-600 border border-gray-200': record.status === 'pending'
                                        }"
                                        x-text="record.status ? record.status.replace('_', ' ').toUpperCase() : 'PENDING'"
                                    ></span>

                                    <span x-show="record.class_name" class="px-2.5 py-0.5 rounded-lg bg-gray-100 text-gray-600 text-[10px] font-bold" x-text="record.class_name"></span>
                                </div>

                                <div class="flex items-center gap-4 text-xs text-gray-500 font-medium">
                                    <span x-show="record.scheduled_at" class="flex items-center gap-1.5">
                                        <i class="ph ph-calendar text-[#0e5c3a]"></i>
                                        <span x-text="new Date(record.scheduled_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })"></span>
                                    </span>
                                    <span x-show="record.room_name" class="flex items-center gap-1.5">
                                        <i class="ph ph-map-pin text-[#0e5c3a]"></i>
                                        <span x-text="record.room_name"></span>
                                    </span>
                                </div>
                            </div>

                            <!-- Research Info & Scores Grid -->
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                                <!-- Left Details: Title & Group -->
                                <div class="lg:col-span-7 space-y-3">
                                    <div>
                                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block" x-text="record.group_name || 'Advisee Group'"></span>
                                        <h3 class="text-base font-bold text-gray-850 font-heading leading-snug mt-0.5" x-text="record.research_title || 'Untitled Research'"></h3>
                                    </div>

                                    <!-- Panel Members Chips -->
                                    <div x-show="record.panelists && record.panelists.length > 0" class="pt-1">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1.5">Evaluation Panel:</span>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="panelist in record.panelists" :key="panelist.name">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-50 border border-gray-200/80 text-[11px] font-medium text-gray-700">
                                                    <i class="ph" :class="panelist.has_submitted ? 'ph-check-circle text-emerald-600' : 'ph-clock text-amber-500'"></i>
                                                    <span x-text="panelist.name"></span>
                                                    <span x-show="panelist.position" class="text-[9px] uppercase font-bold text-gray-400" x-text="'(' + panelist.position + ')'"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Key Recommendations Box -->
                                    <div x-show="record.recommendations && record.recommendations.length > 0" class="p-3.5 rounded-xl bg-amber-50/60 border border-amber-200/70 text-xs text-amber-900 space-y-1.5">
                                        <div class="flex items-center gap-2 font-bold text-[11px] text-amber-800 uppercase tracking-wider">
                                            <i class="ph ph-chat-teardrop-text text-sm"></i>
                                            <span>Panelist Recommendations &amp; Guidance</span>
                                        </div>
                                        <template x-for="(rec, idx) in record.recommendations.slice(0, 2)" :key="idx">
                                            <p class="text-[11px] leading-relaxed text-amber-900/90 pl-3 border-l-2 border-amber-400">
                                                <span class="font-bold" x-text="rec.panelist_name + ': '"></span>
                                                <span x-text="rec.recommendations || rec.general_comments"></span>
                                            </p>
                                        </template>
                                    </div>
                                </div>

                                <!-- Right Details: Standardized Rubric Scores -->
                                <div class="lg:col-span-5 bg-gray-50/80 rounded-xl p-4 border border-gray-150 space-y-3.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-gray-700">Scorecard Summary</span>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">RES-036 / 037</span>
                                    </div>

                                    <!-- Research Paper Average Score -->
                                    <div class="p-3 rounded-xl bg-white border border-gray-150 flex items-center justify-between">
                                        <div>
                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Research Paper Average</span>
                                            <span class="text-xs font-semibold text-gray-600">Weighted Rubric Score</span>
                                        </div>
                                        <span
                                            class="text-lg font-black"
                                            :class="record.summary && record.summary.research_paper_average ? 'text-[#0e5c3a]' : 'text-gray-400'"
                                            x-text="record.summary && record.summary.research_paper_average ? record.summary.research_paper_average + '%' : 'Pending'"
                                        ></span>
                                    </div>

                                    <!-- Individual Student Presentation Averages -->
                                    <div x-show="record.summary && record.summary.student_summaries && record.summary.student_summaries.length > 0" class="space-y-1.5">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Member Presentation Scores:</span>
                                        <div class="space-y-1 max-h-32 overflow-y-auto pr-1">
                                            <template x-for="student in (record.summary ? record.summary.student_summaries : [])" :key="student.student_id">
                                                <div class="flex items-center justify-between text-xs py-1 px-2 rounded-lg bg-white/80 border border-gray-100">
                                                    <span class="text-gray-700 font-medium truncate pr-2" x-text="student.student_name"></span>
                                                    <span class="font-bold text-[#0e5c3a] shrink-0" x-text="student.presentation_average ? student.presentation_average + '%' : '—'"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Modal Trigger -->
                                    <button
                                        type="button"
                                        @click="selectedEvaluation = record"
                                        class="w-full py-2 bg-white hover:bg-[#0e5c3a] text-[#0e5c3a] hover:text-white border border-[#0e5c3a]/30 font-bold text-xs rounded-xl shadow-2xs transition-all flex items-center justify-center gap-2 cursor-pointer"
                                    >
                                        <i class="ph ph-eye text-sm"></i>
                                        <span>View Full Evaluation Breakdown</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty State when filtered or no records -->
                    <div x-show="adviserEvaluations.filter(e => {
                        const matchesFilter = evaluationFilter === 'all' || e.status === evaluationFilter;
                        const search = evaluationSearch.toLowerCase().trim();
                        return matchesFilter && (!search || (e.group_name && e.group_name.toLowerCase().includes(search)) || (e.research_title && e.research_title.toLowerCase().includes(search)));
                    }).length === 0" class="bg-white rounded-2xl border border-gray-150 shadow-xs p-12 text-center space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-[#0e5c3a] border border-emerald-100 flex items-center justify-center text-3xl mx-auto shadow-sm">
                            <i class="ph ph-clipboard-text"></i>
                        </div>
                        <div class="max-w-md mx-auto space-y-1.5">
                            <h3 class="text-base font-bold text-gray-850 font-heading">No Evaluation Records Available</h3>
                            <p class="text-xs text-gray-500 leading-relaxed">
                                Defense evaluation records and panelist recommendations will appear here once your assigned research groups undergo their scheduled defenses and scores are finalized.
                            </p>
                        </div>
                        <div class="pt-2 flex justify-center gap-3">
                            <button
                                type="button"
                                @click="activeTab = 'endorsement'"
                                class="px-4 py-2 bg-[#0e5c3a] hover:bg-[#09472d] text-white text-xs font-bold rounded-xl shadow-sm transition-all cursor-pointer"
                            >
                                Check Defense Endorsements
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'classes'"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all cursor-pointer"
                            >
                                View My Classes
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Evaluation Details Modal Dialog -->
                <div x-show="selectedEvaluation" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="selectedEvaluation = null"></div>

                    <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-white/40 my-8 max-h-[90vh] flex flex-col" @click.stop>
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between px-7 py-5 bg-gradient-to-r from-[#09472d] to-[#0e5c3a] text-white shrink-0">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-[#eebc3f]">Advisee Evaluation Breakdown</span>
                                <h2 class="text-lg font-bold font-heading mt-0.5" x-text="selectedEvaluation?.research_title || 'Evaluation Record'"></h2>
                            </div>
                            <button type="button" @click="selectedEvaluation = null" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all cursor-pointer">
                                <i class="ph ph-x text-base"></i>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6 md:p-7 overflow-y-auto space-y-6 flex-grow">
                            <!-- Quick Meta Badges -->
                            <div class="flex flex-wrap gap-2.5 text-xs">
                                <span class="px-3 py-1 rounded-full font-bold bg-emerald-50 text-[#0e5c3a] border border-emerald-200" x-text="selectedEvaluation?.defense_type ? selectedEvaluation.defense_type.toUpperCase() + ' DEFENSE' : ''"></span>
                                <span class="px-3 py-1 rounded-full font-bold bg-gray-100 text-gray-700" x-text="'Group: ' + (selectedEvaluation?.group_name || 'N/A')"></span>
                                <span x-show="selectedEvaluation?.class_name" class="px-3 py-1 rounded-full font-bold bg-gray-100 text-gray-700" x-text="'Class: ' + selectedEvaluation?.class_name"></span>
                                <span x-show="selectedEvaluation?.scheduled_at" class="px-3 py-1 rounded-full font-bold bg-gray-100 text-gray-700" x-text="'Date: ' + (selectedEvaluation ? new Date(selectedEvaluation.scheduled_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '')"></span>
                            </div>

                            <!-- Score Card Details -->
                            <div class="rounded-2xl p-5 bg-gray-50 border border-gray-200 space-y-4">
                                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Authoritative Rubric Summary (RES-037)</h4>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="p-4 rounded-xl bg-white border border-gray-150">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Research Paper Grade (70%)</span>
                                        <span class="text-2xl font-black text-[#0e5c3a] mt-1 block" x-text="selectedEvaluation?.summary?.research_paper_average ? selectedEvaluation.summary.research_paper_average + '%' : 'Pending'"></span>
                                        <span class="text-[10px] text-gray-400 mt-1 block">Evaluates Quality, Originality &amp; Relevance</span>
                                    </div>
                                    <div class="p-4 rounded-xl bg-white border border-gray-150">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Evaluation Status</span>
                                        <span class="text-2xl font-black text-emerald-600 mt-1 block" x-text="selectedEvaluation?.status ? selectedEvaluation.status.replace('_', ' ').toUpperCase() : 'PENDING'"></span>
                                        <span class="text-[10px] text-gray-400 mt-1 block">Released by Research Facilitator</span>
                                    </div>
                                </div>

                                <!-- Student Individual Presentation Scores -->
                                <div x-show="selectedEvaluation?.summary?.student_summaries && selectedEvaluation?.summary?.student_summaries.length > 0" class="space-y-2 pt-2">
                                    <span class="text-[11px] font-bold text-gray-700 block">Individual Presentation Scores (30%):</span>
                                    <div class="space-y-1.5">
                                        <template x-for="student in (selectedEvaluation?.summary?.student_summaries || [])" :key="student.student_id">
                                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-white border border-gray-150 text-xs">
                                                <div class="flex items-center gap-2">
                                                    <i class="ph ph-user text-gray-400"></i>
                                                    <span class="font-bold text-gray-800" x-text="student.student_name"></span>
                                                </div>
                                                <span class="font-black text-[#0e5c3a]" x-text="student.presentation_average ? student.presentation_average + '%' : '—'"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel Roster & Detailed Remarks -->
                            <div class="space-y-3">
                                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Panelist Feedback &amp; Specific Recommendations</h4>

                                <template x-if="selectedEvaluation?.recommendations && selectedEvaluation?.recommendations.length > 0">
                                    <div class="space-y-3">
                                        <template x-for="(rec, idx) in selectedEvaluation.recommendations" :key="idx">
                                            <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200 text-xs space-y-1">
                                                <div class="flex items-center justify-between text-amber-900 font-bold">
                                                    <span x-text="rec.panelist_name"></span>
                                                    <span class="text-[10px] uppercase text-amber-700">Panel Feedback</span>
                                                </div>
                                                <p class="text-gray-700 leading-relaxed pt-1" x-text="rec.recommendations || rec.general_comments"></p>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="!selectedEvaluation?.recommendations || selectedEvaluation.recommendations.length === 0">
                                    <div class="p-4 rounded-xl bg-gray-50 border border-gray-150 text-center text-xs text-gray-500">
                                        No specific written remarks recorded for this evaluation round.
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-7 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-3 shrink-0">
                            <button
                                type="button"
                                @click="selectedEvaluation = null"
                                class="px-5 py-2.5 bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 font-bold text-xs rounded-xl shadow-2xs transition-all cursor-pointer"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Official Research Forms (UI only; persistence begins in Phase 19 backend work) -->
            <section x-show="activeTab === 'forms'" x-cloak class="space-y-8">
                @include('pages.adviser.forms.index')
            </section>

            <!-- TAB: Notifications Center -->
            <div x-show="activeTab === 'notifications'" x-cloak class="space-y-8 animate-fade-in">
                <x-notifications.center
                    :notifications="$userNotifications ?? collect()"
                    :unread-count="$userUnreadCount ?? 0"
                    :filter="$notificationFilter ?? 'all'"
                    :dashboard-route="route('adviser.dashboard')"
                />
            </div>

            <!-- TAB: Settings -->
            <div x-show="activeTab === 'settings'" x-cloak class="space-y-8 animate-fade-in">
                @include('partials.settings')
            </div>
        </div>
    </main>
</div>
@endsection
