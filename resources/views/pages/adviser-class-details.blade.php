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
            <form method="POST" action="{{ route('logout') }}">
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
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-850">{{ $researchClass->name }}</h1>
                        <span class="px-3 py-1 rounded-full text-[9px] font-bold uppercase {{ $researchClass->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">
                            {{ $researchClass->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    @if ($researchClass->description)
                        <p class="text-sm text-gray-500 mt-2">{{ $researchClass->description }}</p>
                    @endif
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4 min-w-64">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Student Join Code</p>
                    <div class="flex items-center justify-between gap-4 mt-2">
                        <code class="text-lg font-extrabold tracking-widest text-[#0e5c3a]">{{ $joinCode ?? 'Unavailable' }}</code>
                        <button
                            type="button"
                            @if ($joinCode)
                                @click="navigator.clipboard?.writeText(@js($joinCode)).then(() => { copied = true; setTimeout(() => copied = false, 1800) })"
                            @endif
                            class="w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 disabled:cursor-not-allowed disabled:opacity-50"
                            aria-label="Copy class join code"
                            @disabled(! $joinCode)
                        >
                            <i class="ph" :class="copied ? 'ph-check text-emerald-600' : 'ph-copy'"></i>
                        </button>
                    </div>
                    <p x-show="copied" x-cloak class="text-[10px] text-emerald-600 font-bold mt-2">Code copied.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Class Adviser</p>
                    <div class="flex items-center gap-3 mt-4">
                        <div class="w-11 h-11 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($adviser->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-850">{{ $adviser->name }}</p>
                            <p class="text-[10px] text-gray-500">{{ $adviser->email }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Enrollment</p>
                    <p class="text-2xl font-bold text-gray-850 mt-3">{{ $activeStudents }} / {{ $researchClass->max_students }}</p>
                    <div class="h-2 rounded-full bg-gray-100 mt-4 overflow-hidden">
                        <div
                            class="h-full bg-[#0e5c3a] rounded-full"
                            x-data="{ capacityPercentage: @js($capacityPercentage) }"
                            x-bind:style="{ width: capacityPercentage + '%' }"
                        ></div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Created</p>
                    <p class="text-sm font-bold text-gray-850 mt-3">{{ $researchClass->created_at?->format('M j, Y') ?? 'Not available' }}</p>
                    <p class="text-[10px] text-gray-500 mt-1">{{ $researchClass->created_at?->diffForHumans() ?? 'Timestamp unavailable' }}</p>
                </div>
            </div>

            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="font-bold text-lg text-gray-850">Student Roster</h2>
                        <p class="text-xs text-gray-500 mt-1">Students currently associated with this research class.</p>
                    </div>

                    <form method="GET" action="{{ route('adviser.classes.show', $researchClass) }}" class="relative w-full md:w-80">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input
                            type="search"
                            name="q"
                            value="{{ $search }}"
                            maxlength="100"
                            placeholder="Search name or email"
                            class="w-full rounded-xl border border-gray-200 pl-9 pr-4 py-2.5 text-xs focus:border-[#0e5c3a] focus:outline-none"
                        >
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[9px] uppercase tracking-wider text-gray-400">
                            <tr>
                                <th class="px-6 py-3 font-bold">Student</th>
                                <th class="px-6 py-3 font-bold">Student Number</th>
                                <th class="px-6 py-3 font-bold">Joined</th>
                                <th class="px-6 py-3 font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($enrollments as $enrollment)
                                @php($student = $enrollment->student)
                                <tr>
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-bold text-gray-800">{{ $student?->name ?? 'Deleted student account' }}</p>
                                        <p class="text-[10px] text-gray-500 mt-0.5">{{ $student?->email ?? 'Email unavailable' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-600">
                                        {{ $student?->student_id ?: 'Not available' }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-600">
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
