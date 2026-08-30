@extends('layouts.blank')

@section('content')
<div class="min-h-screen bg-[#f4f7f6] font-sans flex">
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
                        {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($student->name, 0, 1)) }}
                    </div>
                    <div class="flex flex-col leading-tight overflow-hidden min-w-0">
                        <span class="font-bold text-xs text-white truncate">{{ $student->name }}</span>
                        <span class="text-[10px] text-white/70 font-medium mt-0.5 truncate">Student Researcher</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-grow px-5 py-3 space-y-2">
            <a
                href="{{ route('student.dashboard') }}"
                class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-[13px] font-semibold text-white/85 hover:text-white hover:bg-white/15 transition-all"
            >
                <i class="ph ph-squares-four text-lg"></i>
                <span>Dashboard</span>
            </a>
            <a
                href="{{ route('student.dashboard', ['tab' => 'classes']) }}"
                class="flex items-center justify-between rounded-xl bg-[#eebc3f] px-3.5 py-2.5 text-[13px] font-bold text-[#09472d] shadow-md shadow-amber-950/20"
            >
                <div class="flex items-center gap-3">
                    <i class="ph ph-users text-lg"></i>
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
    <div class="min-h-screen flex-1 pl-72">
        <header class="sticky top-0 z-40 flex h-20 items-center justify-between border-b border-slate-200/80 bg-white/95 backdrop-blur-md px-8">
            <a href="{{ route('student.dashboard', ['tab' => 'classes']) }}" class="inline-flex items-center gap-2 text-xs font-black text-slate-700 hover:text-[#0e5c3a] transition-colors">
                <i class="ph ph-arrow-left text-sm text-[#0e5c3a]"></i>
                <span>Back to My Classes</span>
            </a>
            <div class="flex items-center gap-3">
                <x-workspace-switcher current="student" />
                <span class="text-xs font-bold text-slate-800">Student Portal</span>
            </div>
        </header>

        <main class="space-y-8 p-6 sm:p-8">
            <!-- Hero Banner -->
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#073823] via-[#0e5c3a] to-[#042416] p-7 sm:p-8 text-white shadow-xl border border-emerald-800/40">
                <div class="absolute -right-12 -top-12 h-64 w-64 rounded-full bg-[#eebc3f]/10 blur-2xl pointer-events-none"></div>
                <div class="flex flex-col justify-between gap-6 md:flex-row md:items-center relative z-10">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl sm:text-3xl font-black font-heading tracking-tight text-white">{{ $researchClass->name }}</h1>
                            <span class="rounded-full bg-[#eebc3f] px-3.5 py-1 text-[10px] font-black uppercase tracking-wider text-[#073823] shadow-xs">
                                Enrolled Class
                            </span>
                        </div>
                        @if ($researchClass->description)
                            <p class="text-xs sm:text-sm text-emerald-100/85 max-w-2xl leading-relaxed">{{ $researchClass->description }}</p>
                        @endif
                    </div>
                    <div class="rounded-2xl border border-white/20 bg-white/10 backdrop-blur-md px-5 py-3.5 text-white shadow-inner shrink-0">
                        <p class="text-[9px] font-extrabold uppercase tracking-widest text-[#eebc3f]">Date Joined</p>
                        <p class="mt-1 text-sm font-black text-white font-mono">{{ $enrollment->joined_at?->format('M j, Y') ?? 'Not available' }}</p>
                    </div>
                </div>
            </section>

            <!-- Cards Grid: Class Facilitator & Assigned Adviser -->
            <section class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#0e5c3a]"></div>
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Class Facilitator</p>
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center font-bold text-base"><i class="ph ph-user-gear"></i></span>
                    </div>
                    <div class="mt-4 flex items-center gap-3.5">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#073823] to-[#0e5c3a] text-sm font-black text-white shadow-xs border border-emerald-700">
                            {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($researchClass->facilitator?->name ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">{{ $researchClass->facilitator?->name ?? 'Not assigned' }}</p>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">{{ $researchClass->facilitator?->email }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#eebc3f]"></div>
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Assigned Research Adviser</p>
                        <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold text-base"><i class="ph ph-user-check"></i></span>
                    </div>
                    <div class="mt-4 flex items-center gap-3.5">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#eebc3f] to-[#f4c542] text-sm font-black text-[#073823] shadow-xs border border-amber-400">
                            {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($group?->adviser?->name ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">{{ $group?->adviser?->name ?? 'Not assigned yet' }}</p>
                            @if ($group?->adviser)
                                <p class="text-xs text-slate-500 font-medium mt-0.5">{{ $group->adviser->email }} · {{ $group->adviser->department ?? 'Faculty' }}</p>
                            @else
                                <p class="text-xs text-amber-700 font-bold mt-0.5">Awaiting facilitator assignment</p>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <!-- Capstone Group Section -->
            <section class="rounded-3xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between p-6 sm:p-7 border-b border-slate-100 bg-slate-50/60 gap-4">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-7 rounded-full bg-[#0e5c3a]"></span>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Capstone Research Group</p>
                            <h2 class="text-lg sm:text-xl font-black font-heading text-slate-900 mt-0.5">{{ $group?->name ?? 'Not assigned to a group yet' }}</h2>
                        </div>
                    </div>
                    @if ($group)
                        <div class="flex items-center gap-2">
                            <span class="px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-[#0e5c3a] text-xs font-black flex items-center gap-2">
                                <i class="ph ph-users text-sm"></i>
                                <span>{{ $group->members->count() }} / 4 Members</span>
                            </span>
                        </div>
                    @endif
                </div>

                <div class="p-6 sm:p-7">
                    @if ($group)
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            @forelse ($group->members as $member)
                                @php($isLeader = ($group->leader_student_id === $member->student_id))
                                <div class="flex items-center justify-between gap-3 rounded-2xl border {{ $isLeader ? 'border-amber-300 bg-amber-50/50 shadow-2xs' : 'border-slate-200/80 bg-white' }} p-4 transition-all duration-200 hover:shadow-md">
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <div class="relative shrink-0">
                                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $isLeader ? 'bg-gradient-to-br from-[#eebc3f] to-[#f4c542] text-[#073823] font-black' : 'bg-emerald-100 text-[#0e5c3a] font-bold' }} text-sm border {{ $isLeader ? 'border-amber-400' : 'border-emerald-200' }}">
                                                {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($member->student?->name ?? '?', 0, 1)) }}
                                            </div>
                                            @if ($isLeader)
                                                <span class="absolute -top-1.5 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-[#eebc3f] text-[#073823] text-[10px] shadow-sm font-black">
                                                    <i class="ph ph-crown text-xs"></i>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p class="truncate text-xs font-black text-slate-900">{{ $member->student?->name ?? 'Deleted account' }}</p>
                                                @if ($isLeader)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-900 border border-amber-200 text-[9px] font-black uppercase tracking-wider">
                                                        <i class="ph ph-crown text-amber-700 text-[10px]"></i>
                                                        <span>Leader</span>
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="truncate text-[10px] text-slate-500 font-medium mt-0.5">{{ $member->student?->student_id ?: $member->student?->email }}</p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-slate-500 font-medium">No members have been assigned.</p>
                            @endforelse
                        </div>
                    @else
                        <div class="p-8 text-center bg-slate-50/50 rounded-2xl border-2 border-dashed border-slate-200">
                            <i class="ph ph-users-three text-4xl text-slate-300"></i>
                            <p class="mt-3 text-xs font-bold text-slate-700">The class facilitator has not assigned you to a research group yet.</p>
                        </div>
                    @endif
                </div>
            </section>

            <!-- Group Submissions & Adviser Review Feedback Section -->
            @if ($group && isset($groupDocuments) && $groupDocuments->isNotEmpty())
                <section class="rounded-3xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                    <div class="p-6 sm:p-7 border-b border-slate-100 bg-slate-50/60 flex items-center gap-2.5">
                        <span class="w-2.5 h-7 rounded-full bg-[#0e5c3a]"></span>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Group Submissions & Adviser Feedback</p>
                            <h2 class="text-lg sm:text-xl font-black font-heading text-slate-900 mt-0.5">Adviser Findings & Review Status</h2>
                        </div>
                    </div>

                    <div class="p-6 sm:p-7 space-y-6">
                        @foreach ($groupDocuments as $doc)
                            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/50 p-5 space-y-4">
                                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200/80 pb-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-black text-slate-900 text-sm sm:text-base">{{ $doc->original_filename }}</h3>
                                            <span class="rounded-full bg-emerald-100 border border-emerald-200 px-2.5 py-0.5 text-[10px] font-black uppercase text-[#0e5c3a]">
                                                V{{ $doc->version_number }} · {{ $doc->is_current ? 'CURRENT' : 'VOID' }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 font-medium mt-1">
                                            Stage: <strong class="text-slate-700">{{ $doc->stageLabel() }}</strong> · Submitted: {{ $doc->submitted_at?->format('M j, Y g:i A') }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-wider
                                            @if($doc->status->value === 'accepted') bg-emerald-100 text-emerald-800 border border-emerald-200
                                            @elseif($doc->status->value === 'revision_requested') bg-amber-100 text-amber-800 border border-amber-200
                                            @elseif($doc->status->value === 'rejected') bg-rose-100 text-rose-800 border border-rose-200
                                            @elseif($doc->status->value === 'under_review') bg-blue-100 text-blue-800 border border-blue-200
                                            @else bg-slate-100 text-slate-700 border border-slate-200 @endif">
                                            {{ \Illuminate\Support\Str::headline($doc->status->value) }}
                                        </span>
                                        <a href="{{ route('documents.view', $doc) }}" class="rounded-xl bg-[#0e5c3a] hover:bg-[#073823] px-4 py-2 text-xs font-bold text-white shadow-xs transition-colors cursor-pointer">
                                            View File
                                        </a>
                                    </div>
                                </div>

                                <!-- Review Comments / Findings -->
                                @if ($doc->comments->isNotEmpty())
                                    <div class="space-y-3">
                                        <h4 class="text-[11px] font-black uppercase tracking-wider text-slate-700">Adviser Findings & Comments</h4>
                                        <div class="space-y-2">
                                            @foreach ($doc->comments as $c)
                                                <div class="rounded-xl border border-slate-200 bg-white p-4 text-xs shadow-2xs flex flex-col gap-1.5">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center gap-2">
                                                            <span class="rounded-md px-2 py-0.5 text-[9px] font-black uppercase
                                                                @if($c->severity === 'critical') bg-rose-100 text-rose-700 border border-rose-200
                                                                @elseif($c->severity === 'revision') bg-amber-100 text-amber-800 border border-amber-200
                                                                @else bg-blue-50 text-blue-700 border border-blue-200 @endif">
                                                                {{ strtoupper($c->severity) }}
                                                            </span>
                                                            @if($c->page_number)
                                                                <span class="font-bold text-slate-500">Page {{ $c->page_number }}</span>
                                                            @endif
                                                            <span class="font-bold text-slate-800">{{ $c->author?->name ?? 'Adviser' }}</span>
                                                        </div>
                                                        @if($c->resolved_at)
                                                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200 flex items-center gap-1">
                                                                <i class="ph ph-check-circle"></i> Resolved
                                                            </span>
                                                        @else
                                                            <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
                                                                Unresolved
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <p class="text-slate-800 font-medium leading-relaxed">{{ $c->comment }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Decision History -->
                                @if ($doc->reviews->isNotEmpty())
                                    <div class="space-y-2 pt-2">
                                        <h4 class="text-[11px] font-black uppercase tracking-wider text-slate-700">Decision History</h4>
                                        <div class="space-y-2">
                                            @foreach ($doc->reviews as $r)
                                                <div class="rounded-xl border border-slate-200 bg-white p-3 text-xs flex flex-col gap-1 {{ $r->is_superseded ? 'opacity-60 bg-slate-50' : '' }}">
                                                    <div class="flex items-center justify-between">
                                                        <span class="font-bold text-slate-800">
                                                            {{ \Illuminate\Support\Str::headline($r->decision) }}
                                                            @if($r->is_superseded)
                                                                <span class="text-[9px] font-black uppercase bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded ml-1">Superseded</span>
                                                            @endif
                                                        </span>
                                                        <span class="text-[10px] text-slate-400 font-medium">{{ $r->reviewed_at?->format('M j, Y g:i A') }}</span>
                                                    </div>
                                                    @if($r->review_notes)
                                                        <p class="text-slate-600 italic font-medium">"{{ $r->review_notes }}"</p>
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

