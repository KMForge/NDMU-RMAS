@extends('layouts.blank')

@php
    $keywords = [];

    if ($researchProject?->keywords) {
        $keywords = is_array($researchProject->keywords)
            ? $researchProject->keywords
            : (json_decode($researchProject->keywords, true) ?: []);
    }

    $progressPercentage = $dashboardOverview['progress_percentage'];
    $nextConsultation = $dashboardOverview['next_consultation'];
    $nextDefense = $dashboardOverview['next_defense'];
    $nextAction = $dashboardOverview['action_items']->first();
    $firstName = \Illuminate\Support\Str::before($student->name, ' ');
    $allowedTabs = ['dashboard', 'classes', 'research', 'proposal', 'progress', 'consultation', 'revisions', 'defense', 'evaluations', 'repository', 'forms', 'notifications', 'settings'];
    $initialTab = $activeDashboardTab ?? (in_array(request()->query('tab'), $allowedTabs, true) ? request()->query('tab') : 'dashboard');
    $showConsultationModal = request()->boolean('book') || $errors->hasAny(['consultation', 'request_token', 'preferred_at', 'consultation_mode', 'agenda']);
    $showJoinClassModal = $errors->hasAny(['class', 'join_code']);
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
@endphp

@section('content')
<style>
    [x-cloak] { display: none !important; }
    /* Match the scroll behavior and appearance used by the other role sidebars. */
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

<div
    class="min-h-screen flex font-sans bg-[#f4f7f6]"
    x-data="{
        activeTab: @js($initialTab),
        activeFormPhase: @js($initialFormPhase),
        activeOfficialForm: @js($initialOfficialForm),
        officialForms: @js($officialForms),
        researchProgress: @js($progressPercentage),
        formsExpanded: @js($initialTab === 'forms'),
        showConsultationModal: @js($showConsultationModal),
        showJoinClassModal: @js($showJoinClassModal),
        dashboardUrl: @js(route('student.dashboard'))
    }"
    x-init="$watch('activeTab', (tab, previousTab) => {
        if (tab === previousTab) return;

        const url = new URL(dashboardUrl, window.location.origin);
        url.searchParams.set('tab', tab);

        window.Livewire?.navigate
            ? window.Livewire.navigate(url.toString())
            : window.location.assign(url.toString());
    })"
