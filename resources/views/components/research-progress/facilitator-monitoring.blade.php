@props(['groups', 'search' => '', 'groupStatus' => 'active', 'groupId' => null, 'allFilterGroups' => null, 'readOnly' => false, 'formAction' => null])

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col gap-1">
        <div class="flex items-center gap-2 text-[10px] text-slate-400 font-bold uppercase tracking-wider">
            <span>Facilitator Portal</span>
            <span>/</span>
            <span class="text-[#0e5c3a]">Research Monitoring</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black font-heading text-slate-900 tracking-tight">
            Research Lifecycle Monitoring
        </h1>
        <p class="text-xs text-slate-500 font-medium">
            Track student group progress across all verified institutional milestones from title proposal to final archiving.
        </p>
    </div>

    <!-- Search & Filters Bar -->
    <form method="GET" action="{{ $formAction ?? route('facilitator.dashboard') }}" class="bg-white rounded-3xl border border-slate-200/80 p-4 sm:p-5 shadow-sm flex flex-wrap items-center gap-3">
        <input type="hidden" name="tab" value="monitoring">
        
        <div class="relative flex-1 min-w-64">
            <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input
                name="progress_search"
                value="{{ $search }}"
                maxlength="100"
                placeholder="Search research group, student name, or research title..."
                class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 hover:bg-white focus:bg-white pl-9 pr-4 py-2.5 text-xs font-medium text-slate-900 placeholder-slate-400 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 focus:outline-none transition-all"
            >
        </div>
        
        @if (isset($allFilterGroups) && count($allFilterGroups) > 0)
            <select
                name="progress_group_id"
                onchange="this.form.submit()"
                class="rounded-2xl border border-slate-200 bg-slate-50/70 hover:bg-white focus:bg-white px-4 py-2.5 text-xs font-bold text-slate-700 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 focus:outline-none transition-all cursor-pointer"
            >
                <option value="">All Research Groups</option>
                @foreach ($allFilterGroups as $fg)
                    <option value="{{ $fg->id }}" @selected((int) (request()->query('progress_group_id') ?? $groupId) === (int) $fg->id)>
                        {{ $fg->name }}{{ $fg->title ? ' — '.\Illuminate\Support\Str::limit($fg->title, 35) : '' }}
                    </option>
                @endforeach
            </select>
        @endif

        <select
            name="progress_group_status"
            onchange="this.form.submit()"
            class="rounded-2xl border border-slate-200 bg-slate-50/70 hover:bg-white focus:bg-white px-4 py-2.5 text-xs font-bold text-slate-700 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 focus:outline-none transition-all cursor-pointer"
        >
            <option value="active" @selected($groupStatus === 'active')>Active Groups</option>
            <option value="disbanded" @selected($groupStatus === 'disbanded')>Disbanded History</option>
        </select>

        <button
            type="submit"
            class="rounded-2xl bg-gradient-to-r from-[#073823] to-[#0e5c3a] hover:brightness-110 px-5 py-2.5 text-xs font-black text-white shadow-sm hover:shadow transition-all cursor-pointer"
        >
            Filter
        </button>
    </form>

    <!-- Group Progress Cards -->
    <div
        class="space-y-8"
        data-research-monitoring-groups
        x-data="{
            refreshTimer: null,
            refreshing: false,
            async refreshFromServer() {
                if (this.refreshing || document.visibilityState !== 'visible') return;
                this.refreshing = true;

                try {
                    const response = await fetch(window.location.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        cache: 'no-store',
                    });
                    if (!response.ok) return;

                    const documentCopy = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const updatedGroups = documentCopy.querySelector('[data-research-monitoring-groups]');
                    if (updatedGroups && updatedGroups.innerHTML !== this.$root.innerHTML) {
                        this.$root.innerHTML = updatedGroups.innerHTML;
                    }
                } finally {
                    this.refreshing = false;
                }
            },
            init() {
                this.refreshTimer = window.setInterval(() => this.refreshFromServer(), 15000);
            },
            destroy() {
                window.clearInterval(this.refreshTimer);
            },
        }"
        @focus.window.debounce.750ms="refreshFromServer()"
    >
        @forelse ($groups as $group)
            @php $summary = $group->progress_summary; @endphp
            <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <!-- Top Brand Accent Stripe -->
                <div class="h-1.5 bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>

                <!-- Group Header -->
                <div class="relative z-[1] flex flex-col justify-between gap-6 border-b border-slate-100 bg-white p-6 sm:p-7 lg:flex-row lg:items-center">
                    <div class="space-y-2.5 max-w-3xl">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-[10px] font-black uppercase tracking-wider text-[#0e5c3a] border border-emerald-200">
                                {{ $group->researchClass?->name }}
                            </span>
                            <span class="text-xs font-bold text-slate-500">
                                Cohort: <strong class="text-slate-900">{{ $group->name }}</strong>
                            </span>
                        </div>

                        <h2 class="text-lg sm:text-xl font-black font-heading text-slate-900 leading-snug">
                            {{ $group->title ?: $group->name }}
                        </h2>

                        <div class="pt-1 flex flex-wrap items-center gap-y-2 gap-x-4 text-xs text-slate-600">
                            <div class="flex items-center gap-1.5 font-medium">
                                <i class="ph ph-chalkboard-teacher text-base text-[#0e5c3a]"></i>
                                <span>Adviser: <strong class="text-slate-900">{{ $group->adviser?->name ?? 'Not assigned' }}</strong></span>
                            </div>

                            <span class="text-slate-300">•</span>

                            <div class="flex items-center gap-2 flex-wrap">
                                <i class="ph ph-users text-base text-[#0e5c3a]"></i>
                                <span class="font-bold text-slate-500">Members ({{ $group->members->count() }}):</span>
                                @if ($group->members->isNotEmpty())
                                    <div class="inline-flex flex-wrap items-center gap-1.5">
                                        @foreach ($group->members as $member)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-white border border-slate-200 text-xs font-medium text-slate-800 shadow-2xs">
                                                @if ($member->student_id === $group->leader_student_id)
                                                    <i class="ph ph-crown text-amber-500 text-xs font-bold" title="Leader"></i>
                                                @endif
                                                <span class="{{ $member->student_id === $group->leader_student_id ? 'font-black text-slate-900' : 'text-slate-700' }}">
                                                    {{ $member->student?->name ?? 'Student #'.$member->student_id }}
                                                </span>
                                                @if ($member->student_id === $group->leader_student_id)
                                                    <span class="text-[9px] font-black text-amber-800 bg-amber-100 px-1.5 py-0.2 rounded-md">Leader</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-slate-400 italic">No members assigned</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Progress Percentage Dial & Stats -->
                    <div class="flex items-center gap-4 lg:flex-col lg:items-end shrink-0">
                        <div class="text-left lg:text-right">
                            <span class="text-3xl sm:text-4xl font-black font-heading text-[#0e5c3a] tracking-tight block">
                                {{ number_format($summary['progress_percentage'], 0) }}%
                            </span>
                            <span class="text-[11px] font-bold text-slate-500 block mt-0.5">
                                {{ $summary['completed_count'] }} of {{ $summary['applicable_count'] }} Milestones
                            </span>
                            @if (isset($summary['journey']))
                                <span class="mt-1 block text-[10px] font-semibold text-slate-400">
                                    Current: Stage {{ $summary['journey']['current_stage'] }} &mdash; {{ $summary['journey']['current_stage_name'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Thin Milestone Progression Bar -->
                <div class="h-2 w-full bg-slate-100">
                    <div
                        class="h-full bg-gradient-to-r from-[#073823] via-[#0e5c3a] to-[#eebc3f] transition-all duration-500"
                        style="width: {{ $summary['progress_percentage'] }}%"
                    ></div>
                </div>

                <!-- Milestones Timeline Grid -->
                <div class="p-6 sm:p-7 space-y-4">
                    <div class="grid grid-cols-1 gap-3.5">
                        @foreach ($summary['milestones'] as $milestone)
                            @php
                                $persistedStatusVal = $milestone->status->value;
                                $journeyStage = $summary['journey']['stages'][$milestone->definition->sequence] ?? null;
                                $statusVal = $journeyStage
                                    ? (($journeyStage['is_optional'] ?? false) && ! $journeyStage['is_completed']
                                        ? 'optional'
                                        : ($journeyStage['is_completed']
                                        ? 'completed'
                                        : (($summary['journey']['current_stage'] ?? null) === $milestone->definition->sequence ? 'in_progress' : 'pending')))
                                    : $persistedStatusVal;
                                $statusLabel = ($journeyStage['is_auto_completed'] ?? false)
                                    ? 'Auto-completed'
                                    : str($statusVal)->replace('_', ' ')->title();
                                $statusBadgeClass = match($statusVal) {
                                    'completed' => 'bg-emerald-50 text-[#0e5c3a] border-emerald-200',
                                    'in_progress' => 'bg-amber-50 text-amber-800 border-amber-200',
                                    'not_applicable' => 'bg-slate-100 text-slate-500 border-slate-200',
                                    'optional' => 'bg-violet-50 text-violet-700 border-violet-200',
                                    default => 'bg-slate-50 text-slate-600 border-slate-200',
                                };
                            @endphp
                            <article @class([
                                'rounded-2xl border p-4 sm:p-5 transition-all duration-200',
                                'border-emerald-200 bg-emerald-50/30 hover:border-emerald-300' => $statusVal === 'completed',
                                'border-amber-200 bg-amber-50/40 ring-2 ring-amber-400/20' => $statusVal === 'in_progress',
                                'border-slate-200/80 bg-white hover:border-slate-300' => $statusVal === 'pending',
                                'border-slate-200 bg-slate-50/60' => $statusVal === 'not_applicable',
                                'border-violet-200 bg-violet-50/30' => $statusVal === 'optional',
                            ])>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">
                                                Stage {{ $milestone->definition->sequence }} · Weight {{ number_format((float) $milestone->definition->weight, 1) }}
                                            </span>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $statusBadgeClass }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $statusVal === 'completed' ? 'bg-[#0e5c3a]' : ($statusVal === 'in_progress' ? 'bg-amber-500 animate-pulse' : 'bg-slate-400') }}"></span>
                                                {{ $statusLabel }}
                                            </span>
                                        </div>
                                        <h3 class="font-black font-heading text-slate-900 text-sm">
                                            {{ $milestone->definition->name }}
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 font-medium">
                                            @if ($milestone->due_at)
                                                <span>Due: <strong class="text-slate-700">{{ $milestone->due_at->format('M j, Y') }}</strong></span>
                                            @endif
                                            @if ($milestone->isOverdue())
                                                <span class="text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200">Overdue</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($milestone->evidences->isNotEmpty())
                                        <div class="flex flex-wrap gap-2 shrink-0">
                                            @foreach ($milestone->evidences as $evidence)
                                                <span class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50/80 px-3 py-1 text-[11px] font-bold text-[#0e5c3a] shadow-2xs" title="{{ $evidence->summary }}">
                                                    <i class="ph ph-seal-check text-emerald-600 text-sm"></i>
                                                    <span>{{ str($evidence->evidence_type)->headline() }} Verified</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                @if ($milestone->remarks)
                                    <p class="text-xs text-slate-600 mt-2.5 bg-white/90 p-3 rounded-xl border border-slate-200/80 font-medium leading-relaxed">
                                        {{ $milestone->remarks }}
                                    </p>
                                @endif

                                @if ($milestone->not_applicable_reason)
                                    <p class="text-xs text-slate-600 mt-2 font-medium">Reason: {{ $milestone->not_applicable_reason }}</p>
                                @endif

                                <!-- Administrative Override (Collapsible) -->
                                @if ($group->isActive() && ! $readOnly)
                                    <details class="mt-3.5 pt-3 border-t border-slate-200/60 text-xs group/override">
                                        <summary class="cursor-pointer font-bold text-slate-500 hover:text-slate-800 transition-colors list-none flex items-center gap-1.5">
                                            <i class="ph ph-sliders text-sm text-slate-400"></i>
                                            <span>Administrative Status Override</span>
                                        </summary>
                                        <div class="grid gap-3 mt-3 sm:grid-cols-3">
                                            <div class="space-y-2 rounded-xl border border-amber-200 bg-amber-50/70 p-3">
                                                <p class="text-xs font-bold text-amber-900">Manual Status Adjustment</p>
                                                <p class="text-[10px] text-amber-800 leading-tight">Milestones automatically sync via verified forms, submissions, and defenses.</p>
                                                @if ($milestone->status->value === 'pending')
                                                    <form method="POST" action="{{ route('facilitator.progress.start', $milestone) }}">
                                                        @csrf @method('PATCH')
                                                        <button class="text-xs font-black text-amber-900 hover:underline cursor-pointer">Start Manually</button>
                                                    </form>
                                                @elseif ($milestone->status->value === 'in_progress')
                                                    <form method="POST" action="{{ route('facilitator.progress.complete', $milestone) }}">
                                                        @csrf @method('PATCH')
                                                        <button class="text-xs font-black text-emerald-800 hover:underline cursor-pointer">Complete Manually</button>
                                                    </form>
                                                @else
                                                    <p class="text-[10px] text-slate-500 font-medium">No manual transition required.</p>
                                                @endif
                                            </div>

                                            <form method="POST" action="{{ route('facilitator.progress.due-date', $milestone) }}" class="space-y-2 rounded-xl border border-slate-200 bg-white p-3">
                                                @csrf @method('PATCH')
                                                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-700 block">Due Date</label>
                                                <input type="date" name="due_at" value="{{ $milestone->due_at?->format('Y-m-d') }}" class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs focus:outline-none">
                                                <input name="reason" maxlength="2000" placeholder="Reason (optional)" class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs focus:outline-none">
                                                <button class="text-xs font-bold text-[#0e5c3a] hover:underline cursor-pointer">Save Date</button>
                                            </form>

                                            @if (in_array($milestone->status->value, ['pending', 'in_progress'], true))
                                                <form method="POST" action="{{ route('facilitator.progress.not-applicable', $milestone) }}" class="space-y-2 rounded-xl border border-slate-200 bg-white p-3">
                                                    @csrf @method('PATCH')
                                                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-700 block">Mark N/A</label>
                                                    <textarea required name="reason" maxlength="2000" placeholder="Required reason" class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs focus:outline-none"></textarea>
                                                    <button class="text-xs font-bold text-slate-700 hover:underline cursor-pointer">Submit N/A</button>
                                                </form>
                                            @elseif ($milestone->status->value === 'completed')
                                                <form method="POST" action="{{ route('facilitator.progress.correct', $milestone) }}" class="space-y-2 rounded-xl border border-slate-200 bg-white p-3">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="status" value="in_progress">
                                                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-700 block">Status Correction</label>
                                                    <textarea required name="reason" maxlength="2000" placeholder="Required correction reason" class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs focus:outline-none"></textarea>
                                                    <button class="text-xs font-bold text-rose-700 hover:underline cursor-pointer">Return to In Progress</button>
                                                </form>
                                            @elseif ($milestone->status->value === 'not_applicable')
                                                <form method="POST" action="{{ route('facilitator.progress.correct', $milestone) }}" class="space-y-2 rounded-xl border border-slate-200 bg-white p-3">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="status" value="pending">
                                                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-700 block">Re-enable Milestone</label>
                                                    <textarea required name="reason" maxlength="2000" placeholder="Required correction reason" class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs focus:outline-none"></textarea>
                                                    <button class="text-xs font-bold text-blue-700 hover:underline cursor-pointer">Return to Pending</button>
                                                </form>
                                            @endif
                                        </div>
                                    </details>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @empty
            <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-white p-12 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center text-3xl mx-auto shadow-2xs">
                    <i class="ph ph-chart-line-up"></i>
                </div>
                <h2 class="text-base font-black text-slate-900">No Research Groups Found</h2>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    No active research cohorts match your filter criteria.
                </p>
            </div>
        @endforelse

        {{ $groups->links() }}
    </div>
</div>
