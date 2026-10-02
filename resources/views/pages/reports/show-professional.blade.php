@extends('layouts.blank')

@section('content')
@php
    $workspace = str(request()->route()->getName())->before('.')->toString();
    $user = auth()->user();
    $hasSidebar = in_array($workspace, ['dean', 'facilitator'], true);
    $workspaceLabel = match ($workspace) { 'dean' => 'College Dean', 'facilitator' => 'Research Facilitator', default => 'System Administration' };
    $query = request()->except('page');
    $unique = fn ($key, $label) => $options->filter(fn ($item) => $item->{$key} !== null)->unique($key)->sortBy($label);
    $activeFilterCount = collect($filters->toArray())->filter(fn ($value) => filled($value))->count();
    $controlClass = 'w-full rounded-xl border-slate-300 bg-white text-sm focus:border-emerald-700 focus:ring-emerald-700';
@endphp

<div data-portal-shell class="min-h-screen bg-[#f3f6f5]">
    @if ($workspace === 'dean')
        <x-dean-sidebar active="reports" />
    @elseif ($workspace === 'facilitator')
        <x-facilitator-sidebar :user="$user" active="reports" />
    @endif

    <div @if($hasSidebar) data-portal-content @endif @class(['min-h-screen', 'pl-72' => $hasSidebar])>
        <header @if($hasSidebar) data-portal-header @endif class="sticky top-0 z-20 border-b border-slate-200/90 bg-white/95 shadow-sm backdrop-blur">
            <div class="flex min-h-20 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3"><a href="{{ route($workspace.'.reports.index') }}" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-emerald-300 hover:text-emerald-800" aria-label="Back to report library"><i class="ph ph-arrow-left"></i></a><div class="min-w-0"><p class="truncate text-[10px] font-black uppercase tracking-[.16em] text-[#0e5c3a]">Reports &amp; Analytics</p><h1 class="truncate text-lg font-black text-slate-900 sm:text-xl">{{ $definition['title'] }}</h1></div></div>
                <div class="flex shrink-0 items-center gap-2 sm:gap-3"><x-workspace-switcher :current="$workspace" /><x-notification-dropdown /><div class="hidden items-center gap-2 border-l border-slate-200 pl-3 md:flex"><div class="h-9 w-9 overflow-hidden rounded-xl bg-amber-400 text-emerald-950"><x-current-user-avatar :user="$user" rounded="rounded-xl" /></div><div class="max-w-36 min-w-0 leading-tight"><p class="truncate text-xs font-black text-slate-900">{{ $user->displayFirstName() }}</p><p class="mt-0.5 truncate text-[9px] font-semibold uppercase tracking-wide text-slate-400">{{ $workspaceLabel }}</p></div></div></div>
            </div>
        </header>

        <main @if($hasSidebar) data-portal-main @endif class="px-4 py-5 sm:px-6 sm:py-7 lg:px-8">
            <div class="mx-auto max-w-[1500px] space-y-5">
                <section class="relative overflow-hidden rounded-3xl bg-[#07482e] px-5 py-6 text-white shadow-[0_16px_40px_-24px_rgba(4,61,39,.65)] sm:px-8 sm:py-8">
                    <div class="pointer-events-none absolute -right-10 -top-24 h-72 w-72 rounded-full border-[42px] border-white/[.04]"></div>
                    <div class="relative flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
                        <div class="max-w-3xl"><div class="flex flex-wrap items-center gap-2"><span class="rounded-full border border-amber-300/30 bg-black/10 px-3 py-1 text-[10px] font-black uppercase tracking-[.16em] text-amber-300">Analytical report</span><span class="text-xs font-semibold text-emerald-100">{{ $scope->label() }}</span></div><h2 class="mt-4 text-2xl font-black tracking-tight sm:text-3xl">{{ $definition['title'] }}</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-emerald-100">{{ $definition['description'] }}</p></div>
                        <div class="grid grid-cols-2 gap-3 sm:flex"><div class="rounded-2xl border border-white/15 bg-white/[.08] px-4 py-3"><p class="text-[9px] font-black uppercase tracking-widest text-emerald-200">Result rows</p><p class="mt-1 text-xl font-black">{{ number_format($result['summary']['row_count']) }}</p></div><div class="rounded-2xl border border-white/15 bg-white/[.08] px-4 py-3"><p class="text-[9px] font-black uppercase tracking-widest text-emerald-200">Active filters</p><p class="mt-1 text-xl font-black">{{ $activeFilterCount }}</p></div></div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="report-filters">
                    <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 id="report-filters" class="flex items-center gap-2 font-black text-slate-900"><i class="ph ph-funnel text-emerald-700"></i>Report filters</h2><p class="mt-1 text-xs text-slate-500">Narrow the report using the available academic and operational fields.</p></div>@if ($activeFilterCount > 0)<span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-[10px] font-black uppercase tracking-wide text-amber-800"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>{{ $activeFilterCount }} applied</span>@endif</div>
                    <form method="GET" class="p-5">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            @if(in_array('academic_year_id', $definition['filters']))<label class="block"><span class="mb-1.5 block text-[10px] font-black uppercase tracking-wide text-slate-500">Academic year</span><select name="academic_year_id" class="{{ $controlClass }}"><option value="">All academic years</option>@foreach($unique('year_id','year_name') as $item)<option value="{{ $item->year_id }}" @selected(request('academic_year_id') == $item->year_id)>{{ $item->year_name }}</option>@endforeach</select></label>@endif
                            @if(in_array('academic_term_id', $definition['filters']))<label class="block"><span class="mb-1.5 block text-[10px] font-black uppercase tracking-wide text-slate-500">Academic term</span><select name="academic_term_id" class="{{ $controlClass }}"><option value="">All terms</option>@foreach($unique('term_id','term_name') as $item)<option value="{{ $item->term_id }}" @selected(request('academic_term_id') == $item->term_id)>{{ $item->term_name }}</option>@endforeach</select></label>@endif
                            @if(in_array('program_id', $definition['filters']))<label class="block"><span class="mb-1.5 block text-[10px] font-black uppercase tracking-wide text-slate-500">Program</span><select name="program_id" class="{{ $controlClass }}"><option value="">All authorized programs</option>@foreach($unique('program_id','program_name') as $item)<option value="{{ $item->program_id }}" @selected(request('program_id') == $item->program_id)>{{ $item->program_name }}</option>@endforeach</select></label>@endif
                            @if(in_array('research_class_id', $definition['filters']))<label class="block"><span class="mb-1.5 block text-[10px] font-black uppercase tracking-wide text-slate-500">Research class</span><select name="research_class_id" class="{{ $controlClass }}"><option value="">All authorized classes</option>@foreach($unique('research_class_id','class_name') as $item)<option value="{{ $item->research_class_id }}" @selected(request('research_class_id') == $item->research_class_id)>{{ $item->class_name }}</option>@endforeach</select></label>@endif
                            @if(in_array('adviser_id', $definition['filters']))<label class="block"><span class="mb-1.5 block text-[10px] font-black uppercase tracking-wide text-slate-500">Research adviser</span><select name="adviser_id" class="{{ $controlClass }}"><option value="">All advisers</option>@foreach($unique('adviser_id','adviser_name') as $item)<option value="{{ $item->adviser_id }}" @selected(request('adviser_id') == $item->adviser_id)>{{ $item->adviser_name }}</option>@endforeach</select></label>@endif
                            @if(in_array('stage', $definition['filters']))<label class="block"><span class="mb-1.5 block text-[10px] font-black uppercase tracking-wide text-slate-500">Research stage</span><select name="stage" class="{{ $controlClass }}"><option value="">All stages</option>@foreach($milestoneDefinitions as $milestone)<option value="{{ $milestone->code }}" @selected(request('stage') === $milestone->code)>{{ $milestone->name }}</option>@endforeach</select></label>@endif
                            @if(in_array('status', $definition['filters']))<label class="block"><span class="mb-1.5 block text-[10px] font-black uppercase tracking-wide text-slate-500">Record status</span><select name="status" class="{{ $controlClass }}"><option value="">All statuses</option>@foreach(['active','completed','overdue','delayed'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>@endforeach</select></label>@endif
                            @if(in_array('date_from', $definition['filters']))<label class="block"><span class="mb-1.5 block text-[10px] font-black uppercase tracking-wide text-slate-500">From date</span><input type="date" name="date_from" value="{{ request('date_from') }}" class="{{ $controlClass }}"></label>@endif
                            @if(in_array('date_to', $definition['filters']))<label class="block"><span class="mb-1.5 block text-[10px] font-black uppercase tracking-wide text-slate-500">To date</span><input type="date" name="date_to" value="{{ request('date_to') }}" class="{{ $controlClass }}"></label>@endif
                        </div>
                        <div class="mt-5 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end"><a href="{{ route($workspace.'.reports.show', $report) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50"><i class="ph ph-arrow-counter-clockwise"></i>Reset filters</a><button class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#09472d]"><i class="ph ph-funnel"></i>Apply filters</button></div>
                    </form>
                </section>

                @if(isset($result['summary']['definition']))<div class="flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs leading-5 text-blue-900"><i class="ph ph-info mt-0.5 shrink-0 text-lg"></i><p>{{ $result['summary']['definition'] }}</p></div>@endif

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="report-results">
                    <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><div class="flex items-center gap-2"><h2 id="report-results" class="font-black text-slate-900">Report results</h2><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-600">{{ number_format($result['summary']['row_count']) }}</span></div><p class="mt-1 text-xs text-slate-500">Generated {{ now()->format('M j, Y \a\t g:i A') }} from your authorized scope.</p></div>@can('reports.export')<div class="grid grid-cols-2 gap-2 sm:flex"><a href="{{ route($workspace.'.reports.csv', [$report] + $query) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-black text-emerald-800 transition hover:bg-emerald-100"><i class="ph ph-file-csv text-base"></i>Export CSV</a><a href="{{ route($workspace.'.reports.pdf', [$report] + $query) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-black text-white transition hover:bg-slate-800"><i class="ph ph-file-pdf text-base"></i>Export PDF</a></div>@endcan</div>
                    <div data-responsive-table-container tabindex="0" aria-label="Report results. Scroll horizontally for all columns." class="overflow-x-auto"><table data-responsive-table class="w-full min-w-[760px] text-left text-sm"><thead><tr class="border-b border-slate-200 bg-slate-50">@foreach($definition['columns'] as $column)<th scope="col" class="whitespace-nowrap px-5 py-3.5 text-[10px] font-black uppercase tracking-[.1em] text-slate-600">{{ $column }}</th>@endforeach</tr></thead><tbody class="divide-y divide-slate-100">@forelse($paginator as $row)<tr class="transition hover:bg-emerald-50/40">@foreach($definition['columns'] as $column)<td class="px-5 py-4 font-medium text-slate-700">{{ $row[$column] ?? 'Not available' }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($definition['columns']) }}" class="px-5 py-16 text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-2xl text-slate-400"><i class="ph ph-magnifying-glass"></i></span><p class="mt-3 font-bold text-slate-700">No matching records</p><p class="mt-1 text-xs text-slate-500">No authorized records match the selected filters.</p></td></tr>@endforelse</tbody></table></div>
                    @if($paginator->hasPages())<div class="border-t border-slate-200 bg-slate-50/60 p-4">{{ $paginator->links() }}</div>@endif
                </section>
            </div>
        </main>
    </div>
</div>
@endsection
