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
    <aside class="fixed inset-y-0 left-0 w-72 bg-[#0e5c3a] text-white flex flex-col z-20 border-r border-white/5">
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
                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($adviser->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="font-semibold text-sm truncate">{{ $adviser->name }}</p>
                <p class="text-[10px] text-white/60 mt-0.5">Research Adviser</p>
            </div>
        </div>

        <nav class="flex-1 px-6 py-5 space-y-2">
            <a href="{{ route('adviser.dashboard') }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-white/90 hover:bg-white/5 text-[13px] font-semibold">
                <i class="ph ph-squares-four text-lg"></i>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('adviser.dashboard', ['tab' => 'classes']) }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#eebc3f] text-[#0e5c3a] text-[13px] font-bold">
                <i class="ph ph-chalkboard-teacher text-lg"></i>
                <span>My Classes</span>
            </a>
        </nav>

        <div class="px-6 pb-6">
            <form method="POST" action="{{ route('logout') }}" data-confirm-logout>
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-white/90 hover:bg-white/5 font-semibold text-[13px]">
                    <i class="ph ph-sign-out text-lg"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="flex-1 min-h-screen pl-72">
        <header class="h-20 bg-white border-b border-gray-150 px-8 flex items-center justify-between sticky top-0 z-10">
            <a href="{{ route('adviser.dashboard', ['tab' => 'classes']) }}" wire:navigate class="inline-flex items-center gap-2 text-xs font-bold text-gray-600 hover:text-[#0e5c3a]">
                <i class="ph ph-arrow-left"></i>
                <span>Back to My Classes</span>
            </a>
            <span class="text-xs font-bold text-gray-800">{{ $adviser->name }}</span>
        </header>

        <main class="p-8 space-y-8">
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0e5c3a] via-[#0a4a2e] to-[#083a24] p-8 text-white shadow-xl border border-emerald-800/40">
                <div class="absolute -right-12 -top-12 h-64 w-64 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
                <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center relative z-10">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-3xl font-black tracking-tight text-white">{{ $researchClass->name }}</h1>
                            <span class="rounded-full px-3.5 py-1 text-[10px] font-black uppercase tracking-wider shadow-xs {{ $researchClass->is_active ? 'bg-[#eebc3f] text-[#0e5c3a]' : 'bg-gray-700 text-gray-200' }}">
                                {{ $researchClass->is_active ? 'Active Class' : 'Inactive' }}
                            </span>
                        </div>
                        @if ($researchClass->description)
                            <p class="mt-2 text-sm text-emerald-100/90 max-w-2xl leading-relaxed">{{ $researchClass->description }}</p>
                        @endif
                    </div>

                    <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 p-5 min-w-64 shadow-inner">
                        <p class="text-[9px] font-extrabold uppercase tracking-widest text-[#eebc3f]">Student Join Code</p>
                        <div class="flex items-center justify-between gap-4 mt-1">
                            <code class="text-xl font-black tracking-widest text-white font-mono">{{ $joinCode ?? 'Unavailable' }}</code>
                            <button
                                type="button"
                                @if ($joinCode)
                                    @click="navigator.clipboard?.writeText(@js($joinCode)).then(() => { copied = true; setTimeout(() => copied = false, 1800) })"
                                @endif
                                class="w-9 h-9 rounded-xl bg-white/15 hover:bg-white/25 text-white flex items-center justify-center transition-all cursor-pointer disabled:cursor-not-allowed disabled:opacity-50"
                                aria-label="Copy class join code"
                                @disabled(! $joinCode)
                            >
                                <i class="ph" :class="copied ? 'ph-check text-[#eebc3f]' : 'ph-copy'"></i>
                            </button>
                        </div>
                        <p x-show="copied" x-cloak class="text-[10px] text-[#eebc3f] font-bold mt-1">Code copied to clipboard!</p>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl p-6 border border-gray-150 shadow-sm border-t-4 border-t-[#0e5c3a]">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Class Adviser</p>
                        <span class="w-8 h-8 rounded-xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center font-bold"><i class="ph ph-user-check text-base"></i></span>
                    </div>
                    <div class="flex items-center gap-3 mt-3">
                        <div class="w-10 h-10 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-sm border border-emerald-700">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($adviser->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-850">{{ $adviser->name }}</p>
                            <p class="text-[10px] text-gray-500">{{ $adviser->email }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-gray-150 shadow-sm border-t-4 border-t-[#0e5c3a]">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Enrollment Capacity</p>
                        <span class="w-8 h-8 rounded-xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center font-bold"><i class="ph ph-users text-base"></i></span>
                    </div>
                    <p class="text-2xl font-black text-gray-850 mt-3">{{ $activeStudents }} <span class="text-sm font-medium text-gray-400">/ {{ $researchClass->max_students }} Enrolled</span></p>
                    <div class="h-2.5 rounded-full bg-gray-100 mt-4 overflow-hidden">
                        <div
                            class="h-full bg-gradient-to-r from-[#0e5c3a] to-[#009b67] rounded-full"
                            x-data="{ capacityPercentage: @js($capacityPercentage) }"
                            x-bind:style="{ width: capacityPercentage + '%' }"
                        ></div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-gray-150 shadow-sm border-t-4 border-t-[#0e5c3a]">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Class Creation</p>
                        <span class="w-8 h-8 rounded-xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center font-bold"><i class="ph ph-calendar text-base"></i></span>
                    </div>
                    <p class="text-sm font-bold text-gray-850 mt-3">{{ $researchClass->created_at?->format('M j, Y') ?? 'Not available' }}</p>
                    <p class="text-[10px] text-gray-500 mt-1">{{ $researchClass->created_at?->diffForHumans() ?? 'Timestamp unavailable' }}</p>
                </div>
            </div>

            <section class="bg-white rounded-2xl border border-gray-150 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-150 flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-gradient-to-r from-gray-50 to-white">
                    <div class="flex items-center gap-3">
                        <div class="w-2.5 h-8 rounded-full bg-[#0e5c3a]"></div>
                        <div>
                            <h2 class="font-extrabold text-xl text-[#0e5c3a]">Student Roster</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Students currently enrolled in this research class.</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('adviser.classes.show', $researchClass) }}" class="relative w-full md:w-80">
                        <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input
                            type="search"
                            name="q"
                            value="{{ $search }}"
                            maxlength="100"
                            placeholder="Search name or email..."
                            class="w-full rounded-xl border border-gray-200 pl-10 pr-4 py-2.5 text-xs focus:border-[#0e5c3a] focus:outline-none shadow-2xs"
                        >
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gradient-to-r from-[#0e5c3a] to-[#0a4a2e] text-white text-[10px] font-black uppercase tracking-wider shadow-xs">
                            <tr>
                                <th class="px-6 py-4 font-bold">Student Details</th>
                                <th class="px-6 py-4 font-bold">Student ID</th>
                                <th class="px-6 py-4 font-bold">Date Joined</th>
                                <th class="px-6 py-4 font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($enrollments as $enrollment)
                                @php($student = $enrollment->student)
                                <tr class="hover:bg-emerald-50/40 transition-colors duration-150">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-emerald-100 text-[#0e5c3a] flex items-center justify-center font-bold text-xs shrink-0 border border-emerald-200">
                                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($student?->name ?? 'S', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-850">{{ $student?->name ?? 'Deleted student account' }}</p>
                                                <p class="text-[10px] text-gray-500 mt-0.5">{{ $student?->email ?? 'Email unavailable' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-semibold text-gray-700">
                                        {{ $student?->student_id ?: 'Not available' }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500">
                                        {{ $enrollment->joined_at?->format('M j, Y g:i A') ?? 'Not available' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 rounded-full text-[9px] font-bold uppercase {{ $enrollment->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">
                                            {{ \Illuminate\Support\Str::headline($enrollment->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">
                                        {{ $search !== '' ? 'No students matched your search.' : 'No students have joined this class yet.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($enrollments->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $enrollments->links() }}
                    </div>
                @endif
            </section>
        </main>
    </div>
</div>
@endsection