>
    <aside class="fixed inset-y-0 left-0 w-72 bg-[#0e5c3a] text-white flex flex-col justify-between z-20 border-r border-white/5 overflow-y-auto">
        <div class="flex-shrink-0">
            <div class="flex items-center gap-3 p-6 border-b border-white/10">
                <div class="p-1 bg-white/10 rounded-xl border border-white/20">
                    <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="NDMU Logo" width="96" height="96" class="h-10 w-auto">
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-heading font-extrabold text-xl tracking-tight">NDMU</span>
                    <span class="text-[9px] font-bold text-[#eebc3f] tracking-wider uppercase mt-1">Research Management</span>
                </div>
            </div>

            <div class="flex items-center gap-3 px-6 py-5 border-b border-white/10">
                <div class="w-10 h-10 rounded-full bg-[#eebc3f] text-[#0e5c3a] font-bold flex items-center justify-center text-lg">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($student->name, 0, 1)) }}
                </div>
                <div class="flex flex-col leading-tight overflow-hidden">
                    <span class="font-semibold text-sm truncate">{{ $student->name }}</span>
                    <span class="text-[10px] text-white/60 font-medium mt-0.5">Student Researcher</span>
                </div>
            </div>
        </div>

        <div class="flex-grow px-6 py-4 space-y-6">
            <div class="space-y-1.5">
                <span class="text-[10px] font-bold tracking-wider text-[#a5c1a0] uppercase px-3 block mb-2">Navigation</span>

                @foreach ([
                    'dashboard' => ['ph-squares-four', 'Dashboard'],
                    'classes' => ['ph-users', 'My Classes'],
                    'research' => ['ph-book-open', 'My Research'],
                    'proposal' => ['ph-file-text', 'Research Proposal'],
                    'progress' => ['ph-chart-line-up', 'Research Progress'],
                    'consultation' => ['ph-chat-teardrop', 'Consultation Records'],
                    'revisions' => ['ph-note-pencil', 'Revision Tracker'],
                    'defense' => ['ph-calendar', 'My Defense Schedule'],
                    'evaluations' => ['ph-exam', 'Evaluation Results'],
                    'repository' => ['ph-folder', 'Research Repository'],
                ] as $tab => [$icon, $label])
                    <a
                        href="{{ route('student.dashboard', ['tab' => $tab]) }}"
                        wire:navigate
                        :class="activeTab === '{{ $tab }}' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer"
                    >
                        <i class="ph {{ $icon }} text-lg"></i>
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
            </div>

            <div class="space-y-1.5 pt-4 mt-4 border-t border-white/10">
                <span class="text-[10px] font-bold tracking-wider text-[#a5c1a0] uppercase px-3 block mb-2">Research Forms</span>
                <button
                    type="button"
                    @click="formsExpanded = ! formsExpanded; activeTab = 'forms'"
                    :class="activeTab === 'forms' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                    :aria-expanded="formsExpanded"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-pdf text-lg"></i>
                        <span>Official Forms</span>
                    </div>
                    <i class="ph ph-caret-right text-xs transition-transform duration-200" :class="formsExpanded && 'rotate-90'"></i>
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
                                class="w-full flex items-center justify-between gap-2 py-2 pl-4 pr-3 rounded-xl text-white/55 hover:text-white hover:bg-white/5 transition-colors duration-200 text-[11px] font-semibold text-left"
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
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex-shrink-0 px-6 pb-6 mt-8">
            <div class="pt-4 border-t border-white/10 space-y-1">
                <a
                    href="{{ route('student.dashboard', ['tab' => 'notifications']) }}"
                    wire:navigate
                    :class="activeTab === 'notifications' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold' : 'text-white/90 hover:bg-white/5 font-semibold'"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] text-left transition-all"
                >
                    <i class="ph ph-bell text-lg"></i>
                    <span>Notifications</span>
                </a>
                <a
                    href="{{ route('student.dashboard', ['tab' => 'settings']) }}"
                    wire:navigate
                    :class="activeTab === 'settings' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold' : 'text-white/90 hover:bg-white/5 font-semibold'"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] text-left transition-all"
                >
                    <i class="ph ph-gear text-lg"></i>
                    <span>Settings</span>
                </a>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 mt-1 rounded-xl text-white/90 hover:bg-white/5 font-semibold text-[13px]">
                    <i class="ph ph-sign-out text-lg"></i>
                    <span>Logout</span>
                </button>
            </form>
            <div class="text-[9px] text-white/30 text-center font-medium mt-6">
                NDMU © {{ now()->year }} - v1.0
            </div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-h-screen pl-72">
        <header class="h-20 bg-white border-b border-gray-150 px-8 flex items-center justify-between sticky top-0 z-10">
            <form method="GET" action="{{ route('student.dashboard') }}" class="relative w-full max-w-2xl">
                <input type="hidden" name="tab" value="dashboard">
                <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input
                    type="search"
                    name="dashboard_q"
                    value="{{ $dashboardSearchQuery }}"
                    maxlength="100"
                    placeholder="Search research, documents, classes, or tasks..."
                    class="w-full h-11 pl-11 pr-4 rounded-2xl border border-gray-200 bg-gray-50 text-xs text-gray-700 focus:outline-none focus:bg-white focus:border-[#0e5c3a]"
                >
            </form>
            <div class="flex items-center gap-3">
                <button type="button" @click="activeTab = 'notifications'" class="w-9 h-9 rounded-full hover:bg-gray-50 text-gray-500 flex items-center justify-center relative">
                    <i class="ph ph-bell text-lg"></i>
                    @if ($notifications->whereNull('read_at')->isNotEmpty())
                        <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-red-500 border border-white"></span>
                    @endif
                </button>
                <div class="w-8 h-8 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-xs">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($student->name, 0, 1)) }}
                </div>
                <div class="hidden sm:block leading-tight">
                    <span class="font-bold text-xs text-gray-800 block">{{ $student->name }}</span>
                    <span class="text-[10px] text-gray-400">Research Portal</span>
                </div>
            </div>
        </header>

        <main class="flex-1 p-8">
            <form id="student-document-upload-form" method="POST" action="{{ route('student.documents.store') }}" enctype="multipart/form-data" class="hidden">
                @csrf
                <input type="hidden" name="submission_token" value="{{ (string) Illuminate\Support\Str::uuid() }}">
                <input
                    id="student-document-upload-input"
                    type="file"
                    name="document"
                    accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                    onchange="if (this.files.length) { document.querySelectorAll('[data-document-upload-trigger]').forEach((button) => button.disabled = true); this.form.requestSubmit(); }"
                >
            </form>

            @if (session('document_success'))
                <div role="status" class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('document_success') }}
                </div>
            @endif

            @if (session('document_error') || $errors->has('document'))
                <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ session('document_error') ?: $errors->first('document') }}
                </div>
            @endif

            @if (session('revision_success'))
                <div role="status" class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('revision_success') }}
                </div>
            @endif

            @if ($errors->has('revision'))
                <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first('revision') }}
                </div>
            @endif

            @if (session('consultation_success'))
                <div role="status" class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('consultation_success') }}
                </div>
            @endif

            @if ($errors->has('consultation'))
                <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first('consultation') }}
                </div>
            @endif

            @if (session('class_success'))
                <div role="status" class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('class_success') }}
                </div>
            @endif

            @if ($errors->has('class'))
                <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first('class') }}
                </div>
            @endif

            <section x-show="activeTab === 'dashboard'" x-cloak class="space-y-8">
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
                            <span class="text-[#0e5c3a] font-bold">Student Researcher Portal</span>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-extrabold font-heading text-slate-900 tracking-tight">Welcome back, {{ $firstName }}!</h1>
                        <p class="text-xs text-slate-500 max-w-xl">Track your research milestones, submit documents, and manage consultations</p>
                    </div>

                    <div class="relative z-10 flex items-center gap-3">
                        <button type="button" data-document-upload-trigger onclick="document.getElementById('student-document-upload-input').click()" class="px-5 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer">
                            <i class="ph ph-upload-simple text-base"></i>
                            <span>Submit Document</span>
                        </button>
                    </div>
                </div>

                @if ($dashboardSearchQuery !== '')
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="font-bold text-gray-900">Search results for “{{ $dashboardSearchQuery }}”</h2>
                            <a href="{{ route('student.dashboard') }}" class="text-xs font-semibold text-[#0e5c3a]">Clear search</a>
                        </div>
                        <div class="mt-4 divide-y divide-gray-100">
                            @forelse ($dashboardSearchResults as $result)
                                <button type="button" @click="activeTab = '{{ $result['tab'] }}'" class="w-full py-3 flex items-center justify-between gap-4 text-left">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $result['title'] }}</p>
                                        <p class="text-xs text-gray-500 truncate mt-1">{{ $result['description'] ?: 'No additional details.' }}</p>
                                    </div>
                                    <span class="text-[10px] font-bold uppercase text-[#0e5c3a]">{{ $result['type'] }}</span>
                                </button>
                            @empty
                                <p class="py-5 text-sm text-gray-500 text-center">No records matched your search.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <button type="button" @click="activeTab = 'progress'" class="w-full text-left bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-emerald-50/80 text-[#0e5c3a] border border-emerald-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-trend-up"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Research Progress</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block">{{ $progressPercentage }}%</span>
                            <p class="text-xs text-emerald-600 font-medium mt-1">
                                @if ($dashboardOverview['total_milestones'] > 0)
                                    {{ $dashboardOverview['completed_milestones'] }} of {{ $dashboardOverview['total_milestones'] }} milestones
                                @else
                                    No milestones configured
                                @endif
                            </p>
                        </div>
                        <span class="text-emerald-600 text-xl font-bold">
                            <i class="ph ph-arrow-right"></i>
                        </span>
                    </button>

                    <button type="button" @click="activeTab = @js($nextAction['tab'] ?? 'progress')" class="w-full text-left bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-red-50/80 text-red-700 border border-red-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-warning-circle"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Urgent Tasks</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block">{{ $dashboardOverview['urgent_task_count'] }}</span>
                            <p class="text-xs text-red-600 font-medium mt-1">
                                @if ($nextAction && $nextAction['due_at'])
                                    Due {{ $nextAction['due_at']->diffForHumans() }}
                                @else
                                    No deadline
                                @endif
                            </p>
                        </div>
                        <span class="text-red-500 text-xl font-bold">
                            <i class="ph ph-warning"></i>
                        </span>
                    </button>

                    <button type="button" @click="activeTab = 'consultation'" class="w-full text-left bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-blue-50/80 text-blue-700 border border-blue-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-calendar-blank"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Next Consultation</span>
                            @if ($nextConsultation)
                                <span class="text-2xl font-extrabold text-slate-900 tracking-tight mt-1 block">
                                    {{ $nextConsultation['starts_at']->isToday() ? 'Today' : $nextConsultation['starts_at']->format('M j') }}
                                </span>
                                <p class="text-xs text-blue-600 font-medium mt-1">
                                    {{ $nextConsultation['starts_at']->format('g:i A') }}
                                    @if ($nextConsultation['adviser_name'])
                                        with {{ $nextConsultation['adviser_name'] }}
                                    @endif
                                </p>
                            @else
                                <span class="text-xl font-bold text-slate-900 block mt-1">Not scheduled</span>
                                <p class="text-xs text-slate-400 mt-1">Book when needed</p>
                            @endif
                        </div>
                        <span class="text-blue-500 text-xl font-bold">
                            <i class="ph ph-clock"></i>
                        </span>
                    </button>

                    <button type="button" @click="activeTab = 'repository'" class="w-full text-left bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-purple-50/80 text-purple-700 border border-purple-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-file-text"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Documents</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block">{{ $dashboardOverview['document_count'] }}</span>
                            <p class="text-xs text-purple-600 font-medium mt-1">{{ $dashboardOverview['pending_document_count'] }} pending review</p>
                        </div>
                        <span class="text-purple-500 text-xl font-bold">
                            <i class="ph ph-files"></i>
                        </span>
                    </button>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
                    <div class="xl:col-span-2 space-y-6">
                        <div class="bg-white rounded-2xl p-6 shadow-xs hover:shadow-md transition-all duration-200 border border-slate-200/60">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="font-bold text-lg text-gray-900">My Research</h2>
                                <i class="ph ph-book-open text-2xl text-[#00a36c]"></i>
                            </div>

                            @if ($researchProject)
                                <div class="rounded-2xl border border-emerald-300 bg-emerald-50 p-6">
                                    <h3 class="font-bold text-lg text-gray-900">{{ $researchProject->title }}</h3>
                                    <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                                        <span>ID: {{ $researchProject->id }}</span>
                                        <span class="px-2.5 py-1 rounded-full bg-emerald-500 text-white text-[10px] font-bold">
                                            {{ \Illuminate\Support\Str::headline($researchProject->status) }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between mt-6 text-sm">
                                        <span class="font-medium text-gray-700">Research Journey Progress</span>
                                        <span class="font-bold text-emerald-700">{{ $progressPercentage }}%</span>
                                    </div>
                                    <div class="h-3 rounded-full bg-white mt-3 overflow-hidden">
                                        <div class="h-full rounded-full bg-[#00a36c]" :style="{ width: researchProgress + '%' }"></div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                                        <div class="rounded-xl bg-white p-4">
                                            <span class="text-[10px] text-gray-500">Adviser</span>
                                            <p class="font-semibold text-gray-900 mt-1">{{ $adviser?->name ?? 'Not assigned' }}</p>
                                        </div>
                                        <div class="rounded-xl bg-white p-4">
                                            <span class="text-[10px] text-gray-500">Final Defense</span>
                                            <p class="font-semibold text-gray-900 mt-1">
                                                {{ $nextDefense?->starts_at ? \Illuminate\Support\Carbon::parse($nextDefense->starts_at)->format('F j, Y') : 'Not scheduled' }}
                                            </p>
                                        </div>
                                    </div>

                                    <button type="button" @click="activeTab = 'research'" class="w-full mt-4 py-3 rounded-xl bg-[#009b67] hover:bg-[#008558] text-white text-sm font-bold shadow">
                                        View Full Research Details
                                    </button>
                                </div>
                            @else
                                <x-student-empty-state message="No research project is associated with your account yet." />
                            @endif
                        </div>

                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h2 class="font-bold text-lg text-gray-900 mb-5">Current Milestone</h2>
                            <div class="space-y-3">
                                @forelse ($dashboardOverview['current_milestones'] as $milestone)
                                    @php
                                        $milestoneComplete = in_array($milestone->status, ['accepted', 'approved', 'completed', 'resolved'], true);
                                    @endphp
                                    <button type="button" @click="activeTab = 'progress'" @class([
                                        'w-full rounded-xl border p-4 flex items-start gap-4 text-left',
                                        'border-emerald-300 bg-emerald-50' => $milestoneComplete,
                                        'border-amber-300 bg-amber-50' => ! $milestoneComplete,
                                    ])>
                                        <i @class([
                                            'ph text-2xl mt-0.5',
                                            'ph-check-circle text-emerald-600' => $milestoneComplete,
                                            'ph-target text-amber-500' => ! $milestoneComplete,
                                        ])></i>
                                        <span class="min-w-0">
                                            <span class="font-semibold text-gray-900 block">{{ $milestone->name }}</span>
                                            <span class="text-xs text-gray-600 block mt-1">
                                                {{ $milestoneComplete ? 'Successfully completed' : ($milestone->status ? \Illuminate\Support\Str::headline($milestone->status) : 'Not started') }}
                                                @if ($milestone->due_at)
                                                    · Due {{ \Illuminate\Support\Carbon::parse($milestone->due_at)->format('M j, Y') }}
                                                @endif
                                            </span>
                                            @if ($milestone->feedback)
                                                <span class="text-xs text-emerald-700 block mt-1">{{ $milestone->feedback }}</span>
                                            @endif
                                        </span>
                                    </button>
                                @empty
                                    <x-student-empty-state message="No research milestones are configured yet." />
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h2 class="font-bold text-lg text-gray-900 flex items-center gap-2">
                                <i class="ph ph-warning-circle text-red-500"></i>
                                Action Required
                            </h2>
                            <div class="space-y-3 mt-5">
                                @forelse ($dashboardOverview['action_items'] as $item)
                                    <button type="button" @click="activeTab = '{{ $item['tab'] }}'" @class([
                                        'w-full border-l-4 rounded-r-lg p-3 text-left',
                                        'border-red-500 bg-red-50' => $item['type'] === 'revision',
                                        'border-amber-500 bg-amber-50' => $item['type'] !== 'revision',
                                    ])>
                                        <span class="font-semibold text-sm text-gray-900 block">{{ $item['title'] }}</span>
                                        <span class="text-xs text-gray-600 block mt-1">
                                            {{ $item['due_at'] ? 'Due '.$item['due_at']->diffForHumans() : \Illuminate\Support\Str::limit($item['description'] ?: 'Action required', 70) }}
                                        </span>
                                    </button>
                                @empty
                                    <p class="text-sm text-gray-500 py-5 text-center">No actions require your attention.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="rounded-2xl p-6 shadow-sm bg-gradient-to-br from-blue-500 to-blue-600 text-white">
                            <h2 class="font-bold text-lg flex items-center gap-2">
                                <i class="ph ph-chat-circle"></i>
                                {{ $nextConsultation && $nextConsultation['starts_at']->isToday() ? "Today's Consultation" : 'Upcoming Consultation' }}
                            </h2>
                            @if ($nextConsultation)
                                <div class="rounded-xl bg-blue-600/70 p-4 mt-4">
                                    <p class="font-bold">{{ $nextConsultation['adviser_name'] ?? 'Research Adviser' }}</p>
                                    <p class="text-xs text-blue-100 mt-2">{{ \Illuminate\Support\Str::headline((string) $nextConsultation['mode']) }}</p>
                                    <p class="text-sm mt-3">
                                        <i class="ph ph-clock mr-1"></i>
                                        {{ $nextConsultation['starts_at']->format('M j, g:i A') }}
                                    </p>
                                    <p class="text-xs text-blue-100 mt-2">
                                        {{ $nextConsultation['location'] ?: ($nextConsultation['meeting_url'] ? 'Online meeting' : 'Location to be confirmed') }}
                                    </p>
                                </div>
                                <button type="button" @click="activeTab = 'consultation'" class="w-full mt-4 py-3 bg-white text-blue-600 text-sm font-semibold rounded-xl">
                                    View All Consultations
                                </button>
                            @else
                                <p class="text-sm text-blue-100 mt-4">No upcoming consultation is scheduled.</p>
                                <a href="{{ route('student.dashboard', ['tab' => 'consultation', 'book' => 1]) }}" wire:navigate class="block w-full mt-4 py-3 bg-white text-blue-600 text-sm font-semibold text-center rounded-xl">
                                    Book Consultation
                                </a>
                            @endif
                        </div>

                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h2 class="font-bold text-lg text-gray-900">Recent Updates</h2>
                            <div class="divide-y divide-gray-100 mt-4">
                                @forelse ($dashboardOverview['recent_updates'] as $update)
                                    <button type="button" @click="activeTab = '{{ $update['tab'] }}'" class="w-full py-3 flex items-start gap-3 text-left">
                                        <span class="w-9 h-9 rounded-full bg-emerald-50 text-[#009b67] flex items-center justify-center flex-shrink-0">
                                            <i @class([
                                                'ph',
                                                'ph-chart-line-up' => $update['type'] === 'progress',
                                                'ph-file-text' => $update['type'] === 'document',
                                                'ph-note-pencil' => $update['type'] === 'revision',
                                                'ph-chat-circle' => $update['type'] === 'consultation',
                                            ])></i>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="text-sm font-semibold text-gray-800 truncate block">{{ $update['title'] }}</span>
                                            <span class="text-xs text-gray-500 block mt-1">
                                                {{ $update['description'] }} · {{ $update['occurred_at']->diffForHumans() }}
                                            </span>
                                        </span>
                                    </button>
                                @empty
                                    <p class="text-sm text-gray-500 py-5 text-center">No recent updates yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section x-show="activeTab === 'classes'" x-cloak class="space-y-8">
                <div class="flex items-center justify-between gap-4">
                    <x-student-section-heading title="My Classes" description="Approved classes and join requests for your account." />
                    <button
                        type="button"
                        @click="showJoinClassModal = true"
                        class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2"
                    >
                        <i class="ph ph-plus-circle text-base"></i>
                        <span>Request to Join</span>
                    </button>
                </div>

                @if ($classJoinRequests->isNotEmpty())
                    <div class="space-y-3">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500">Join request status</h2>
                        @foreach ($classJoinRequests as $joinRequest)
                            <article class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <h3 class="font-bold text-gray-800 text-sm">{{ $joinRequest->class_name }}</h3>
                                    <p class="text-[11px] text-gray-500 mt-1">
                                        Facilitator: {{ $joinRequest->facilitator_name }}
                                        · Requested {{ \Illuminate\Support\Carbon::parse($joinRequest->requested_at)->diffForHumans() }}
                                    </p>
                                </div>
                                <span @class([
                                    'px-3 py-1 rounded-full text-[10px] font-bold uppercase',
                                    'bg-amber-50 text-amber-700' => $joinRequest->status === 'pending',
                                    'bg-red-50 text-red-700' => $joinRequest->status === 'rejected',
                                ])>
                                    {{ $joinRequest->status }}
                                </span>
                            </article>
                        @endforeach
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse ($classes as $class)
                        <a href="{{ route('student.classes.show', $class->id) }}" class="block bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 space-y-4">
                            <div>
                                <h2 class="font-bold text-gray-800 text-sm">{{ $class->name }}</h2>
                                <p class="text-[11px] text-gray-500 mt-1">Facilitator: {{ $class->facilitator_name }}</p>
                            </div>
                            @if ($class->description)
                                <p class="text-xs text-gray-500 leading-6">{{ $class->description }}</p>
                            @endif
                            <p class="text-[10px] text-gray-400 pt-3 border-t border-gray-100">
                                Joined {{ \Illuminate\Support\Carbon::parse($class->joined_at)->diffForHumans() }}
                            </p>
                            <div class="flex items-center justify-between pt-1 text-[10px] font-bold text-[#0e5c3a]">
                                <span>View class</span><i class="ph ph-arrow-right"></i>
                            </div>
                        </a>
                    @empty
                        <div class="md:col-span-2 lg:col-span-3">
                            <x-student-empty-state message="You have not joined a research class yet." />
                        </div>
                    @endforelse
                </div>
            </section>

            <section x-show="activeTab === 'research'" x-cloak class="space-y-8">
                <x-student-section-heading title="Research Details" :description="$researchProject?->title" />

                @if ($researchProject)
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2 space-y-8">
                            <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md transition-all duration-200">
                                <h2 class="font-bold text-gray-850 text-lg">Abstract</h2>
                                <p class="text-sm text-gray-600 leading-7 mt-4">{{ $researchProject->abstract ?: 'No abstract has been provided.' }}</p>
                            </div>
                            <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md transition-all duration-200">
                                <h2 class="font-bold text-gray-850 text-lg mb-4">Keywords</h2>
                                @forelse ($keywords as $keyword)
                                    <span class="inline-flex px-3 py-1.5 mr-2 mb-2 bg-[#e6f4ea] text-[#0e5c3a] text-xs font-semibold rounded-full">{{ $keyword }}</span>
                                @empty
                                    <p class="text-sm text-gray-500">No keywords have been provided.</p>
                                @endforelse
                            </div>
                        </div>
                        <div class="space-y-8">
                            <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md transition-all duration-200 space-y-5">
                                <h2 class="font-bold text-gray-850 text-lg">Research Information</h2>
                                <x-student-detail label="Research ID" :value="'RES-'.str_pad((string) $researchProject->id, 6, '0', STR_PAD_LEFT)" />
                                <x-student-detail label="Type" :value="$researchProject->category" />
                                <x-student-detail label="Program" :value="$program?->name" />
                                <x-student-detail label="Status" :value="$researchProject->status" />
                            </div>
                            <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md transition-all duration-200">
                                <h2 class="font-bold text-gray-850 text-lg mb-5">Team</h2>
                                <div class="space-y-4">
                                    @forelse ($teamMembers as $member)
                                        <x-student-detail :label="\Illuminate\Support\Str::headline($member->member_role ?: 'Member')" :value="$member->name" />
                                    @empty
                                        <p class="text-sm text-gray-500">No active team members found.</p>
                                    @endforelse
                                    <x-student-detail label="Adviser" :value="$adviser?->name" />
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <x-student-empty-state message="No research details are available for your account." />
                @endif
            </section>

            <section x-show="activeTab === 'proposal'" x-cloak class="space-y-8">
                <x-student-section-heading title="Proposal Management" description="Submitted proposal versions for your research." />
                <div class="space-y-4">
                    @forelse ($proposals as $proposal)
                        <x-student-record-card
                            :title="'Proposal version '.$proposal->version"
                            :status="$proposal->status"
                            :date="$proposal->submitted_at"
                            :description="$researchProject?->title"
                        />
                    @empty
                        <x-student-empty-state message="No research proposals have been submitted." />
                    @endforelse
                </div>
            </section>

            <section x-show="activeTab === 'progress'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title Section -->
                <div>
                    <h1 class="text-3xl font-extrabold font-heading text-gray-800 tracking-tight">Research Lifecycle Tracker</h1>
                    <p class="text-sm text-gray-500 font-light mt-1">Track your research progress through each milestone</p>
                </div>

                @php
                    $completedStatuses = ['accepted', 'approved', 'completed', 'resolved', 'passed'];
                    $activeStatuses = ['pending', 'in_progress', 'submitted', 'review', 'under_review'];
                    
                    $completedCount = $researchMilestones->filter(fn($m) => in_array(strtolower($m->status ?? ''), $completedStatuses, true))->count();
                    $inProgressCount = $researchMilestones->filter(fn($m) => in_array(strtolower($m->status ?? ''), $activeStatuses, true))->count();
                    $pendingCount = $researchMilestones->filter(fn($m) => empty($m->status) || !in_array(strtolower($m->status), array_merge($completedStatuses, $activeStatuses), true))->count();
                    
                    $totalMilestones = $researchMilestones->count();
                    $progressPercentage = $dashboardOverview['progress_percentage'] ?? 0;
                    if ($progressPercentage === 0 && $totalMilestones > 0) {
                        $progressPercentage = (int) round(($completedCount / $totalMilestones) * 100);
                    }
                @endphp

                <!-- Overall Progress Card -->
                <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100 space-y-6">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <h3 class="text-lg font-bold text-gray-800">Overall Progress</h3>
                            <p class="text-xs text-gray-400 font-light">{{ $researchProject->title ?? 'No active research project found' }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-extrabold text-emerald-600 font-heading">{{ $progressPercentage }}%</span>
                            <span class="text-[10px] text-gray-400 font-bold block uppercase tracking-wider mt-0.5">Complete</span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full h-4 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-600 rounded-full transition-all duration-500" x-init="$el.style.width = @js($progressPercentage) + '%'" style="width: 0"></div>
                    </div>

                    <!-- Counts -->
                    <div class="grid grid-cols-3 gap-6 text-center pt-2">
                        <div>
                            <span class="text-xl font-extrabold text-emerald-600 font-heading block">{{ $completedCount }}</span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mt-1 block">Completed</span>
                        </div>
                        <div class="border-l border-r border-gray-150">
                            <span class="text-xl font-extrabold text-amber-500 font-heading block">{{ $inProgressCount }}</span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mt-1 block">In Progress</span>
                        </div>
                        <div>
                            <span class="text-xl font-extrabold text-gray-400 font-heading block">{{ $pendingCount }}</span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mt-1 block">Pending</span>
                        </div>
                    </div>
                </div>

                <!-- Research Milestones Container -->
                <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100 space-y-6">
                    <h3 class="text-base font-bold text-gray-800">Research Milestones</h3>

                    <!-- Timeline Vertical Container -->
                    <div class="relative pl-10 border-l-2 border-gray-150 space-y-8 ml-6 py-2">
                        @forelse ($researchMilestones as $milestone)
                            @php
                                $statusLower = strtolower($milestone->status ?? 'not_started');
                                $milestoneCompleted = in_array($statusLower, $completedStatuses, true);
                                $milestoneInProgress = in_array($statusLower, $activeStatuses, true);
                                
                                $isOverdue = !$milestoneCompleted
                                    && $milestone->due_at
                                    && Illuminate\Support\Carbon::parse($milestone->due_at)->isPast();
                                
                                $displayStatus = $milestoneCompleted ? 'completed' : ($milestoneInProgress ? 'in_progress' : 'pending');
                                if ($isOverdue && !$milestoneCompleted) {
                                    $displayStatus = 'overdue';
                                }
                            @endphp
                            <div class="relative">
                                <!-- Bullet Circle -->
                                <span @class([
                                    'absolute -left-[57px] top-1.5 flex h-8 w-8 items-center justify-center rounded-full shadow-sm bg-white',
                                    'bg-[#0fa47b] border border-[#0fa47b]' => $milestoneCompleted,
                                    'bg-[#f59e0b] border border-[#f59e0b]' => $milestoneInProgress,
                                    'bg-white border border-gray-200' => !$milestoneCompleted && !$milestoneInProgress,
                                ])>
                                    @if ($milestoneCompleted)
                                        <i class="ph-bold ph-check text-white text-xs"></i>
                                    @elseif ($milestoneInProgress)
                                        <i class="ph-bold ph-clock text-white text-xs"></i>
                                    @else
                                        <span class="w-2.5 h-2.5 bg-gray-250 rounded-full"></span>
                                    @endif
                                </span>

                                <!-- Content Card -->
                                <div @class([
                                    'p-6 border rounded-3xl hover:shadow-sm transition-all duration-300 flex items-center justify-between',
                                    'bg-[#f4faf7] border-emerald-100' => $milestoneCompleted,
                                    'bg-[#fdfaf2] border-amber-100' => $milestoneInProgress,
                                    'bg-white border-gray-150 opacity-70' => !$milestoneCompleted && !$milestoneInProgress,
                                ])>
                                    <div class="space-y-1.5">
                                        <h4 class="font-extrabold text-gray-800 text-sm">{{ $milestone->name }}</h4>
                                        <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                                            <i class="ph ph-calendar"></i>
                                            @if ($milestone->due_at)
                                                <span>{{ \Illuminate\Support\Carbon::parse($milestone->due_at)->format('M j, Y') }}</span>
                                            @else
                                                <span>Not Started</span>
                                            @endif
                                        </div>
                                        @if ($milestoneCompleted)
                                            <p class="text-xs text-emerald-705 font-medium flex items-center gap-1">✓ All requirements met and approved</p>
                                        @elseif ($milestoneInProgress)
                                            <p class="text-xs text-amber-705 font-medium flex items-center gap-1">Currently working on this milestone</p>
                                        @elseif ($milestone->description)
                                            <p class="text-xs text-gray-500 font-medium">{{ $milestone->description }}</p>
                                        @endif
                                    </div>
                                    <span @class([
                                        'text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider',
                                        'bg-[#0fa47b]' => $milestoneCompleted,
                                        'bg-amber-500' => $milestoneInProgress,
                                        'bg-gray-400' => !$milestoneCompleted && !$milestoneInProgress,
                                    ])>{{ str($displayStatus)->headline() }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="p-10 bg-gray-55/60 border border-gray-100 rounded-3xl text-center text-sm text-gray-500">
                                No research milestones have been configured for your program and academic term.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Bottom Action Buttons -->
                <div class="flex items-center gap-3 pt-6">
                    <button 
                        type="button"
                        @click="alert('Progress updates are submitted automatically when you upload documents or complete consultations.')"
                        class="px-5 py-3 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl transition-all duration-200 cursor-pointer"
                    >
                        Update Progress
                    </button>
                    <button 
                        type="button"
                        @click="window.print()"
                        class="px-5 py-3 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl transition-all duration-200 cursor-pointer"
                    >
                        Download Timeline
                    </button>
                </div>
            </section>

            <section x-show="activeTab === 'consultation'" x-cloak class="space-y-8">
                <div class="flex items-center justify-between gap-4">
                    <x-student-section-heading title="Consultation Management" description="Schedule and manage adviser consultations." />
                    <button
                        type="button"
                        @click="showConsultationModal = true"
                        class="px-4 py-2.5 bg-[#009b67] hover:bg-[#008558] text-white text-xs font-bold rounded-xl flex items-center gap-2 transition-colors"
                    >
                        <i class="ph ph-plus text-base"></i>
                        <span>Book Consultation</span>
                    </button>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md transition-all duration-200">
                    <h2 class="font-bold text-gray-850 text-lg mb-5">Consultation Requests</h2>
                    <div class="space-y-4">
                        @forelse ($consultationRequests as $consultationRequest)
                            <x-student-record-card
                                :title="$consultationRequest->adviser_name"
                                :status="$consultationRequest->status"
                                :date="\Illuminate\Support\Carbon::parse($consultationRequest->preferred_at)->timezone(config('ndmu-rmas.timezone'))"
                                :description="\Illuminate\Support\Str::headline($consultationRequest->consultation_mode).' — '.$consultationRequest->agenda"
                            />
                        @empty
                            <x-student-empty-state message="No consultation requests have been submitted." />
                        @endforelse
                    </div>
                </div>

                <h2 class="font-bold text-gray-850 text-lg">Consultation Records</h2>
                <div class="space-y-4">
                    @forelse ($consultations as $consultation)
                        <x-student-record-card
                            :title="$consultation->facilitator_name ?: 'Research consultation'"
                            :status="$consultation->consultation_mode"
                            :date="$consultation->consulted_at"
                            :description="$consultation->agenda"
                        />
                    @empty
                        <x-student-empty-state message="No consultation records have been created." />
                    @endforelse
                </div>
            </section>

            <section x-show="activeTab === 'revisions'" x-cloak class="space-y-8">
                <x-student-section-heading title="Revision Tracker" description="Revision requests for your research." />
                <div class="space-y-4">
                    @forelse ($revisions as $revision)
                        <div class="space-y-3">
                            <x-student-record-card
                                :title="$revision->title"
                                :status="$revision->status"
                                :date="$revision->created_at"
                                :description="$revision->instructions"
                            />

                            <div class="bg-white rounded-2xl px-5 pb-5 border border-gray-100 shadow-sm -mt-5 pt-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                <div class="text-xs text-gray-500">
                                    @if ($revision->latest_document_id)
                                        Latest submission:
                                        <a href="{{ route('documents.view', $revision->latest_document_id) }}" class="font-bold text-[#0e5c3a]">
                                            {{ $revision->latest_document_name }}
                                        </a>
                                    @elseif ($revision->due_at)
                                        Due {{ \Illuminate\Support\Carbon::parse($revision->due_at)->format('M j, Y') }}
                                    @else
                                        No revised document submitted yet.
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    @if ($revision->workflow_enabled && $revision->status === 'open')
                                        <form method="POST" action="{{ route('student.revisions.start', $revision->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-4 py-2.5 border border-[#0e5c3a] text-[#0e5c3a] text-xs font-bold rounded-xl">
                                                Start Revision
                                            </button>
                                        </form>
                                    @endif

                                    @if ($revision->workflow_enabled && in_array($revision->status, ['open', 'in_progress'], true))
                                        <form method="POST" action="{{ route('student.revisions.submit', $revision->id) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="submission_token" value="{{ (string) Illuminate\Support\Str::uuid() }}">
                                            <input
                                                type="file"
                                                name="document"
                                                required
                                                accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                                class="max-w-56 text-xs text-gray-600 file:mr-3 file:px-3 file:py-2 file:border-0 file:rounded-lg file:bg-gray-100 file:text-gray-700"
                                            >
                                            <button type="submit" class="px-4 py-2.5 bg-[#0e5c3a] text-white text-xs font-bold rounded-xl">
                                                Submit Revision
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-student-empty-state message="No revision requests have been issued." />
                    @endforelse
                </div>
            </section>

            <section x-show="activeTab === 'defense'" x-cloak class="space-y-8">
                <x-student-section-heading title="My Defense Schedule" description="Defense requests and confirmed schedules." />
                <div class="space-y-4">
                    @forelse ($defenses as $defense)
                        <x-student-record-card
                            :title="\Illuminate\Support\Str::headline($defense->defense_type)"
                            :status="$defense->schedule_status ?: $defense->request_status"
                            :date="$defense->starts_at ?: $defense->preferred_date"
                            :description="$defense->room_name ? trim($defense->room_name.' '.$defense->building) : ($defense->meeting_url ? 'Online defense' : 'Venue not assigned')"
                        />
                    @empty
                        <x-student-empty-state message="No defense request or schedule is available." />
                    @endforelse
                </div>
            </section>

            <section x-show="activeTab === 'evaluations'" x-cloak class="space-y-8">
                <x-student-section-heading title="Evaluation Results" description="Released evaluation records for your defenses." />
                <div class="space-y-4">
                    @forelse ($evaluations as $evaluation)
                        <x-student-record-card
                            :title="\Illuminate\Support\Str::headline($evaluation->defense_type)"
                            :status="$evaluation->status"
                            :date="$evaluation->submitted_at"
                            :description="$evaluation->recommendation ?: $evaluation->comments"
                        />
                    @empty
                        <x-student-empty-state message="No evaluation results have been released." />
                    @endforelse
                </div>
            </section>

            <section x-show="activeTab === 'repository'" x-cloak class="space-y-8">
                <div class="flex items-center justify-between">
                    <x-student-section-heading title="Research Repository" description="Documents securely submitted by your account." />
                    <button type="button" data-document-upload-trigger onclick="document.getElementById('student-document-upload-input').click()" class="px-4 py-2.5 bg-[#0e5c3a] text-white text-xs font-bold rounded-xl flex items-center gap-2 disabled:opacity-60">
                        <i class="ph ph-upload-simple"></i>
                        <span>Upload Document</span>
                    </button>
                </div>
                <div class="space-y-4">
                    @forelse ($documents as $document)
                        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p class="font-bold text-sm text-gray-800 truncate">{{ $document->original_filename }}</p>
                                <p class="text-[10px] text-gray-500 mt-1">
                                    {{ $document->formattedFileSize() }}
                                    · {{ $document->submitted_at?->format('M j, Y g:i A') }}
                                    · {{ \Illuminate\Support\Str::headline($document->status->value) }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('documents.view', $document) }}" class="px-3 py-2 text-xs font-bold rounded-lg border border-gray-200 text-gray-700">View</a>
                                <a href="{{ route('documents.download', $document) }}" class="px-3 py-2 text-xs font-bold rounded-lg bg-[#0e5c3a] text-white">Download</a>
                            </div>
                        </div>
                    @empty
                        <x-student-empty-state message="No documents have been uploaded." />
                    @endforelse
                </div>
            </section>

            <section x-show="activeTab === 'forms'" x-cloak class="space-y-8">
                @include('pages.student.forms.index')
            </section>

            <section x-show="activeTab === 'notifications'" x-cloak class="space-y-8">
                <x-student-section-heading title="Notifications" description="Notifications delivered to your account." />
                <div class="space-y-4">
                    @forelse ($notifications as $notification)
                        <x-student-record-card
                            :title="$notification->data['title'] ?? 'Notification'"
                            :status="$notification->read_at ? 'read' : 'unread'"
                            :date="$notification->created_at"
                            :description="$notification->data['message'] ?? null"
                        />
                    @empty
                        <x-student-empty-state message="You have no notifications." />
                    @endforelse
                </div>
            </section>

            <section x-show="activeTab === 'settings'" x-cloak class="space-y-8">
                @include('partials.settings', [
                    'avatarInitials' => strtoupper(substr($student->name, 0, 1)),
                    'userName' => $student->name,
                    'emailAddress' => $student->email,
                    'userRole' => 'Student Researcher',
                    'userRoleBadge' => 'STUDENT RESEARCHER',
                    'department' => $program?->name ?: $student->program,
                    'userId' => $studentProfile?->student_number ?: $student->student_id,
                    'portalType' => 'Student Portal',
                    'accessLevel' => 'Student & Research Access'
                ])
            </section>
        </main>
    </div>

    <div x-show="showConsultationModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showConsultationModal = false"></div>
        <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden" @click.stop>
            <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100">
                <div>
                    <h2 class="font-bold text-lg text-gray-850">Book Consultation</h2>
                    <p class="text-xs text-gray-500 mt-1">Your assigned adviser will review this request.</p>
                </div>
                <button type="button" @click="showConsultationModal = false" class="w-8 h-8 rounded-full hover:bg-gray-100 text-gray-500">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('student.consultations.store') }}" class="p-6 space-y-5">
                @csrf
                <input type="hidden" name="request_token" value="{{ old('request_token', (string) Illuminate\Support\Str::uuid()) }}">

                <div>
                    <label for="preferred_at" class="text-xs font-bold text-gray-700 block mb-2">Preferred date and time</label>
                    <input
                        id="preferred_at"
                        name="preferred_at"
                        type="datetime-local"
                        value="{{ old('preferred_at') }}"
                        min="{{ now(config('ndmu-rmas.timezone'))->addMinutes(30)->format('Y-m-d\TH:i') }}"
                        max="{{ now(config('ndmu-rmas.timezone'))->addMonths(3)->format('Y-m-d\TH:i') }}"
                        required
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-[#009b67] focus:outline-none"
                    >
                    @error('preferred_at')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="consultation_mode" class="text-xs font-bold text-gray-700 block mb-2">Consultation mode</label>
                    <select
                        id="consultation_mode"
                        name="consultation_mode"
                        required
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-[#009b67] focus:outline-none"
                    >
                        <option value="">Select a mode</option>
                        <option value="in_person" @selected(old('consultation_mode') === 'in_person')>In person</option>
                        <option value="online" @selected(old('consultation_mode') === 'online')>Online</option>
                    </select>
                    @error('consultation_mode')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="agenda" class="text-xs font-bold text-gray-700 block mb-2">Agenda</label>
                    <textarea
                        id="agenda"
                        name="agenda"
                        rows="4"
                        maxlength="2000"
                        required
                        placeholder="Describe what you want to discuss with your adviser."
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-[#009b67] focus:outline-none resize-none"
                    >{{ old('agenda') }}</textarea>
                    @error('agenda')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showConsultationModal = false" class="px-4 py-2.5 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2.5 bg-[#009b67] hover:bg-[#008558] text-white text-xs font-bold rounded-xl">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showJoinClassModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showJoinClassModal = false"></div>
        <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden" @click.stop>
            <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100">
                <div>
                    <h2 class="font-bold text-lg text-gray-850">Request to Join a Research Class</h2>
                    <p class="text-xs text-gray-500 mt-1">Enter the class code. Your adviser must approve the request before you are enrolled.</p>
                </div>
                <button type="button" @click="showJoinClassModal = false" class="w-8 h-8 rounded-full hover:bg-gray-100 text-gray-500">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('student.classes.join') }}" class="p-6 space-y-5">
                @csrf
                <div>
                    <label for="join_code" class="text-xs font-bold text-gray-700 block mb-2">Class code</label>
                    <input
                        id="join_code"
                        name="join_code"
                        type="text"
                        value="{{ old('join_code') }}"
                        minlength="5"
                        maxlength="16"
                        autocomplete="off"
                        required
                        placeholder="Enter class code"
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm uppercase tracking-widest focus:border-[#0e5c3a] focus:outline-none"
                    >
                    @error('join_code')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" @click="showJoinClassModal = false" class="px-4 py-2.5 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
