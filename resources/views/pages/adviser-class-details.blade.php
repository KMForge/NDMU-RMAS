@extends('layouts.blank')

@php
    $joinCode = rescue(
        fn () => filled($researchClass->join_code_encrypted) ? $researchClass->revealJoinCode() : null,
        null,
        report: false,
    );
    $capacityPercentage = $researchClass->max_students > 0
        ? min(100, (int) round(($activeStudents / $researchClass->max_students) * 100))
        : 0;
@endphp

@section('content')
<style>[x-cloak] { display: none !important; }</style>

<div class="min-h-screen flex font-sans bg-[#f4f7f6]" x-data="{ copied: false }">
    <!-- Left Sidebar -->
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

            <!-- Profile Card -->
            <div class="px-5 py-3">
                <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/[0.06] border border-white/10 shadow-inner backdrop-blur-xs">
                    <div class="relative w-10 h-10 rounded-xl bg-gradient-to-tr from-[#eebc3f] to-[#ffd76f] text-[#09472d] font-black flex items-center justify-center text-lg flex-shrink-0 shadow-md">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($adviser->name, 0, 1)) }}
                    </div>
                    <div class="flex flex-col leading-tight overflow-hidden min-w-0">
                        <span class="font-bold text-xs text-white truncate">{{ $adviser->name }}</span>
                        <span class="text-[10px] text-white/70 font-medium mt-0.5 truncate">Research Adviser</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-grow px-5 py-3 space-y-2">
            <a
                href="{{ route('adviser.dashboard') }}"
                wire:navigate
                class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-[13px] font-semibold text-white/85 hover:text-white hover:bg-white/15 transition-all"
            >
                <i class="ph ph-squares-four text-lg"></i>
                <span>Dashboard</span>
            </a>
            <a
                href="{{ route('adviser.dashboard', ['tab' => 'classes']) }}"
                wire:navigate
                class="flex items-center justify-between rounded-xl bg-[#eebc3f] px-3.5 py-2.5 text-[13px] font-bold text-[#09472d] shadow-md shadow-amber-950/20"
            >
                <div class="flex items-center gap-3">
                    <i class="ph ph-chalkboard-teacher text-lg"></i>
                    <span>My Classes</span>
                </div>
                <span class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
            </a>
        </div>

        <div class="px-5 pb-6">
            <form method="POST" action="{{ route('logout') }}" data-confirm-logout>
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-[13px] font-semibold text-white/85 hover:text-white hover:bg-white/15 transition-all cursor-pointer">
                    <i class="ph ph-sign-out text-lg text-[#eebc3f]"></i>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 min-h-screen pl-72">
        <header class="sticky top-0 z-40 flex h-20 items-center justify-between border-b border-slate-200/80 bg-white/95 backdrop-blur-md px-8">
            <a href="{{ route('adviser.dashboard', ['tab' => 'classes']) }}" wire:navigate class="inline-flex items-center gap-2 text-xs font-black text-slate-700 hover:text-[#0e5c3a] transition-colors">
                <i class="ph ph-arrow-left text-sm text-[#0e5c3a]"></i>
                <span>Back to My Classes</span>
            </a>
            <div class="flex items-center gap-3">
                <x-workspace-switcher current="adviser" />
                <span class="text-xs font-bold text-slate-800">{{ $adviser->name }}</span>
            </div>
        </header>

        <main class="space-y-8 p-6 sm:p-8">
            <!-- Hero Banner -->
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#073823] via-[#0e5c3a] to-[#042416] p-7 sm:p-8 text-white shadow-xl border border-emerald-800/40">
                <div class="absolute -right-12 -top-12 h-64 w-64 rounded-full bg-[#eebc3f]/10 blur-2xl pointer-events-none"></div>
                <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center relative z-10">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl sm:text-3xl font-black font-heading tracking-tight text-white">{{ $researchClass->name }}</h1>
                            <span class="rounded-full px-3.5 py-1 text-[10px] font-black uppercase tracking-wider shadow-xs {{ $researchClass->is_active ? 'bg-[#eebc3f] text-[#073823]' : 'bg-slate-700 text-slate-200' }}">
                                {{ $researchClass->is_active ? 'Active Class' : 'Inactive' }}
                            </span>
                        </div>
                        @if ($researchClass->description)
                            <p class="text-xs sm:text-sm text-emerald-100/85 max-w-2xl leading-relaxed">{{ $researchClass->description }}</p>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-white/20 bg-white/10 backdrop-blur-md px-5 py-3.5 text-white shadow-inner shrink-0 min-w-64">
                        <p class="text-[9px] font-extrabold uppercase tracking-widest text-[#eebc3f]">Student Join Code</p>
                        <div class="flex items-center justify-between gap-4 mt-1">
                            <code class="text-xl font-black tracking-widest text-white font-mono">{{ $joinCode ?? 'Unavailable' }}</code>
                            <button
                                type="button"
                                @if ($joinCode)
                                    @click="navigator.clipboard?.writeText(@js($joinCode)).then(() => { copied = true; setTimeout(() => copied = false, 1800) })"
                                @endif
                                class="h-9 w-9 rounded-xl bg-white/15 text-white hover:bg-white/25 transition-all flex items-center justify-center cursor-pointer disabled:cursor-not-allowed disabled:opacity-50"
                                aria-label="Copy class join code"
                                @disabled(! $joinCode)
                            >
                                <i class="ph" :class="copied ? 'ph-check text-[#eebc3f] font-bold' : 'ph-copy'"></i>
                            </button>
                        </div>
                        <p x-show="copied" x-cloak class="text-[10px] text-[#eebc3f] font-bold mt-1">Join code copied to clipboard!</p>
                    </div>
                </div>
            </section>

            <!-- 3 KPI Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#0e5c3a]"></div>
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Class Adviser</p>
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center font-bold text-base"><i class="ph ph-user-check"></i></span>
                    </div>
                    <div class="flex items-center gap-3.5 mt-3.5">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#073823] to-[#0e5c3a] text-sm font-black text-white shadow-xs border border-emerald-700">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($adviser->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">{{ $adviser->name }}</p>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">{{ $adviser->email }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#eebc3f]"></div>
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Enrollment Capacity</p>
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center font-bold text-base"><i class="ph ph-users"></i></span>
                    </div>
                    <p class="text-2xl font-black text-slate-900 mt-3">{{ $activeStudents }} <span class="text-xs font-bold text-slate-400">/ {{ $researchClass->max_students }} Enrolled</span></p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full bg-gradient-to-r from-[#073823] via-[#0e5c3a] to-[#eebc3f] transition-all duration-500 rounded-full"
                            x-data="{ capacityPercentage: @js($capacityPercentage) }"
                            x-bind:style="{ width: capacityPercentage + '%' }"
                        ></div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#0e5c3a] to-emerald-400"></div>
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Class Creation</p>
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center font-bold text-base"><i class="ph ph-calendar"></i></span>
                    </div>
                    <p class="text-sm font-black text-slate-900 mt-3">{{ $researchClass->created_at?->format('M j, Y') ?? 'Not available' }}</p>
                    <p class="text-xs text-slate-500 font-medium mt-1">{{ $researchClass->created_at?->diffForHumans() ?? 'Timestamp unavailable' }}</p>
                </div>
            </div>

            <!-- Student Roster Table -->
            <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-100 p-6 md:flex-row md:items-center md:justify-between bg-slate-50/60">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-7 rounded-full bg-[#0e5c3a]"></span>
                        <div>
                            <h2 class="text-lg sm:text-xl font-black font-heading text-slate-900">Student Roster</h2>
                            <p class="mt-0.5 text-xs text-slate-500 font-medium">Students currently enrolled in this research class.</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('adviser.classes.show', $researchClass) }}" class="relative w-full md:w-80">
                        <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input
                            type="search"
                            name="q"
                            value="{{ $search }}"
                            maxlength="100"
                            placeholder="Search name or email..."
                            class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-xs font-medium text-slate-900 placeholder-slate-400 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 focus:outline-none transition-all"
                        >
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-[#073823] text-white text-[10px] font-black uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5">Student Details</th>
                                <th class="px-6 py-3.5">Student ID</th>
                                <th class="px-6 py-3.5">Date Joined</th>
                                <th class="px-6 py-3.5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-xs">
                            @forelse ($enrollments as $enrollment)
                                @php($student = $enrollment->student)
                                <tr class="hover:bg-emerald-50/40 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-emerald-100 text-[#0e5c3a] flex items-center justify-center font-black text-xs shrink-0 border border-emerald-200">
                                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($student?->name ?? 'S', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-slate-900">{{ $student?->name ?? 'Deleted student account' }}</p>
                                                <p class="text-[10px] text-slate-500 font-medium mt-0.5">{{ $student?->email ?? 'Email unavailable' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-mono font-bold text-slate-700">
                                        {{ $student?->student_id ?: 'Not specified' }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 font-medium">
                                        {{ $enrollment->joined_at?->format('M j, Y g:i A') ?? 'Not available' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-wider {{ $enrollment->status === 'active' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                            {{ \Illuminate\Support\Str::headline($enrollment->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-xs font-medium text-slate-500">
                                        {{ $search !== '' ? 'No students matched your search.' : 'No students have joined this class yet.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($enrollments->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                        {{ $enrollments->links() }}
                    </div>
                @endif
            </section>
        </main>
    </div>
</div>
@endsection

