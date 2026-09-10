@props(['statistics'])

@php
    $filters = $statistics['filters'];
    $options = $statistics['options'];
    $kpis = $statistics['kpis'];
    $operations = $statistics['operations'];
    $maxLifecycle = max(1, collect($statistics['lifecycle'])->max('count') ?? 1);
    $attentionTones = [
        'rose' => 'border-rose-200 bg-rose-50 text-rose-800',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-800',
        'violet' => 'border-violet-200 bg-violet-50 text-violet-800',
        'slate' => 'border-slate-200 bg-slate-50 text-slate-700',
    ];
@endphp

<div class="space-y-7">
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
        <div>
            <div class="flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                <span>Dashboard</span><span>/</span><span class="text-[#0e5c3a]">Research Statistics</span>
            </div>
            <h1 class="mt-2 text-2xl font-black text-slate-900">Live Research Analytics</h1>
            <p class="mt-1 text-sm text-slate-500">Operational trends for {{ strtolower($statistics['scope_label']) }}. Detailed records and exports remain in Research Reports.</p>
        </div>
        <a href="{{ route('facilitator.reports.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#0e5c3a]/20 bg-white px-4 py-2.5 text-xs font-black text-[#0e5c3a] shadow-sm hover:bg-emerald-50">
            <i class="ph ph-file-text"></i> Open Detailed Reports
        </a>
    </div>

    <form method="GET" action="{{ route('facilitator.dashboard') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <input type="hidden" name="tab" value="statistics">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Academic year
                <select name="statistics_academic_year_id" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-700">
                    @forelse($options['years'] as $year)
                        <option value="{{ $year->id }}" @selected($filters['academic_year_id'] === (int) $year->id)>{{ $year->name }}</option>
                    @empty
                        <option value="">No academic years</option>
                    @endforelse
                </select>
            </label>
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Term
                <select name="statistics_academic_term_id" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-700">
                    <option value="">All terms</option>
                    @foreach($options['terms'] as $term)
                        <option value="{{ $term->id }}" @selected($filters['academic_term_id'] === (int) $term->id)>{{ $term->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Program
                <select name="statistics_program_id" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-700">
                    <option value="">All programs</option>
                    @foreach($options['programs'] as $program)
                        <option value="{{ $program->id }}" @selected($filters['program_id'] === (int) $program->id)>{{ $program->code }} — {{ $program->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Class
                <select name="statistics_research_class_id" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-700">
                    <option value="">All classes</option>
                    @foreach($options['classes'] as $class)
                        <option value="{{ $class->id }}" @selected($filters['research_class_id'] === (int) $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Adviser
                <select name="statistics_adviser_id" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-700">
                    <option value="">All advisers</option>
                    @foreach($options['advisers'] as $adviser)
                        <option value="{{ $adviser->id }}" @selected($filters['adviser_id'] === (int) $adviser->id)>{{ $adviser->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <a href="{{ route('facilitator.dashboard', ['tab' => 'statistics']) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-black text-slate-600 hover:bg-slate-50">Reset</a>
            <button type="submit" class="rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-xs font-black text-white shadow-sm hover:bg-[#09472d]">Apply Filters</button>
        </div>
    </form>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
        @foreach([
            ['Total Groups', $kpis['total'], 'ph-users-three', 'text-[#0e5c3a]', 'bg-emerald-50'],
            ['Completed', $kpis['completed'], 'ph-check-circle', 'text-blue-700', 'bg-blue-50'],
            ['In Progress', $kpis['in_progress'], 'ph-spinner-gap', 'text-amber-700', 'bg-amber-50'],
            ['Blocked', $kpis['blocked'], 'ph-lock-key', 'text-rose-700', 'bg-rose-50'],
            ['Overdue', $kpis['overdue'], 'ph-warning-circle', 'text-orange-700', 'bg-orange-50'],
            ['Completion Rate', $kpis['completion_rate'].'%', 'ph-chart-line-up', 'text-violet-700', 'bg-violet-50'],
        ] as [$label, $value, $icon, $textClass, $bgClass])
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $bgClass }} {{ $textClass }}"><i class="ph {{ $icon }}"></i></span>
                <p class="mt-4 text-[10px] font-black uppercase tracking-wider text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-black text-slate-900">{{ $value }}</p>
            </article>
        @endforeach
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div><h2 class="font-black text-slate-900">Groups by Program</h2><p class="mt-1 text-xs text-slate-500">Distribution within the selected scope</p></div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-black text-emerald-700">{{ $kpis['total'] }} groups</span>
            </div>
            <div class="mt-6 space-y-4">
                @forelse($statistics['programs'] as $program)
                    <div>
                        <div class="mb-2 flex justify-between text-xs"><span class="font-bold text-slate-600">{{ $program['name'] }}</span><span class="font-black text-slate-900">{{ $program['count'] }}</span></div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-[#0e5c3a]" style="width: {{ $program['percentage'] }}%"></div></div>
                    </div>
                @empty
                    <p class="py-12 text-center text-sm text-slate-400">No research groups match the selected filters.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div><h2 class="font-black text-slate-900">Current Lifecycle Stage</h2><p class="mt-1 text-xs text-slate-500">Uses the same authoritative journey as student dashboards and monitoring</p></div>
            <div class="mt-6 space-y-3">
                @forelse($statistics['lifecycle'] as $stage)
                    <div class="grid grid-cols-[minmax(0,1fr)_3rem] items-center gap-3">
                        <div>
                            <div class="mb-1.5 truncate text-xs font-bold text-slate-600" title="{{ $stage['label'] }}">{{ $stage['label'] }}</div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-amber-400" style="width: {{ (int) round(($stage['count'] / $maxLifecycle) * 100) }}%"></div></div>
                        </div>
                        <span class="text-right text-sm font-black text-slate-900">{{ $stage['count'] }}</span>
                    </div>
                @empty
                    <p class="py-12 text-center text-sm text-slate-400">No lifecycle data is available.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div><h2 class="font-black text-slate-900">Monthly Document Submissions</h2><p class="mt-1 text-xs text-slate-500">Actual submitted manuscripts during the selected academic year</p></div>
            <span class="text-xs font-bold text-slate-500">Average completion time: <strong class="text-slate-900">{{ $kpis['average_duration_months'] !== null ? $kpis['average_duration_months'].' months' : 'Not enough completed data' }}</strong></span>
        </div>
        <div class="mt-6 flex h-52 items-end gap-2 overflow-x-auto border-b border-slate-200 px-1 pb-1">
            @foreach($statistics['monthly_submissions'] as $month)
                <div class="flex h-full min-w-10 flex-1 flex-col items-center justify-end gap-2" title="{{ $month['label'] }}: {{ $month['count'] }} submission(s)">
                    <span class="text-[10px] font-black text-slate-600">{{ $month['count'] }}</span>
                    <div class="w-full max-w-12 rounded-t-lg bg-[#0e5c3a] transition-all" style="height: {{ max(3, $month['percentage']) }}%"></div>
                    <span class="text-[9px] font-bold uppercase text-slate-400">{{ $month['label'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_.85fr]">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-black text-slate-900">Operational Workload</h2>
            <p class="mt-1 text-xs text-slate-500">Work currently moving through review, forms, revisions, and defenses</p>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['Documents awaiting review', $operations['documents_awaiting_review'], 'ph-file-magnifying-glass'],
                    ['Open revisions', $operations['revisions_open'], 'ph-arrows-clockwise'],
                    ['Overdue revisions', $operations['revisions_overdue'], 'ph-clock-countdown'],
                    ['Scheduled defenses', $operations['defenses_scheduled'], 'ph-calendar-check'],
                    ['Completed defenses', $operations['defenses_completed'], 'ph-seal-check'],
                    ['Forms awaiting action', $operations['forms_awaiting_action'], 'ph-signature'],
                ] as [$label, $value, $icon])
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3"><i class="ph {{ $icon }} text-lg text-[#0e5c3a]"></i><span class="text-xl font-black text-slate-900">{{ $value }}</span></div>
                        <p class="mt-3 text-xs font-bold text-slate-600">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-black text-slate-900">Attention Needed</h2>
            <p class="mt-1 text-xs text-slate-500">Issues requiring facilitator follow-up</p>
            <div class="mt-5 space-y-3">
                @foreach($statistics['attention'] as $item)
                    <a href="{{ route('facilitator.dashboard', ['tab' => $item['tab']]) }}" class="flex items-center justify-between gap-4 rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:shadow-sm {{ $attentionTones[$item['tone']] }}">
                        <div><p class="text-xs font-black">{{ $item['label'] }}</p><p class="mt-1 text-[10px] font-semibold opacity-75">{{ $item['description'] }}</p></div>
                        <span class="text-2xl font-black">{{ $item['count'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>

    <p class="text-right text-[10px] font-semibold text-slate-400">Updated {{ $statistics['generated_at']->format('M j, Y g:i A') }}</p>
</div>
