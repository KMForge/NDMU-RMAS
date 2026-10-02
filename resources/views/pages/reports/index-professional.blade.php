@extends('layouts.blank')

@section('content')
@php
    $workspace = str(request()->route()->getName())->before('.')->toString();
    $user = auth()->user();
    $hasSidebar = in_array($workspace, ['dean', 'facilitator'], true);
    $workspaceLabel = match ($workspace) {
        'dean' => 'College Dean',
        'facilitator' => 'Research Facilitator',
        default => 'System Administration',
    };
    $metricStyles = [
        ['bg-emerald-50', 'text-emerald-700', 'border-emerald-100'],
        ['bg-blue-50', 'text-blue-700', 'border-blue-100'],
        ['bg-violet-50', 'text-violet-700', 'border-violet-100'],
        ['bg-rose-50', 'text-rose-700', 'border-rose-100'],
        ['bg-amber-50', 'text-amber-700', 'border-amber-100'],
        ['bg-cyan-50', 'text-cyan-700', 'border-cyan-100'],
        ['bg-orange-50', 'text-orange-700', 'border-orange-100'],
        ['bg-slate-100', 'text-slate-700', 'border-slate-200'],
    ];
    $reportIcons = [
        'research-stage-status' => 'ph-chart-line-up', 'research-summary' => 'ph-chart-donut',
        'milestone-completion' => 'ph-flag-checkered', 'research-output-program' => 'ph-buildings',
        'research-throughput' => 'ph-trend-up', 'defense-types' => 'ph-presentation-chart',
        'defense-status' => 'ph-calendar-check', 'adviser-workload' => 'ph-users',
        'document-review-status' => 'ph-files', 'revision-summary' => 'ph-arrows-clockwise',
        'evaluation-release-status' => 'ph-seal-check',
    ];
    $categories = [
        ['title' => 'Research performance', 'description' => 'Portfolio health, lifecycle progress, output, and completion trends.', 'icon' => 'ph-chart-line-up', 'reports' => ['research-stage-status', 'research-summary', 'milestone-completion', 'research-output-program', 'research-throughput']],
        ['title' => 'Defense and evaluation', 'description' => 'Defense readiness, outcomes, and evaluation release status.', 'icon' => 'ph-presentation-chart', 'reports' => ['defense-types', 'defense-status', 'evaluation-release-status']],
        ['title' => 'Operational oversight', 'description' => 'Adviser capacity, document review queues, and revision workload.', 'icon' => 'ph-briefcase', 'reports' => ['adviser-workload', 'document-review-status', 'revision-summary']],
    ];
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
                <div class="flex min-w-0 items-center gap-3">
                    @unless ($hasSidebar)<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0e5c3a] text-lg text-white"><i class="ph ph-chart-line-up"></i></span>@endunless
                    <div class="min-w-0"><p class="truncate text-[10px] font-black uppercase tracking-[.16em] text-[#0e5c3a]">{{ $workspaceLabel }} Portal</p><h1 class="truncate text-lg font-black text-slate-900 sm:text-xl">Reports &amp; Analytics</h1></div>
                </div>
                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <a href="{{ route($workspace.'.dashboard') }}" class="hidden items-center gap-2 rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:border-emerald-300 hover:text-emerald-800 sm:inline-flex"><i class="ph ph-arrow-left"></i>Dashboard</a>
                    <x-workspace-switcher :current="$workspace" /><x-notification-dropdown />
                    <div class="hidden items-center gap-2 border-l border-slate-200 pl-3 md:flex"><div class="h-9 w-9 overflow-hidden rounded-xl bg-amber-400 text-emerald-950"><x-current-user-avatar :user="$user" rounded="rounded-xl" /></div><div class="max-w-36 min-w-0 leading-tight"><p class="truncate text-xs font-black text-slate-900">{{ $user->displayFirstName() }}</p><p class="mt-0.5 truncate text-[9px] font-semibold uppercase tracking-wide text-slate-400">{{ $workspaceLabel }}</p></div></div>
                </div>
            </div>
        </header>

        <main @if($hasSidebar) data-portal-main @endif class="px-4 py-5 sm:px-6 sm:py-7 lg:px-8">
            <div class="mx-auto max-w-[1500px] space-y-6">
                <section class="relative overflow-hidden rounded-3xl bg-[#07482e] px-5 py-6 text-white shadow-[0_16px_40px_-24px_rgba(4,61,39,.65)] sm:px-8 sm:py-8">
                    <div class="pointer-events-none absolute -right-10 -top-24 h-72 w-72 rounded-full border-[42px] border-white/[.04]"></div>
                    <div class="relative flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
                        <div class="max-w-3xl"><div class="inline-flex items-center gap-2 rounded-full border border-amber-300/30 bg-black/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[.18em] text-amber-300"><span class="h-1.5 w-1.5 rounded-full bg-amber-300"></span>Institutional reporting</div><h2 class="mt-4 text-2xl font-black tracking-tight sm:text-3xl">Research intelligence at a glance</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-emerald-100">Monitor research performance, identify work requiring attention, and generate decision-ready reports from live authorized records.</p></div>
                        <div class="grid grid-cols-2 gap-3 sm:flex"><div class="rounded-2xl border border-white/15 bg-white/[.08] px-4 py-3"><p class="text-[9px] font-black uppercase tracking-widest text-emerald-200">Access scope</p><p class="mt-1 max-w-48 truncate text-sm font-bold">{{ $scope->label() }}</p></div><div class="rounded-2xl border border-white/15 bg-white/[.08] px-4 py-3"><p class="text-[9px] font-black uppercase tracking-widest text-emerald-200">Data status</p><p class="mt-1 flex items-center gap-2 text-sm font-bold"><span class="h-2 w-2 rounded-full bg-emerald-300"></span>Live records</p></div></div>
                    </div>
                </section>

                <section aria-labelledby="reporting-snapshot">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-[10px] font-black uppercase tracking-[.18em] text-emerald-700">Portfolio overview</p><h2 id="reporting-snapshot" class="mt-1 text-xl font-black text-slate-900">Research reporting snapshot</h2></div><p class="max-w-xl text-xs leading-5 text-slate-500">Authoritative counts within {{ $scope->label() }}. Select a metric to review its supporting records.</p></div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ($summaryCards as $card)
                            @php([$surface, $ink, $border] = $metricStyles[$loop->index % count($metricStyles)])
                            <a href="{{ route($workspace.'.reports.show', $card['report']) }}" class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg hover:shadow-slate-200/60">
                                <div class="flex items-start justify-between gap-4"><div><p class="text-[10px] font-black uppercase tracking-[.12em] text-slate-500">{{ $card['label'] }}</p><p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ number_format($card['value']) }}</p></div><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border {{ $surface }} {{ $ink }} {{ $border }}"><i class="ph {{ $card['icon'] }} text-xl"></i></span></div>
                                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-[11px] font-bold text-slate-500"><span>View supporting report</span><i class="ph ph-arrow-up-right text-emerald-700 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"></i></div>
                            </a>
                        @endforeach
                    </div>
                </section>

                <section aria-labelledby="report-library" class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 sm:px-7 lg:flex-row lg:items-center lg:justify-between"><div><p class="text-[10px] font-black uppercase tracking-[.18em] text-emerald-700">Report library</p><h2 id="report-library" class="mt-1 text-xl font-black text-slate-900">Detailed reports and exports</h2><p class="mt-1 text-sm text-slate-500">Open a report to filter, inspect, and export its authorized dataset.</p></div><div class="inline-flex w-fit items-center gap-2 rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600"><i class="ph ph-files text-base text-emerald-700"></i>{{ count($reports) }} available reports</div></div>
                    <div class="grid gap-5 p-5 sm:p-7 xl:grid-cols-3">
                        @foreach ($categories as $category)
                            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/60">
                                <div class="border-b border-slate-200 bg-white px-5 py-4"><div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-lg text-emerald-700"><i class="ph {{ $category['icon'] }}"></i></span><div><h3 class="font-black text-slate-900">{{ $category['title'] }}</h3><p class="mt-1 text-xs leading-5 text-slate-500">{{ $category['description'] }}</p></div></div></div>
                                <div class="divide-y divide-slate-200">
                                    @foreach ($category['reports'] as $identifier)
                                        @php($definition = $reports[$identifier])
                                        <a href="{{ route($workspace.'.reports.show', $identifier) }}" class="group flex items-center gap-3 px-5 py-4 transition hover:bg-emerald-50/70"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition group-hover:border-emerald-200 group-hover:text-emerald-700"><i class="ph {{ $reportIcons[$identifier] ?? 'ph-file-text' }}"></i></span><span class="min-w-0 flex-1"><span class="block text-sm font-extrabold text-slate-800 group-hover:text-emerald-900">{{ $definition['title'] }}</span><span class="mt-0.5 block truncate text-[11px] text-slate-500">{{ $definition['description'] }}</span></span><i class="ph ph-caret-right shrink-0 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-emerald-700"></i></a>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
@endsection
