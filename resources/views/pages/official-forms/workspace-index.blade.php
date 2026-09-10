<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Research Forms Archive | NDMU-RMAS</title>
    <style>[x-cloak] { display: none !important; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#f4f7f6] text-gray-900" x-data="{
    activeCategory: 'all',
    searchQuery: '',
    viewMode: 'grouped',
    selectedGroupFilter: 'all'
}">
    <!-- Top Navigation Header -->
    <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur-md shadow-xs">
        <div class="mx-auto flex max-w-7xl flex-col items-stretch gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <img src="{{ asset('images/ndmu_logo.png') }}" alt="NDMU Logo" class="h-10 w-auto object-contain">
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-[.22em] text-amber-500">NDMU Research Management</p>
                    <h1 class="text-xl sm:text-2xl font-black font-heading text-[#0e5c3a]">Official Forms Archive & Workspace</h1>
                </div>
            </div>
            <div class="flex items-center gap-3 sm:shrink-0">
                <a href="{{ route('dashboard') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 transition shadow-2xs sm:w-auto">
                    <i class="ph ph-arrow-left font-bold text-sm"></i>
                    <span>Back to Dashboard</span>
                </a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl space-y-8 px-4 py-6 sm:px-6 sm:py-8">
        @if (session('official_form_success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800 shadow-2xs flex items-center gap-3">
                <i class="ph ph-check-circle text-emerald-600 text-lg"></i>
                <span>{{ session('official_form_success') }}</span>
            </div>
        @endif
        @if (isset($errors) && $errors->has('official_form'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700 shadow-2xs flex items-center gap-3">
                <i class="ph ph-warning-circle text-red-600 text-lg"></i>
                <span>{{ $errors->first('official_form') }}</span>
            </div>
        @endif

        <!-- Action Required / Pending Signatures Banner -->
        @if (isset($pendingInstances) && $pendingInstances->isNotEmpty())
            <section class="rounded-3xl border-2 border-amber-400 bg-gradient-to-r from-amber-500 to-amber-600 p-6 sm:p-7 text-white shadow-xl">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-[11px] font-black uppercase tracking-wider text-white backdrop-blur-md">
                            <span class="h-2 w-2 rounded-full bg-white animate-pulse"></span>
                            Action Required
                        </div>
                        <h2 class="mt-2 text-xl sm:text-2xl font-black font-heading tracking-tight">Requires Your Signature / Endorsement ({{ $pendingInstances->count() }})</h2>
                        <p class="mt-1 text-xs text-white/90">The following authoritative forms are awaiting your digital signature, endorsement, or approval.</p>
                    </div>
                </div>
                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($pendingInstances as $pending)
                        <a href="{{ route('official-forms.workspace.show', $pending) }}" class="group rounded-2xl bg-white p-5 text-gray-900 shadow-md transition-all duration-200 hover:-translate-y-1 hover:shadow-xl border border-amber-200 flex flex-col justify-between">
                            <div>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <span class="inline-block rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-black text-[#0e5c3a] border border-emerald-200/60">{{ $pending->definition->code }}</span>
                                        <h3 class="mt-1.5 text-sm font-bold text-gray-900 group-hover:text-[#0e5c3a] transition-colors line-clamp-2">{{ $pending->definition->title }}</h3>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-rose-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-rose-700 border border-rose-200">Needs Review</span>
                                </div>
                                <div class="mt-3.5 space-y-1 rounded-xl bg-slate-50 p-2.5 text-xs text-slate-600 border border-slate-100">
                                    <div class="font-bold text-slate-800 truncate">
                                        <i class="ph ph-users-three mr-1 text-slate-400"></i>
                                        {{ $pending->group?->name ?? $pending->researchClass?->name ?? 'Academic Context' }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 truncate">
                                        <i class="ph ph-user-circle mr-1 text-slate-400"></i>
                                        Initiated by: <strong class="text-slate-700">{{ $pending->initiatedBy?->name ?? 'Student' }}</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-xs font-bold text-[#0e5c3a]">
                                <span class="flex items-center gap-1.5 group-hover:underline">
                                    <span>Open for Review & Signing</span>
                                </span>
                                <i class="ph ph-arrow-right text-sm transition-transform group-hover:translate-x-1"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Authoritative Archive Header & Toolbar -->
        <section class="rounded-3xl bg-gradient-to-br from-[#073823] via-[#0e5c3a] to-[#0a462c] p-7 text-white shadow-xl border border-emerald-600/30 relative overflow-hidden">
            <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-[#eebc3f]/15 blur-3xl"></div>
            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-1.5 rounded-full bg-black/25 px-3 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-[#eebc3f] border border-[#eebc3f]/30">
                        <i class="ph ph-archive text-xs"></i>
                        <span>Institutional Records Archive</span>
                    </div>
                    <h2 class="mt-2 text-2xl sm:text-3xl font-black font-heading text-white tracking-tight">Saved Authoritative Form Records</h2>
                    <p class="mt-1 text-xs sm:text-sm text-emerald-100/80 max-w-2xl">
                        Every submitted version is cryptographically stamped and stored. Panelist evaluations, summaries, and institutional clearances are archived here.
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <!-- Toggle View Mode: Grid vs Group Dossier -->
                    <div class="inline-flex rounded-xl bg-black/30 p-1 border border-white/15">
                        <button
                            type="button"
                            @click="viewMode = 'grid'"
                            :class="viewMode === 'grid' ? 'bg-[#eebc3f] text-[#073823] font-black shadow-xs' : 'text-white/80 hover:text-white font-medium'"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs transition cursor-pointer"
                        >
                            <i class="ph ph-squares-four text-sm"></i>
                            <span>Grid View</span>
                        </button>
                        <button
                            type="button"
                            @click="viewMode = 'grouped'"
                            :class="viewMode === 'grouped' ? 'bg-[#eebc3f] text-[#073823] font-black shadow-xs' : 'text-white/80 hover:text-white font-medium'"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs transition cursor-pointer"
                        >
                            <i class="ph ph-folder-open text-sm"></i>
                            <span>Group Dossier</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <div class="mt-6 pt-5 border-t border-white/10 flex flex-col md:flex-row gap-4 items-stretch md:items-center justify-between">
                <!-- Search Input -->
                <div class="relative flex-1 max-w-md">
                    <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-white/60 text-base"></i>
                    <input
                        type="text"
                        x-model="searchQuery"
                        placeholder="Search by form code, title, student group, or panelist name..."
                        class="w-full rounded-xl bg-white/10 border border-white/20 pl-10 pr-4 py-2 text-xs text-white placeholder:text-white/50 focus:bg-white/20 focus:outline-hidden focus:ring-2 focus:ring-[#eebc3f]/50 transition"
                    >
                    <button
                        type="button"
                        x-show="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-white/60 hover:text-white text-xs cursor-pointer"
                    >
                        <i class="ph ph-x-circle text-base"></i>
                    </button>
                </div>

                <!-- Category Filters -->
                <div class="flex flex-wrap items-center gap-1.5">
                    @php
                        $evalCount = $instances->filter(fn($i) => in_array(strtoupper($i->definition->code), ['RES-036', 'RES-037']))->count();
                        $endorseCount = $instances->filter(fn($i) => in_array(strtoupper($i->definition->code), ['RES-026', 'RES-038', 'RES-040', 'RES-041', 'RES-047']))->count();
                        $consultCount = $instances->filter(fn($i) => in_array(strtoupper($i->definition->code), ['RES-031', 'RES-032']))->count();
                    @endphp
                    <button
                        type="button"
                        @click="activeCategory = 'all'"
                        :class="activeCategory === 'all' ? 'bg-white text-[#073823] font-black' : 'bg-white/10 text-white/85 hover:bg-white/20 font-semibold'"
                        class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5"
                    >
                        <span>All</span>
                        <span class="rounded-full bg-black/20 px-1.5 py-0.2 text-[10px]">{{ $instances->count() }}</span>
                    </button>
                    <button
                        type="button"
                        @click="activeCategory = 'evaluations'"
                        :class="activeCategory === 'evaluations' ? 'bg-white text-[#073823] font-black' : 'bg-white/10 text-white/85 hover:bg-white/20 font-semibold'"
                        class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5"
                    >
                        <i class="ph ph-scales text-sm"></i>
                        <span>Evaluations (036/037)</span>
                        <span class="rounded-full bg-black/20 px-1.5 py-0.2 text-[10px]">{{ $evalCount }}</span>
                    </button>
                    <button
                        type="button"
                        @click="activeCategory = 'endorsements'"
                        :class="activeCategory === 'endorsements' ? 'bg-white text-[#073823] font-black' : 'bg-white/10 text-white/85 hover:bg-white/20 font-semibold'"
                        class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5"
                    >
                        <i class="ph ph-signature text-sm"></i>
                        <span>Endorsements</span>
                        <span class="rounded-full bg-black/20 px-1.5 py-0.2 text-[10px]">{{ $endorseCount }}</span>
                    </button>
                    <button
                        type="button"
                        @click="activeCategory = 'consultations'"
                        :class="activeCategory === 'consultations' ? 'bg-white text-[#073823] font-black' : 'bg-white/10 text-white/85 hover:bg-white/20 font-semibold'"
                        class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5"
                    >
                        <i class="ph ph-chat-centered-text text-sm"></i>
                        <span>Consultations</span>
                        <span class="rounded-full bg-black/20 px-1.5 py-0.2 text-[10px]">{{ $consultCount }}</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Form Cards: Standard Grid View -->
        <div x-show="viewMode === 'grid'" x-cloak class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($instances as $instance)
                    @php
                        $code = strtoupper($instance->definition->code);
                        $category = match ($code) {
                            'RES-036', 'RES-037' => 'evaluations',
                            'RES-026', 'RES-038', 'RES-040', 'RES-041', 'RES-047' => 'endorsements',
                            'RES-031', 'RES-032' => 'consultations',
                            default => 'other',
                        };

                        $authorName = $instance->computed_author_name ?? 'Academic Author';
                        $authorRole = $instance->computed_author_role ?? 'Authorized Submitter';
                        $groupName = $instance->group?->name ?? $instance->researchClass?->name ?? 'Academic Group';
                        $searchHaystack = strtolower("{$code} {$instance->definition->title} {$groupName} {$authorName} {$authorRole}");

                        $statusBadgeClass = match (strtolower($instance->status)) {
                            'signed' => 'bg-purple-50 text-purple-700 border-purple-200',
                            'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'submitted' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'endorsed' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                            default => 'bg-amber-50 text-amber-700 border-amber-200',
                        };
                    @endphp
                    <div
                        x-show="(activeCategory === 'all' || activeCategory === '{{ $category }}') && (!searchQuery || '{{ $searchHaystack }}'.includes(searchQuery.toLowerCase()))"
                        class="group rounded-2xl border border-gray-200 bg-white p-5.5 shadow-xs transition-all duration-200 hover:-translate-y-1 hover:shadow-lg flex flex-col justify-between relative overflow-hidden"
                    >
                        <div class="space-y-3.5">
                            <!-- Top Bar: Form Code & Status Pill -->
                            <div class="flex items-start justify-between gap-3">
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-black text-[#0e5c3a] border border-emerald-200/80">
                                    <span>{{ $code }}</span>
                                </span>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wider border {{ $statusBadgeClass }}">
                                    {{ str($instance->status)->headline() }}
                                </span>
                            </div>

                            <!-- Form Title -->
                            <h3 class="text-sm font-bold text-gray-900 group-hover:text-[#0e5c3a] transition-colors leading-snug line-clamp-2">
                                {{ $instance->definition->title }}
                            </h3>

                            <!-- Submitter / Attribution Pill -->
                            <div class="rounded-xl bg-slate-50 border border-slate-200/70 p-3 space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded-full bg-[#0e5c3a]/10 text-[#0e5c3a] flex items-center justify-center font-black text-[10px] shrink-0">
                                        {{ strtoupper(substr($authorName, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-bold text-slate-800 truncate" title="{{ $authorName }}">
                                            {{ $authorName }}
                                        </div>
                                        <div class="text-[10px] font-semibold text-slate-500">
                                            <span class="inline-block rounded px-1.5 py-0.2 bg-slate-200/70 text-slate-700 text-[9px] font-bold">
                                                {{ $authorRole }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Context & Version Info -->
                            <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1">
                                <span class="flex items-center gap-1 font-semibold text-slate-700 truncate max-w-[65%]" title="{{ $groupName }}">
                                    <i class="ph ph-users text-xs text-slate-400"></i>
                                    {{ $groupName }}
                                </span>
                                <span class="text-[10px] font-mono font-bold text-slate-400">
                                    v{{ $instance->currentVersion?->version_number ?? 1 }} · {{ $instance->updated_at->format('M j, Y') }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Action Footer -->
                        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                            <a
                                href="{{ route('official-forms.workspace.show', $instance) }}"
                                class="w-full inline-flex items-center justify-between text-xs font-bold text-[#0e5c3a] hover:text-[#073823] group/link"
                            >
                                <span>Open Form Workspace</span>
                                <i class="ph ph-arrow-right text-sm transition-transform group-hover/link:translate-x-1"></i>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center text-sm text-gray-500">
                        <i class="ph ph-folder-dashed text-3xl text-gray-400 block mb-2"></i>
                        No saved official form is accessible in your current academic assignments.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Form Cards: Group Dossier / Archive View -->
        <div x-show="viewMode === 'grouped'" x-cloak class="space-y-6">
            @php
                $groupedInstances = $instances->groupBy(fn($i) => $i->group?->name ?? ($i->researchClass?->name ?? 'Class & General Forms'));
            @endphp
            @forelse ($groupedInstances as $gName => $gInstances)
                <div class="rounded-3xl border border-gray-200 bg-white shadow-xs overflow-hidden" x-data="{ isExpanded: true }">
                    <!-- Dossier Header -->
                    <div class="bg-gradient-to-r from-emerald-50 via-slate-50 to-white px-6 py-4.5 border-b border-gray-200 flex items-center justify-between cursor-pointer" @click="isExpanded = !isExpanded">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-xl bg-[#0e5c3a] text-white flex items-center justify-center shadow-xs">
                                <i class="ph ph-folder-open text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-gray-900">{{ $gName }}</h3>
                                <p class="text-xs text-slate-500 font-medium">{{ $gInstances->count() }} official record(s) archived</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold text-emerald-800">
                                {{ $gInstances->count() }} Forms
                            </span>
                            <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" :class="isExpanded && 'rotate-180'"></i>
                        </div>
                    </div>

                    <!-- Dossier Items List -->
                    <div x-show="isExpanded" class="p-6 divide-y divide-gray-100">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($gInstances as $instance)
                                @php
                                    $code = strtoupper($instance->definition->code);
                                    $category = match ($code) {
                                        'RES-036', 'RES-037' => 'evaluations',
                                        'RES-026', 'RES-038', 'RES-040', 'RES-041', 'RES-047' => 'endorsements',
                                        'RES-031', 'RES-032' => 'consultations',
                                        default => 'other',
                                    };

                                    $authorName = $instance->computed_author_name ?? 'Academic Author';
                                    $authorRole = $instance->computed_author_role ?? 'Authorized Submitter';
                                    $searchHaystack = strtolower("{$code} {$instance->definition->title} {$gName} {$authorName} {$authorRole}");

                                    $statusBadgeClass = match (strtolower($instance->status)) {
                                        'signed' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'submitted' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'endorsed' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        default => 'bg-amber-50 text-amber-700 border-amber-200',
                                    };
                                @endphp
                                <div
                                    x-show="(activeCategory === 'all' || activeCategory === '{{ $category }}') && (!searchQuery || '{{ $searchHaystack }}'.includes(searchQuery.toLowerCase()))"
                                    class="group rounded-2xl border border-gray-200 bg-slate-50/50 p-4.5 shadow-2xs hover:bg-white hover:shadow-md transition-all flex flex-col justify-between"
                                >
                                    <div class="space-y-2.5">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="rounded bg-emerald-100/70 px-2 py-0.5 text-[11px] font-black text-[#0e5c3a]">{{ $code }}</span>
                                            <span class="rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider border {{ $statusBadgeClass }}">
                                                {{ str($instance->status)->headline() }}
                                            </span>
                                        </div>
                                        <h4 class="text-xs font-bold text-gray-900 group-hover:text-[#0e5c3a] transition-colors line-clamp-2">
                                            {{ $instance->definition->title }}
                                        </h4>
                                        <div class="text-[11px] text-slate-600 bg-white rounded-lg p-2 border border-slate-200/60">
                                            <div class="font-bold text-slate-800 truncate">{{ $authorName }}</div>
                                            <div class="text-[10px] text-slate-400">{{ $authorRole }} · v{{ $instance->currentVersion?->version_number ?? 1 }}</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-2.5 border-t border-gray-200/60 flex items-center justify-between">
                                        <a href="{{ route('official-forms.workspace.show', $instance) }}" class="text-[11px] font-bold text-[#0e5c3a] hover:underline flex items-center gap-1">
                                            <span>Open Record</span>
                                            <i class="ph ph-arrow-right"></i>
                                        </a>
                                        <span class="text-[10px] text-slate-400 font-mono">{{ $instance->updated_at->format('M j, Y') }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center text-sm text-gray-500">
                    No grouped records found.
                </div>
            @endforelse
        </div>

        <!-- Create An Authorized Form Record Section -->
        <section class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-7 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center">
                    <i class="ph ph-file-plus text-xl"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black text-[#0e5c3a] font-heading">Create an Authorized Form Record</h2>
                    <p class="text-xs text-gray-500">Only forms permitted for your role and current group assignment are enabled. Source-bound forms open automatically from defense/evaluation schedules.</p>
                </div>
            </div>
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($definitions as $definition)
                    @php
                        $code = strtolower($definition->code);
                        $hasFormPermission = auth()->user()->can('users.manage') || auth()->user()->getAllPermissions()->contains(fn ($permission) => str_starts_with($permission->name, "forms.{$code}."));
                        if ($definition->code === 'RES-031') {
                            $hasFormPermission = auth()->user()->can('users.manage')
                                || (auth()->user()->can('forms.res-031.sign')
                                    && $contexts['groups']->contains('adviser_id', auth()->id()));
                        }
                        $sourceBound = in_array($definition->code, ['RES-039', 'RES-043A', 'RES-043B'], true);
                        $blocked = in_array($definition->code, ['RES-029', 'RES-036', 'RES-037'], true);
                        $res026Locked = $definition->code === 'RES-026'
                            && auth()->user()->user_type->value === 'student'
                            && $contexts['groups']->pluck('id')->intersect($res026UnlockedGroupIds)->isEmpty();
                        $res031Locked = $definition->code === 'RES-031'
                            && $contexts['groups']->pluck('id')->intersect($res031UnlockedGroupIds)->isEmpty();
                    @endphp
                    @if ($hasFormPermission)
                        <form method="POST" action="{{ route('official-forms.workspace.store', $definition) }}" class="rounded-2xl border border-gray-200 bg-slate-50/40 p-4.5 flex flex-col justify-between hover:bg-white hover:border-emerald-200 transition-all shadow-2xs">
                            @csrf
                            <div>
                                <span class="rounded bg-emerald-100/70 px-2 py-0.5 text-[11px] font-black text-[#0e5c3a]">{{ $definition->code }}</span>
                                <p class="mt-2 min-h-10 text-xs font-bold text-gray-900">{{ $definition->title }}</p>
                                @if ($definition->ownership_scope === 'research_group')
                                    <select name="group_id" class="mt-3 w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700" @disabled(auth()->user()->user_type->value === 'student')>
                                        <option value="">{{ auth()->user()->user_type->value === 'student' ? 'Your current group is selected securely' : 'Select research group' }}</option>
                                        @foreach ($contexts['groups'] as $group)
                                            <option value="{{ $group->id }}">{{ $group->name }} — {{ $group->researchClass?->name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <select name="class_id" class="mt-3 w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
                                        <option value="">Select research class</option>
                                        @foreach ($contexts['classes'] as $class)
                                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                @if ($definition->cardinality === 'single_per_context')
                                    <select name="context_key" class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
                                        @if ($definition->code === 'RES-033')
                                            <option value="title_presentation">Title Proposal / Title Presentation</option>
                                            <option value="proposal_defense">Proposal Defense</option>
                                            <option value="pre_final_defense">Pre-Final Defense</option>
                                            <option value="final_defense">Final Defense</option>
                                        @else
                                            <option value="proposal_defense">Proposal Defense</option>
                                            <option value="pre_final_defense">Pre-Final Defense</option>
                                            <option value="final_defense">Final Defense</option>
                                        @endif
                                    </select>
                                @endif
                                @if ($res026Locked)<p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-[10px] font-semibold text-amber-800">Your Title Proposal document must first be approved for Title Presentation.</p>@endif
                                @if ($res031Locked)<p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-[10px] font-semibold text-amber-800">Available after the Title Presentation is finalized and RES-026 is approved.</p>@endif
                            </div>
                            <button type="submit" @disabled($sourceBound || $blocked || $res026Locked || $res031Locked) class="mt-4 w-full rounded-xl bg-[#0e5c3a] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#073823] disabled:cursor-not-allowed disabled:bg-gray-300 cursor-pointer">
                                {{ ($res026Locked || $res031Locked) ? 'Locked' : ($blocked ? 'Blocked pending verification' : ($sourceBound ? 'Open from source record' : 'Create / Open Form')) }}
                            </button>
                        </form>
                    @endif
                @endforeach
            </div>
        </section>
    </main>
    @livewireScripts
</body>
</html>
