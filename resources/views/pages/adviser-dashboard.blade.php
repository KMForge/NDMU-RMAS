@extends('layouts.blank')

@php
    $allowedTabs = ['dashboard', 'classes', 'consultation', 'docreview', 'revisions', 'repository', 'forms', 'notifications', 'settings', 'researchers', 'proposal', 'monitoring', 'endorsement', 'evaluations'];
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
    notificationsFilter: 'all',
    showClassModal: @js($showClassModal),
    showConsultationModal: false,
    showRepositoryUploadModal: @js($errors->has('document') && $initialTab === 'repository'),
    selectedNotification: null,
    notifications: @js($adviserNotifications),
    assignedResearchers: @js($adviserOverviewAdvisees),
    confirmingAcceptId: null,
    confirmingDeclineId: null
}">
    <!-- SIDEBAR NAV -->
    <aside class="fixed inset-y-0 left-0 w-72 bg-[#0e5c3a] text-white flex flex-col justify-between z-20 border-r border-white/5 overflow-y-auto">
        <div class="flex-shrink-0">
            <!-- Brand Logo Header -->
            <div class="p-6 border-b border-white/10 flex items-center gap-3">
                <div class="p-1 bg-white/10 rounded-xl border border-white/20">
                    <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="NDMU Logo" width="96" height="96" class="h-10 w-auto">
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-heading font-extrabold text-xl tracking-tight">NDMU</span>
                    <span class="text-[9px] text-[#eebc3f] font-bold tracking-wider uppercase mt-1">Research Management</span>
                </div>
            </div>

            <!-- Profile Info Header -->
            <div class="px-6 py-5 border-b border-white/10 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-[#eebc3f] text-[#0e5c3a] font-bold text-lg flex items-center justify-center">
                    {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($adviser->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-sm truncate">{{ $adviser->name }}</p>
                    <p class="text-[10px] text-white/60">Research Adviser</p>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-grow px-6 py-4 space-y-6">
            <div class="space-y-1.5">
                <span class="text-[10px] font-bold tracking-wider text-[#a5c1a0] uppercase px-3 block mb-2">Navigation</span>

                <!-- Dashboard -->
                <a 
                   href="{{ route('adviser.dashboard', ['tab' => 'dashboard']) }}"
                   wire:navigate
                   :class="activeTab === 'dashboard' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-squares-four text-lg"></i>
                        <span>Dashboard</span>
                    </div>
                    <span x-show="activeTab === 'dashboard'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>
                
                <!-- My Classes -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'classes']) }}"
                   wire:navigate
                   :class="activeTab === 'classes' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-book text-lg"></i>
                        <span>My Classes</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($pendingCount > 0)
                            <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-rose-500 text-white shadow-xs">
                                {{ $pendingCount }}
                            </span>
                        @endif
                        <span x-show="activeTab === 'classes'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                    </div>
                </a>

                <!-- Assigned Researchers -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'researchers']) }}"
                   wire:navigate
                   :class="activeTab === 'researchers' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-users-three text-lg"></i>
                        <span>Assigned Researchers</span>
                    </div>
                    <span x-show="activeTab === 'researchers'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <!-- Proposal Review -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'proposal']) }}"
                   wire:navigate
                   :class="activeTab === 'proposal' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-magnifying-glass text-lg"></i>
                        <span>Proposal Review</span>
                    </div>
                    <span x-show="activeTab === 'proposal'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <!-- Research Monitoring -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'monitoring']) }}"
                   wire:navigate
                   :class="activeTab === 'monitoring' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chart-line-up text-lg"></i>
                        <span>Research Monitoring</span>
                    </div>
                    <span x-show="activeTab === 'monitoring'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <!-- Document Review -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'docreview']) }}"
                   wire:navigate
                   :class="activeTab === 'docreview' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-text text-lg"></i>
                        <span>Document Review</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if (($pendingDocReviewsCount ?? 0) > 0)
                            <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-amber-400 text-amber-950 shadow-xs">
                                {{ $pendingDocReviewsCount }}
                            </span>
                        @endif
                        <span x-show="activeTab === 'docreview'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                    </div>
                </a>

                <!-- Consultations -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'consultation']) }}"
                   wire:navigate
                   :class="activeTab === 'consultation' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chats-teardrop text-lg"></i>
                        <span>Consultations</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if (($pendingConsultationsCount ?? 0) > 0)
                            <span class="min-w-5 rounded-full bg-red-500 px-1.5 py-0.5 text-center text-[9px] font-bold text-white">
                                {{ $pendingConsultationsCount }}
                            </span>
                        @endif
                        <span x-show="activeTab === 'consultation'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                    </div>
                </a>

                <!-- Revisions -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'revisions']) }}"
                   wire:navigate
                   :class="activeTab === 'revisions' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-arrows-counter-clockwise text-lg"></i>
                        <span>Revision Tracker</span>
                    </div>
                    <span x-show="activeTab === 'revisions'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <!-- Defense Endorsement -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'endorsement']) }}"
                   wire:navigate
                   :class="activeTab === 'endorsement' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-seal-check text-lg"></i>
                        <span>Defense Endorsement</span>
                    </div>
                    <span x-show="activeTab === 'endorsement'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <!-- Evaluation Records -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'evaluations']) }}"
                   wire:navigate
                   :class="activeTab === 'evaluations' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-clipboard-text text-lg"></i>
                        <span>Evaluation Records</span>
                    </div>
                    <span x-show="activeTab === 'evaluations'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <!-- Research Repository -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'repository']) }}"
                   wire:navigate
                   :class="activeTab === 'repository' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-archive text-lg"></i>
                        <span>Research Repository</span>
                    </div>
                    <span x-show="activeTab === 'repository'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                <!-- Pending Form Approvals Queue -->
                <a
                   href="{{ route('official-forms.workspace.index') }}"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer text-white/90 hover:text-white hover:bg-white/5 font-semibold">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-check-square-offset text-lg text-amber-300"></i>
                        <span>Pending Form Approvals</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if (isset($pendingFormInstances) && $pendingFormInstances->count() > 0)
                            <span class="min-w-5 rounded-full bg-red-500 px-1.5 py-0.5 text-center text-[10px] font-black text-white shadow-sm">{{ $pendingFormInstances->count() }}</span>
                        @endif
                    </div>
                </a>
            </div>

            <!-- Official Forms Section -->
            <div class="space-y-1.5 pt-4 border-t border-white/10">
                <span class="text-[10px] font-bold tracking-wider text-[#a5c1a0] uppercase px-3 block mb-2">Research Forms</span>
                <button 
                   type="button" 
                   @click="formsExpanded = !formsExpanded"
                   :class="(activeTab === 'forms' || formsExpanded) ? 'bg-white/10 text-white font-bold' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-pdf text-lg"></i>
                        <span>Official Forms</span>
                    </div>
                    <i class="ph text-xs transition-transform duration-200" :class="formsExpanded ? 'ph-caret-up' : 'ph-caret-down'"></i>
                </button>

                <div x-show="formsExpanded" x-cloak x-transition class="mt-1 space-y-0.5">
                    @foreach ($officialFormPhases as $phase => $label)
                        @php
                            $phaseForms = $officialFormsByPhase->get($phase, collect());
                        @endphp

                        @if ($phaseForms->isNotEmpty())
                            <div>
                            <button
                                type="button"
                                @click="activeTab = 'forms'; activeFormPhase = activeFormPhase === '{{ $phase }}' ? null : '{{ $phase }}'"
                                class="w-full flex items-center justify-between gap-2 rounded-xl py-2 pl-4 pr-3 text-left text-[11px] font-semibold text-white/55 transition-colors duration-200 hover:bg-white/5 hover:text-white"
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
                                x-transition:enter-start="-translate-y-1 opacity-0"
                                x-transition:enter-end="translate-y-0 opacity-100"
                                class="mt-0.5 space-y-0.5 pl-2"
                            >
                                @foreach ($phaseForms as $code => $form)
                                    <button
                                        type="button"
                                        @click="activeTab = 'forms'; activeOfficialForm = '{{ $code }}'"
                                        :class="activeOfficialForm === '{{ $code }}' ? 'bg-[#eebc3f] text-[#0e5c3a] ring-1 ring-white font-bold' : 'text-white/70 hover:text-white hover:bg-white/5'"
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
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <!-- User Logout Footer -->
        <div class="flex-shrink-0 px-6 pb-6 mt-8">
            <div class="pt-4 border-t border-white/10 space-y-1">
                <!-- Notifications -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'notifications']) }}"
                   wire:navigate
                   :class="activeTab === 'notifications' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-bell text-lg"></i>
                        <span>Notifications</span>
                    </div>
                    <span x-show="activeTab === 'notifications'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <!-- System Settings -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'settings']) }}"
                   wire:navigate
                   :class="activeTab === 'settings' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-gear text-lg"></i>
                        <span>Settings</span>
                    </div>
                    <span x-show="activeTab === 'settings'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>
            </div>

            <form method="POST" action="{{ route('logout') }}" data-confirm-logout>
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 mt-1 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all cursor-pointer">
                    <i class="ph ph-sign-out text-lg"></i>
                    <span>Logout</span>
                </button>
            </form>
            <div class="text-[9px] text-white/30 text-center font-medium mt-6">
                NDMU © {{ now()->year }} - v1.0
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <main class="flex-1 min-w-0 flex flex-col h-screen overflow-y-auto pl-72">
        <!-- Top Sticky Header -->
        <header class="h-20 bg-white border-b border-gray-150 px-8 flex items-center justify-between sticky top-0 z-10 shrink-0">
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Research Adviser</span>
                <span class="text-gray-300">/</span>
                <span class="text-xs font-extrabold text-[#0e5c3a] uppercase tracking-wider">Workspace</span>
            </div>

            <div class="flex items-center gap-4">
                <x-workspace-switcher current="adviser" />
                <span class="text-xs font-bold text-gray-700">{{ $adviser->name }}</span>
            </div>
        </header>

        <div class="p-8 space-y-8 flex-1">
            <!-- Green Hero Banner Component -->
            <x-portal-feature-banner class="mb-8" :sections="[
                'dashboard' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Adviser Dashboard', 'description' => 'Review adviser requests, assigned groups, account status, and research-advising activity.', 'icon' => 'ph-squares-four'],
                'classes' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'My Classes Workspace', 'description' => 'Review pending adviser invitations and manage your assigned research groups.', 'icon' => 'ph-chalkboard-teacher'],
                'researchers' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Assigned Researchers', 'description' => 'View students and groups assigned to you for research advising.', 'icon' => 'ph-users-three'],
                'proposal' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Proposal Review', 'description' => 'Review proposal submissions and prepare feedback for assigned researchers.', 'icon' => 'ph-file-magnifying-glass'],
                'monitoring' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Research Monitoring', 'description' => 'Track advisee progress, milestones, and research activity.', 'icon' => 'ph-chart-line-up'],
                'docreview' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Document Review System', 'description' => 'Review and annotate documents submitted by your assigned researchers.', 'icon' => 'ph-file-text'],
                'consultation' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Consultation Records', 'description' => 'Manage student consultation bookings and record consultation outcomes.', 'icon' => 'ph-chats-teardrop'],
                'revisions' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Revision Tracker', 'description' => 'Monitor requested manuscript revisions and resubmissions.', 'icon' => 'ph-arrows-counter-clockwise'],
                'endorsement' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Defense Endorsement', 'description' => 'Prepare and monitor defense endorsement requests for your advisees.', 'icon' => 'ph-seal-check'],
                'evaluations' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Evaluation Records', 'description' => 'Review defense and research evaluation records for assigned groups.', 'icon' => 'ph-clipboard-text'],
                'repository' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Research Repository', 'description' => 'Browse approved research documents, manuscripts, and archives.', 'icon' => 'ph-archive'],
                'forms' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Official Research Forms', 'description' => 'Open the official forms required for adviser participation across the seven research phases.', 'icon' => 'ph-file-text'],
                'notifications' => ['eyebrow' => 'Research Adviser Portal', 'title' => 'Notifications', 'description' => 'Stay updated with real-time research activity alerts and reminders.', 'icon' => 'ph-bell'],
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
            <div x-show="activeTab === 'dashboard'" class="space-y-8">
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-850">Welcome back, {{ $adviser->name }}</h1>
                    <p class="text-xs text-gray-500 mt-1">Research Adviser Dashboard Overview</p>
                </div>
                <!-- Metrics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-2">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Pending Requests</p>
                        <p class="text-2xl font-black text-gray-850">{{ $pendingCount }}</p>
                        <p class="text-[10px] text-gray-500">Awaiting your response</p>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-2">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Assigned Groups</p>
                        <p class="text-2xl font-black text-gray-850">{{ $assignedCount }}</p>
                        <p class="text-[10px] text-gray-500">Active research groups</p>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-2">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Role Identity</p>
                        <p class="text-sm font-bold text-[#0e5c3a]">Thesis Adviser</p>
                        <p class="text-[10px] text-gray-500">Verified Faculty</p>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-2">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Account Status</p>
                        <p class="text-sm font-bold text-emerald-600">Active & Approved</p>
                        <p class="text-[10px] text-gray-500">Full platform access</p>
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
                                    @php($isSelected = isset($selectedReviewDocument) && $selectedReviewDocument->id === $qDoc->id)
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
                            @php($selDoc = $selectedReviewDocument)
                            @php($unresolvedBlocking = isset($documentReviewComments) && $documentReviewComments->whereNull('resolved_at')->whereIn('severity', ['revision', 'critical'])->count() > 0)

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

            <!-- TAB: Proposal Review -->
            <div x-show="activeTab === 'proposal'" x-cloak class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-850">Proposal Review</h1>
                    <p class="text-sm text-gray-500 mt-1">Review research proposals submitted by your assigned researchers.</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                    <i class="ph ph-file-magnifying-glass text-4xl text-gray-300"></i>
                    <p class="mt-3 text-sm text-gray-500">Proposal review backend is not connected yet.</p>
                </div>
            </div>

            <!-- TAB: Research Monitoring -->
            <div x-show="activeTab === 'monitoring'" x-cloak class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-850">Research Monitoring</h1>
                    <p class="text-sm text-gray-500 mt-1">Track progress and milestone movement for assigned research groups.</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                    <i class="ph ph-chart-line-up text-4xl text-gray-300"></i>
                    @forelse ($adviserProgressGroups as $group)
                        @php($summary = $group->progress_summary)
                        <article class="mt-4 rounded-2xl border border-gray-200 p-5">
                            <div class="flex justify-between gap-4">
                                <div>
                                    <h3 class="font-bold text-gray-900">{{ $group->research_title ?: $group->name }}</h3>
                                    <p class="text-xs text-gray-500">{{ $group->researchClass?->name }} · Read-only adviser view</p>
                                </div>
                                <strong class="text-2xl text-emerald-700">{{ number_format($summary['progress_percentage'], 0) }}%</strong>
                            </div>
                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full bg-emerald-600" style="width: {{ $summary['progress_percentage'] }}%"></div></div>
                            <div class="mt-4 grid gap-2 md:grid-cols-2">
                                @foreach ($summary['milestones'] as $milestone)
                                    <div class="rounded-xl border border-gray-100 p-3 text-xs">
                                        <span class="font-bold">{{ $milestone->definition->sequence }}. {{ $milestone->definition->name }}</span>
                                        <span class="block text-gray-500 mt-1">{{ $milestone->status->label() }}@if($milestone->isOverdue()) · Overdue @endif</span>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @empty
                        <p class="mt-3 text-sm text-gray-500">No assigned research groups are available.</p>
                    @endforelse
                </div>
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
            <div x-show="activeTab === 'evaluations'" x-cloak class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-850">Evaluation Records</h1>
                    <p class="text-sm text-gray-500 mt-1">View evaluation outcomes related to your assigned research groups.</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                    <i class="ph ph-clipboard-text text-4xl text-gray-300"></i>
                    <p class="mt-3 text-sm text-gray-500">Evaluation records backend will be rebuilt in the evaluation phase.</p>
                </div>
            </div>

            <!-- TAB: Official Research Forms (UI only; persistence begins in Phase 19 backend work) -->
            <section x-show="activeTab === 'forms'" x-cloak class="space-y-8">
                @include('pages.adviser.forms.index')
            </section>
        </div>
    </main>
</div>
@endsection
