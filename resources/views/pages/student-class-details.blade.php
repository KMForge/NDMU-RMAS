@extends('layouts.blank')

@section('content')
<div class="min-h-screen bg-[#f4f7f6] font-sans">
    <aside class="fixed inset-y-0 left-0 z-20 flex w-72 flex-col border-r border-white/5 bg-[#0e5c3a] text-white">
        <div class="flex items-center gap-3 border-b border-white/10 p-6">
            <div class="rounded-xl border border-white/20 bg-white/10 p-1">
                <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="NDMU Logo" width="96" height="96" class="h-10 w-auto">
            </div>
            <div class="flex flex-col leading-none">
                <span class="font-heading text-xl font-extrabold tracking-tight">NDMU</span>
                <span class="mt-1 text-[9px] font-bold uppercase tracking-wider text-[#eebc3f]">Research Management</span>
            </div>
        </div>

        <div class="flex items-center gap-3 border-b border-white/10 px-6 py-5">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#eebc3f] text-lg font-bold text-[#0e5c3a]">
                {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($student->name, 0, 1)) }}
            </div>
            <div class="min-w-0"><p class="truncate text-sm font-semibold">{{ $student->name }}</p><p class="mt-0.5 text-[10px] text-white/60">Student Researcher</p></div>
        </div>

        <nav class="flex-1 space-y-2 px-6 py-5">
            <a href="{{ route('student.dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-white/90 hover:bg-white/5"><i class="ph ph-squares-four text-lg"></i><span>Dashboard</span></a>
            <a href="{{ route('student.dashboard', ['tab' => 'classes']) }}" class="flex items-center gap-3 rounded-xl bg-[#eebc3f] px-3 py-2.5 text-[13px] font-bold text-[#0e5c3a]"><i class="ph ph-users text-lg"></i><span>My Classes</span></a>
        </nav>

        <div class="px-6 pb-6"><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-[13px] font-semibold text-white/90 hover:bg-white/5"><i class="ph ph-sign-out text-lg"></i><span>Logout</span></button></form></div>
    </aside>

    <div class="min-h-screen pl-72">
        <header class="sticky top-0 z-10 flex h-20 items-center justify-between border-b border-gray-150 bg-white px-8">
            <a href="{{ route('student.dashboard', ['tab' => 'classes']) }}" class="inline-flex items-center gap-2 text-xs font-bold text-gray-600 hover:text-[#0e5c3a]"><i class="ph ph-arrow-left"></i><span>Back to My Classes</span></a>
            <span class="text-xs font-bold text-gray-800">Student Portal</span>
        </header>

        <main class="space-y-8 p-8">
            <section class="rounded-2xl border border-gray-100 bg-white p-7 shadow-sm">
                <div class="flex flex-col justify-between gap-5 md:flex-row md:items-start">
                    <div><div class="flex items-center gap-3"><h1 class="text-2xl font-bold text-gray-900">{{ $researchClass->name }}</h1><span class="rounded-full bg-emerald-100 px-3 py-1 text-[9px] font-bold uppercase text-emerald-700">Enrolled</span></div>@if ($researchClass->description)<p class="mt-2 text-sm text-gray-500">{{ $researchClass->description }}</p>@endif</div>
                    <div class="text-right"><p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Joined</p><p class="mt-1 text-sm font-bold text-gray-700">{{ $enrollment->joined_at?->format('M j, Y') ?? 'Not available' }}</p></div>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Class Facilitator</p>
                    <div class="mt-4 flex items-center gap-3"><div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 font-bold text-emerald-700">{{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($researchClass->facilitator?->name ?? '?', 0, 1)) }}</div><div><p class="text-sm font-bold text-gray-800">{{ $researchClass->facilitator?->name ?? 'Not assigned' }}</p><p class="text-[10px] text-gray-500">{{ $researchClass->facilitator?->email }}</p></div></div>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Assigned Research Adviser</p>
                    <div class="mt-4 flex items-center gap-3"><div class="flex h-11 w-11 items-center justify-center rounded-full bg-amber-100 font-bold text-amber-700">{{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($group?->adviser?->name ?? '?', 0, 1)) }}</div><div><p class="text-sm font-bold text-gray-800">{{ $group?->adviser?->name ?? 'Not assigned yet' }}</p>@if ($group?->adviser)<p class="text-[10px] text-gray-500">{{ $group->adviser->email }}</p>@endif</div></div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between"><div><p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Capstone Group</p><h2 class="mt-1 text-lg font-bold text-gray-900">{{ $group?->name ?? 'Not assigned to a group yet' }}</h2></div><i class="ph ph-users-three text-3xl text-[#0e5c3a]"></i></div>

                @if ($group)
                    <div class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @forelse ($group->members as $member)
                            <div class="flex items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 px-4 py-3"><div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">{{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($member->student?->name ?? '?', 0, 1)) }}</div><div class="min-w-0"><p class="truncate text-xs font-bold text-gray-800">{{ $member->student?->name ?? 'Deleted account' }}</p><p class="truncate text-[10px] text-gray-500">{{ $member->student?->student_id ?: $member->student?->email }}</p></div></div>
                        @empty
                            <p class="text-sm text-gray-500">No members have been assigned.</p>
                        @endforelse
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-500">The facilitator has not assigned you to a Capstone group.</p>
                @endif
            </section>
        </main>
    </div>
</div>
@endsection
