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

        <div class="px-6 pb-6"><form method="POST" action="{{ route('logout') }}" data-confirm-logout>@csrf<button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-[13px] font-semibold text-white/90 hover:bg-white/5"><i class="ph ph-sign-out text-lg"></i><span>Logout</span></button></form></div>
    </aside>

    <div class="min-h-screen pl-72">
        <header class="sticky top-0 z-10 flex h-20 items-center justify-between border-b border-gray-150 bg-white px-8">
            <a href="{{ route('student.dashboard', ['tab' => 'classes']) }}" class="inline-flex items-center gap-2 text-xs font-bold text-gray-600 hover:text-[#0e5c3a]"><i class="ph ph-arrow-left"></i><span>Back to My Classes</span></a>
            <div class="flex items-center gap-3">
                <x-workspace-switcher current="student" />
                <span class="text-xs font-bold text-gray-800">Student Portal</span>
            </div>
        </header>

        <main class="space-y-8 p-8">
            <!-- Hero Banner -->
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0e5c3a] via-[#0a4a2e] to-[#083a24] p-8 text-white shadow-xl border border-emerald-800/40">
                <div class="absolute -right-12 -top-12 h-64 w-64 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
                <div class="flex flex-col justify-between gap-6 md:flex-row md:items-center relative z-10">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-3xl font-black tracking-tight text-white">{{ $researchClass->name }}</h1>
                            <span class="rounded-full bg-[#eebc3f] px-3.5 py-1 text-[10px] font-black uppercase tracking-wider text-[#0e5c3a] shadow-xs">Enrolled</span>
                        </div>
                        @if ($researchClass->description)
                            <p class="mt-2 text-sm text-emerald-100/90 max-w-2xl leading-relaxed">{{ $researchClass->description }}</p>
                        @endif
                    </div>
                    <div class="rounded-2xl border border-white/20 bg-white/10 backdrop-blur-md px-5 py-3.5 text-right shadow-inner">
                        <p class="text-[9px] font-extrabold uppercase tracking-widest text-[#eebc3f]">Date Joined</p>
                        <p class="mt-1 text-sm font-extrabold text-white">{{ $enrollment->joined_at?->format('M j, Y') ?? 'Not available' }}</p>
                    </div>
                </div>
            </section>

            <!-- Cards Grid: Class Facilitator & Assigned Adviser -->
            <section class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="rounded-2xl border border-gray-150 bg-white p-6 shadow-sm border-t-4 border-t-[#0e5c3a]">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Class Facilitator</p>
                        <span class="w-8 h-8 rounded-xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center font-bold"><i class="ph ph-user-gear text-base"></i></span>
                    </div>
                    <div class="mt-4 flex items-center gap-3.5">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#0e5c3a] text-sm font-bold text-white shadow-xs border border-emerald-700">
                            {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($researchClass->facilitator?->name ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-850">{{ $researchClass->facilitator?->name ?? 'Not assigned' }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $researchClass->facilitator?->email }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-150 bg-white p-6 shadow-sm border-t-4 border-t-[#0e5c3a]">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Assigned Research Adviser</p>
                        <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold"><i class="ph ph-user-check text-base"></i></span>
                    </div>
                    <div class="mt-4 flex items-center gap-3.5">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-500 text-sm font-bold text-white shadow-xs border border-amber-600">
                            {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($group?->adviser?->name ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-850">{{ $group?->adviser?->name ?? 'Not assigned yet' }}</p>
                            @if ($group?->adviser)
                                <p class="text-xs text-gray-500 mt-0.5">{{ $group->adviser->email }} · {{ $group->adviser->department ?? 'Faculty' }}</p>
                            @else
                                <p class="text-xs text-amber-700 font-medium mt-0.5">Awaiting facilitator assignment</p>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <!-- Capstone Group Section -->
            <section class="rounded-2xl border border-gray-150 bg-white shadow-sm overflow-hidden">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between p-6 border-b border-gray-150 bg-gradient-to-r from-gray-50 to-white gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-2.5 h-8 rounded-full bg-[#0e5c3a]"></div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Capstone Research Group</p>
                            <h2 class="text-xl font-extrabold text-gray-850 mt-0.5">{{ $group?->name ?? 'Not assigned to a group yet' }}</h2>
                        </div>
                    </div>
                    @if ($group)
                        <div class="flex items-center gap-2">
                            <span class="px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-[#0e5c3a] text-xs font-bold flex items-center gap-2">
                                <i class="ph ph-users text-sm"></i>
                                <span>{{ $group->members->count() }} / 4 Members</span>
                            </span>
                        </div>
                    @endif
                </div>

                <div class="p-6">
                    @if ($group)
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            @forelse ($group->members as $member)
                                @php($isLeader = ($group->leader_student_id === $member->student_id))
                                <div class="flex items-center justify-between gap-3 rounded-2xl border {{ $isLeader ? 'border-amber-300 bg-gradient-to-r from-amber-50/90 via-amber-50/40 to-white shadow-xs' : 'border-gray-150 bg-white' }} p-4 transition-all duration-200 hover:shadow-md">
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <div class="relative shrink-0">
                                            <div class="flex h-11 w-11 items-center justify-center rounded-full {{ $isLeader ? 'bg-amber-400 text-amber-950 font-black ring-2 ring-amber-300' : 'bg-emerald-100 text-[#0e5c3a] font-bold' }} text-sm border {{ $isLeader ? 'border-amber-500' : 'border-emerald-200' }}">
                                                {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($member->student?->name ?? '?', 0, 1)) }}
                                            </div>
                                            @if ($isLeader)
                                                <span class="absolute -top-1.5 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-amber-500 text-white text-[10px] shadow-sm">
                                                    <i class="ph ph-crown-fill text-[10px]"></i>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p class="truncate text-sm font-bold text-gray-850">{{ $member->student?->name ?? 'Deleted account' }}</p>
                                                @if ($isLeader)
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 border border-amber-300 text-[9px] font-black uppercase tracking-wider shadow-2xs">
                                                        <i class="ph ph-crown-fill text-amber-600 text-[10px]"></i>
                                                        <span>Student Leader</span>
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="truncate text-xs text-gray-500 mt-0.5">{{ $member->student?->student_id ?: $member->student?->email }}</p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">No members have been assigned.</p>
                            @endforelse
                        </div>
                    @else
                        <div class="p-8 text-center bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                            <i class="ph ph-users-three text-4xl text-gray-300"></i>
                            <p class="mt-3 text-sm text-gray-500">The class facilitator has not assigned you to a research group yet.</p>
                        </div>
                    @endif
                </div>
            </section>

            <!-- Group Submissions & Adviser Review Feedback Section -->
            @if ($group && isset($groupDocuments) && $groupDocuments->isNotEmpty())
                <section class="rounded-2xl border border-gray-150 bg-white shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-150 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-2.5 h-8 rounded-full bg-[#0e5c3a]"></div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Group Submissions & Adviser Feedback</p>
                                <h2 class="text-xl font-extrabold text-gray-850 mt-0.5">Adviser Findings & Review Status</h2>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">
                        @foreach ($groupDocuments as $doc)
                            <div class="rounded-2xl border border-gray-150 bg-gray-50/50 p-5 space-y-4">
                                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200/80 pb-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-bold text-gray-900 text-base">{{ $doc->original_filename }}</h3>
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-black uppercase text-[#0e5c3a]">
                                                V{{ $doc->version_number }} · {{ $doc->is_current ? 'CURRENT' : 'VOID' }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Stage: {{ $doc->stageLabel() }} · Submitted: {{ $doc->submitted_at?->format('M j, Y g:i A') }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider
                                            @if($doc->status->value === 'accepted') bg-emerald-100 text-emerald-800
                                            @elseif($doc->status->value === 'revision_requested') bg-amber-100 text-amber-800
                                            @elseif($doc->status->value === 'rejected') bg-rose-100 text-rose-800
                                            @elseif($doc->status->value === 'under_review') bg-blue-100 text-blue-800
                                            @else bg-gray-200 text-gray-700 @endif">
                                            {{ \Illuminate\Support\Str::headline($doc->status->value) }}
                                        </span>
                                        <a href="{{ route('documents.view', $doc) }}" class="rounded-xl bg-[#0e5c3a] px-3.5 py-2 text-xs font-bold text-white hover:bg-[#0a4a2e]">
                                            View File
                                        </a>
                                    </div>
                                </div>

                                <!-- Review Comments / Findings -->
                                @if ($doc->comments->isNotEmpty())
                                    <div class="space-y-3">
                                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-600">Adviser Findings & Comments</h4>
                                        <div class="space-y-2">
                                            @foreach ($doc->comments as $c)
                                                <div class="rounded-xl border border-gray-200 bg-white p-3.5 text-xs shadow-2xs flex flex-col gap-1.5">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center gap-2">
                                                            <span class="rounded-md px-2 py-0.5 text-[9px] font-black uppercase
                                                                @if($c->severity === 'critical') bg-rose-100 text-rose-700 border border-rose-200
                                                                @elseif($c->severity === 'revision') bg-amber-100 text-amber-800 border border-amber-200
                                                                @else bg-blue-50 text-blue-700 border border-blue-200 @endif">
                                                                {{ strtoupper($c->severity) }}
                                                            </span>
                                                            @if($c->page_number)
                                                                <span class="font-bold text-gray-500">Page {{ $c->page_number }}</span>
                                                            @endif
                                                            <span class="font-bold text-gray-700">{{ $c->author?->name ?? 'Adviser' }}</span>
                                                        </div>
                                                        @if($c->resolved_at)
                                                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200 flex items-center gap-1">
                                                                <i class="ph ph-check-circle-fill"></i> Resolved
                                                            </span>
                                                        @else
                                                            <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
                                                                Unresolved
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <p class="text-gray-800 font-medium leading-relaxed">{{ $c->comment }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Decision History -->
                                @if ($doc->reviews->isNotEmpty())
                                    <div class="space-y-2 pt-2">
                                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-600">Decision History</h4>
                                        <div class="space-y-2">
                                            @foreach ($doc->reviews as $r)
                                                <div class="rounded-xl border border-gray-200 bg-white p-3 text-xs flex flex-col gap-1 {{ $r->is_superseded ? 'opacity-60 bg-gray-50' : '' }}">
                                                    <div class="flex items-center justify-between">
                                                        <span class="font-bold text-gray-800">
                                                            {{ \Illuminate\Support\Str::headline($r->decision) }}
                                                            @if($r->is_superseded)
                                                                <span class="text-[9px] font-black uppercase bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded ml-1">Superseded</span>
                                                            @endif
                                                        </span>
                                                        <span class="text-[10px] text-gray-400">{{ $r->reviewed_at?->format('M j, Y g:i A') }}</span>
                                                    </div>
                                                    @if($r->review_notes)
                                                        <p class="text-gray-600 italic">"{{ $r->review_notes }}"</p>
                                                    @endif
                                                    @if($r->correction_reason)
                                                        <p class="text-amber-800 font-semibold text-[11px] mt-0.5">Correction Reason: {{ $r->correction_reason }}</p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </main>
    </div>
</div>
@endsection
