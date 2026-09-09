@extends('layouts.blank')

@section('content')
@php($workspace = str(request()->route()->getName())->before('.'))
<main class="min-h-screen bg-[#f4f7f6] px-4 py-8 sm:px-8">
    <div class="mx-auto max-w-7xl">
        <section class="overflow-hidden rounded-3xl bg-[#0e5c3a] p-8 text-white shadow-sm">
            <p class="text-xs font-black uppercase tracking-[.2em] text-amber-300">NDMU-RMAS {{ strtoupper($workspace) }} PORTAL</p>
            <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
                <div><h1 class="text-3xl font-black">Reports &amp; Analytics</h1><p class="mt-1 text-sm text-emerald-100">CEAC reporting with permission and ownership controls.</p></div>
                <a href="{{ route($workspace.'.dashboard') }}" class="rounded-xl border border-white/30 px-4 py-2 text-sm font-bold hover:bg-white/10">Back to Dashboard</a>
            </div>
        </section>
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900"><strong>Scope:</strong> {{ $scope->label() }}. Report screens are read-only and contain only records you are authorized to view.</div>

        <section class="mt-6">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.16em] text-emerald-700">Current overview</p>
                    <h2 class="mt-1 text-xl font-black text-slate-900">Research reporting snapshot</h2>
                </div>
                <p class="text-xs text-slate-500">Counts use the same authoritative scoped queries as the reports below.</p>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($summaryCards as $card)
                    <a href="{{ route($workspace.'.reports.show', $card['report']) }}" class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $card['label'] }}</p>
                                <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($card['value']) }}</p>
                            </div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-xl text-emerald-700 transition group-hover:bg-emerald-100">
                                <i class="ph {{ $card['icon'] }}"></i>
                            </span>
                        </div>
                        <p class="mt-4 text-xs font-bold text-emerald-700">Open report <span aria-hidden="true">&rarr;</span></p>
                    </a>
                @endforeach
            </div>
        </section>

        <div class="mt-8">
            <p class="text-xs font-black uppercase tracking-[.16em] text-emerald-700">Report catalog</p>
            <h2 class="mt-1 text-xl font-black text-slate-900">Detailed reports and exports</h2>
            <p class="mt-1 text-sm text-slate-500">Open a report to apply academic year, term, program, class, adviser, stage, status, or date filters where supported.</p>
        </div>
        <section class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach($reports as $identifier => $definition)
                <a href="{{ route($workspace.'.reports.show', $identifier) }}" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
                    <p class="text-xs font-black uppercase tracking-wider text-emerald-700">Report</p>
                    <h2 class="mt-2 text-lg font-black text-slate-900">{{ $definition['title'] }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $definition['description'] }}</p>
                </a>
            @endforeach
        </section>
    </div>
</main>
@endsection
