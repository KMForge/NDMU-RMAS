@extends('layouts.blank')

@section('content')
@php
    $workspace = str(request()->route()->getName())->before('.');
    $query = request()->except('page');
    $unique = fn($key, $label) => $options->filter(fn($item) => $item->{$key} !== null)->unique($key)->sortBy($label);
@endphp
<main class="min-h-screen bg-[#f4f7f6] px-4 py-8 sm:px-8">
<div class="mx-auto max-w-7xl">
    <section class="rounded-3xl bg-[#0e5c3a] p-7 text-white shadow-sm">
        <p class="text-xs font-black uppercase tracking-[.2em] text-amber-300">CEAC · {{ $scope->label() }}</p>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-4"><div><h1 class="text-3xl font-black">{{ $definition['title'] }}</h1><p class="mt-1 text-sm text-emerald-100">{{ $definition['description'] }}</p></div><a href="{{ route($workspace.'.reports.index') }}" class="rounded-xl border border-white/30 px-4 py-2 text-sm font-bold">Report Catalog</a></div>
    </section>

    <form method="GET" class="mt-6 grid gap-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:grid-cols-4">
        @if(in_array('academic_year_id', $definition['filters']))<select name="academic_year_id" class="rounded-xl border-gray-300 text-sm"><option value="">All academic years</option>@foreach($unique('year_id','year_name') as $item)<option value="{{ $item->year_id }}" @selected(request('academic_year_id') == $item->year_id)>{{ $item->year_name }}</option>@endforeach</select>@endif
        @if(in_array('academic_term_id', $definition['filters']))<select name="academic_term_id" class="rounded-xl border-gray-300 text-sm"><option value="">All terms</option>@foreach($unique('term_id','term_name') as $item)<option value="{{ $item->term_id }}" @selected(request('academic_term_id') == $item->term_id)>{{ $item->term_name }}</option>@endforeach</select>@endif
        @if(in_array('program_id', $definition['filters']))<select name="program_id" class="rounded-xl border-gray-300 text-sm"><option value="">All CEAC programs</option>@foreach($unique('program_id','program_name') as $item)<option value="{{ $item->program_id }}" @selected(request('program_id') == $item->program_id)>{{ $item->program_name }}</option>@endforeach</select>@endif
        @if(in_array('research_class_id', $definition['filters']))<select name="research_class_id" class="rounded-xl border-gray-300 text-sm"><option value="">All authorized classes</option>@foreach($unique('research_class_id','class_name') as $item)<option value="{{ $item->research_class_id }}" @selected(request('research_class_id') == $item->research_class_id)>{{ $item->class_name }}</option>@endforeach</select>@endif
        @if(in_array('adviser_id', $definition['filters']))<select name="adviser_id" class="rounded-xl border-gray-300 text-sm"><option value="">All advisers</option>@foreach($unique('adviser_id','adviser_name') as $item)<option value="{{ $item->adviser_id }}" @selected(request('adviser_id') == $item->adviser_id)>{{ $item->adviser_name }}</option>@endforeach</select>@endif
        @if(in_array('stage', $definition['filters']))<select name="stage" class="rounded-xl border-gray-300 text-sm"><option value="">All stages</option>@foreach($milestoneDefinitions as $milestone)<option value="{{ $milestone->code }}" @selected(request('stage') === $milestone->code)>{{ $milestone->name }}</option>@endforeach</select>@endif
        @if(in_array('status', $definition['filters']))<select name="status" class="rounded-xl border-gray-300 text-sm"><option value="">All statuses</option>@foreach(['active','completed','overdue','delayed'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>@endforeach</select>@endif
        @if(in_array('date_from', $definition['filters']))<input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-xl border-gray-300 text-sm" aria-label="From date">@endif
        @if(in_array('date_to', $definition['filters']))<input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-xl border-gray-300 text-sm" aria-label="To date">@endif
        <div class="flex gap-2"><button class="rounded-xl bg-[#0e5c3a] px-5 py-2 text-sm font-bold text-white">Apply</button><a href="{{ route($workspace.'.reports.show', $report) }}" class="rounded-xl border border-gray-300 px-5 py-2 text-sm font-bold">Reset</a></div>
    </form>

    <div class="mt-5 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-slate-600"><strong>{{ $result['summary']['row_count'] }}</strong> result rows</p>@can('reports.export')<div class="flex gap-2"><a href="{{ route($workspace.'.reports.csv', [$report] + $query) }}" class="rounded-xl border border-emerald-700 bg-white px-4 py-2 text-sm font-bold text-emerald-800">Export CSV</a><a href="{{ route($workspace.'.reports.pdf', [$report] + $query) }}" class="rounded-xl bg-violet-600 px-4 py-2 text-sm font-bold text-white">Export PDF</a></div>@endcan</div>
    @if(isset($result['summary']['definition']))<p class="mt-3 rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900">{{ $result['summary']['definition'] }}</p>@endif

    <section class="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-[#0e5c3a] text-xs uppercase tracking-wider text-white"><tr>@foreach($definition['columns'] as $column)<th class="px-5 py-4">{{ $column }}</th>@endforeach</tr></thead><tbody class="divide-y divide-gray-100">@forelse($paginator as $row)<tr class="hover:bg-emerald-50/60">@foreach($definition['columns'] as $column)<td class="whitespace-nowrap px-5 py-4 text-slate-700">{{ $row[$column] ?? '—' }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($definition['columns']) }}" class="px-5 py-16 text-center text-slate-500">No authorized records match the selected filters.</td></tr>@endforelse</tbody></table></div>
        @if($paginator->hasPages())<div class="border-t border-gray-100 p-4">{{ $paginator->links() }}</div>@endif
    </section>
</div>
</main>
@endsection
