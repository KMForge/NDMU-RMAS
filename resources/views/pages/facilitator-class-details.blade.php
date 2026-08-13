@extends('layouts.blank')

@php
    $joinCode = rescue(fn () => $researchClass->revealJoinCode(), null, report: false);
    $capacityPercentage = $researchClass->max_students > 0
        ? min(100, (int) round(($activeStudents / $researchClass->max_students) * 100))
        : 0;
    $groupsCollection = $groups ?? collect();
    $unassignedCollection = $unassignedStudents ?? collect();
    $adviserOptions = $classAdviserOptions ?? collect();
@endphp

@section('content')
<style>[x-cloak] { display: none !important; }</style>

<div class="min-h-screen bg-[#f4f7f6] font-sans" x-data="{ copied: false, showCreateGroupModal: false }">
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
                {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($facilitator->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold">{{ $facilitator->name }}</p>
                <p class="mt-0.5 text-[10px] text-white/60">Research Facilitator</p>
            </div>
        </div>

        <nav class="flex-1 space-y-2 px-6 py-5">
            <a href="{{ route('facilitator.dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-white/90 hover:bg-white/5">
                <i class="ph ph-squares-four text-lg"></i><span>Dashboard</span>
            </a>
            <a href="{{ route('facilitator.dashboard', ['tab' => 'classes']) }}" class="flex items-center gap-3 rounded-xl bg-[#eebc3f] px-3 py-2.5 text-[13px] font-bold text-[#0e5c3a]">
                <i class="ph ph-chalkboard-teacher text-lg"></i><span>My Classes</span>
            </a>
        </nav>

        <div class="px-6 pb-6">
            <form method="POST" action="{{ route('logout') }}" data-confirm-logout>
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-[13px] font-semibold text-white/90 hover:bg-white/5">
                    <i class="ph ph-sign-out text-lg"></i><span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="min-h-screen pl-72">
        <header class="sticky top-0 z-10 flex h-20 items-center justify-between border-b border-gray-150 bg-white px-8">
            <a href="{{ route('facilitator.dashboard', ['tab' => 'classes']) }}" class="inline-flex items-center gap-2 text-xs font-bold text-gray-600 hover:text-[#0e5c3a]">
                <i class="ph ph-arrow-left"></i><span>Back to My Classes</span>
            </a>
            <div class="flex items-center gap-3">
                <x-workspace-switcher current="facilitator" />
                <span class="text-xs font-bold text-gray-800">{{ $facilitator->name }}</span>
            </div>
        </header>

        <main class="space-y-8 p-8">
            @if (session('class_success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('class_success') }}</div>
            @endif
            @if ($errors->has('group'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('group') }}</div>
            @endif
            @if (session('class_actor_success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('class_actor_success') }}</div>
            @endif
            @if ($errors->has('class_actor'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('class_actor') }}</div>
            @endif

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

                    <div class="flex flex-wrap items-center gap-4">
                        <button type="button" @click="showCreateGroupModal = true" class="inline-flex items-center gap-2 rounded-2xl bg-[#eebc3f] px-5 py-3 text-xs font-black text-[#0e5c3a] shadow-md hover:bg-[#f4c542] hover:shadow-lg transition-all duration-200 cursor-pointer">
                            <i class="ph ph-plus-circle text-lg"></i><span>Create Research Group</span>
                        </button>
                        <div class="min-w-64 rounded-2xl border border-white/20 bg-white/10 backdrop-blur-md px-5 py-3.5 text-white shadow-inner">
                            <p class="text-[9px] font-extrabold uppercase tracking-widest text-[#eebc3f]">Student Join Code</p>
                            <div class="mt-1 flex items-center justify-between gap-4">
                                <code class="text-xl font-black tracking-widest text-white font-mono">{{ $joinCode ?? 'Unavailable' }}</code>
                                <button type="button" @if ($joinCode) @click="navigator.clipboard?.writeText(@js($joinCode)).then(() => { copied = true; setTimeout(() => copied = false, 1800) })" @endif class="h-9 w-9 rounded-xl bg-white/15 text-white hover:bg-white/25 transition-all flex items-center justify-center cursor-pointer" @disabled(! $joinCode)>
                                    <i class="ph" :class="copied ? 'ph-check text-[#eebc3f]' : 'ph-copy'"></i>
                                </button>
                            </div>
                            <p x-show="copied" x-cloak class="mt-1 text-[10px] font-bold text-[#eebc3f]">Join code copied to clipboard!</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="rounded-2xl border border-gray-150 bg-white p-6 shadow-sm border-t-4 border-t-[#0e5c3a]">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Facilitator</p>
                        <span class="w-8 h-8 rounded-xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center font-bold"><i class="ph ph-user-gear text-base"></i></span>
                    </div>
                    <p class="mt-3 text-base font-bold text-gray-850">{{ $facilitator->name }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $facilitator->email }}</p>
                </div>
                <div class="rounded-2xl border border-gray-150 bg-white p-6 shadow-sm border-t-4 border-t-[#0e5c3a]">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Enrollment Capacity</p>
                        <span class="w-8 h-8 rounded-xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center font-bold"><i class="ph ph-users text-base"></i></span>
                    </div>
                    <p class="mt-3 text-2xl font-black text-gray-850">{{ $activeStudents }} <span class="text-sm font-medium text-gray-400">/ {{ $researchClass->max_students }} Enrolled</span></p>
                    <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-gray-100">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-[#0e5c3a] to-[#009b67]"
                            x-data="{ percentage: @js($capacityPercentage) }"
                            x-bind:style="{ width: percentage + '%' }"
                        ></div>
                    </div>
                </div>
                <div class="rounded-2xl border border-gray-150 bg-white p-6 shadow-sm border-t-4 border-t-[#0e5c3a]">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Research Groups</p>
                        <span class="w-8 h-8 rounded-xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center font-bold"><i class="ph ph-folder-user text-base"></i></span>
                    </div>
                    <p class="mt-3 text-2xl font-black text-gray-850">{{ $groupsCollection->count() }} <span class="text-sm font-medium text-emerald-700">Active Groups</span></p>
                    <p class="mt-1 text-xs text-amber-700 font-semibold">{{ $unassignedCollection->count() }} Unassigned Student(s)</p>
                </div>
            </section>

            <section class="rounded-3xl border border-gray-150 bg-white p-6 shadow-sm">
                <div class="mb-5 flex flex-col justify-between gap-3 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-extrabold text-[#0e5c3a]">Official Forms Institutional Actors</h2>
                        <p class="mt-1 text-xs text-gray-500">Assign the exact faculty members authorized to act for this class. This does not change their system roles.</p>
                    </div>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-700">Class scoped</span>
                </div>

                <div class="grid gap-5 lg:grid-cols-3">
                    @foreach ([
                        'research_instructor' => 'Research Instructor',
                        'program_coordinator' => 'Program Coordinator',
                        'dean' => 'College Dean',
                    ] as $actorType => $actorLabel)
                        @php
                            $assignment = $researchClass->officialFormActorAssignments->firstWhere('actor_type', $actorType);
                            $candidates = $classActorCandidates->get($actorType, collect());
                        @endphp
                        <article class="rounded-2xl border border-gray-200 p-5">
                            <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">{{ $actorLabel }}</p>
                            @if ($assignment)
                                <p class="mt-3 text-sm font-bold text-gray-900">{{ $assignment->user->name }}</p>
                                <p class="mt-1 truncate text-xs text-gray-500">{{ $assignment->user->email }}</p>
                                <p class="mt-3 text-[10px] leading-4 text-gray-500">
                                    Assigned {{ $assignment->assigned_at?->format('M j, Y g:i A') }}
                                    @if ($assignment->assigner) by {{ $assignment->assigner->name }} @endif
                                </p>
                            @else
                                <p class="mt-3 text-sm font-semibold text-gray-500">No active assignment</p>
                            @endif

                            <form method="POST" action="{{ route('facilitator.classes.form-actors.store', $researchClass) }}" class="mt-4 space-y-2">
                                @csrf
                                <input type="hidden" name="actor_type" value="{{ $actorType }}">
                                <select name="user_id" required class="w-full rounded-xl border border-gray-200 px-3 py-2 text-xs">
                                    <option value="">{{ $assignment ? 'Replace assignment' : 'Select eligible faculty' }}</option>
                                    @foreach ($candidates as $candidate)
                                        <option value="{{ $candidate->id }}">{{ $candidate->name }} — {{ $candidate->email }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="w-full rounded-xl bg-[#0e5c3a] px-3 py-2 text-xs font-bold text-white hover:bg-[#0a4a2e]">
                                    {{ $assignment ? 'Replace' : 'Assign' }} {{ $actorLabel }}
                                </button>
                            </form>

                            @if ($assignment)
                                <form method="POST" action="{{ route('facilitator.classes.form-actors.destroy', [$researchClass, $assignment]) }}" class="mt-2" onsubmit="return confirm('Deactivate this class actor assignment?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full rounded-xl border border-red-200 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50">Deactivate</button>
                                </form>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>

            <!-- Research Groups List -->
            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-7 rounded-full bg-[#0e5c3a]"></div>
                        <h2 class="text-xl font-extrabold text-[#0e5c3a]">Research Groups</h2>
                    </div>
                    <span class="text-xs font-semibold text-gray-500">Max 4 students per group</span>
                </div>

                @if ($groupsCollection->isEmpty())
                    <div class="rounded-2xl border border-gray-100 bg-white p-8 text-center text-sm text-gray-500">
                        No research groups created yet. Click <span class="font-bold text-[#0e5c3a]">Create Research Group</span> to organize your students.
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        @foreach ($groupsCollection as $grp)
                            @php
                                $members = $grp->members;
                                $pendingReq = $grp->adviserRequests->first();
                            @endphp
                            <div class="rounded-2xl border border-gray-150 bg-white p-6 shadow-sm space-y-4">
                                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                                    <div>
                                        <h3 class="font-bold text-base text-gray-850">{{ $grp->name }}</h3>
                                        <span class="text-[10px] font-semibold text-gray-400">Members: {{ $members->count() }} / 4</span>
                                    </div>
                                    <form method="POST" action="{{ route('facilitator.classes.groups.disband', [$researchClass, $grp]) }}" onsubmit="return confirm('Disband this group? Members will return to Unassigned Students.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-bold">Disband Group</button>
                                    </form>
                                </div>

                                <!-- Adviser Status -->
                                <div class="rounded-xl bg-gray-50 p-4 border border-gray-100 space-y-2">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Assigned Adviser</p>
                                    @if ($grp->adviser)
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-bold text-gray-800">{{ $grp->adviser->name }}</p>
                                                <p class="text-[10px] text-gray-500">{{ $grp->adviser->email }}</p>
                                            </div>
                                            <form method="POST" action="{{ route('facilitator.classes.groups.adviser.remove', [$researchClass, $grp]) }}" onsubmit="return confirm('Remove adviser from group?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-rose-600 hover:underline font-semibold">Remove</button>
                                            </form>
                                        </div>
                                    @elseif ($pendingReq)
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-xs font-semibold text-amber-700">Request Pending: {{ $pendingReq->adviser?->name }}</p>
                                                <p class="text-[10px] text-gray-400">Awaiting adviser acceptance</p>
                                            </div>
                                            <form method="POST" action="{{ route('facilitator.classes.groups.adviser-requests.cancel', [$researchClass, $grp, $pendingReq]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-gray-500 hover:text-gray-700 font-semibold">Cancel Request</button>
                                            </form>
                                        </div>
                                    @else
                                        <form method="POST" action="{{ route('facilitator.classes.groups.adviser-requests.store', [$researchClass, $grp]) }}" class="flex items-center gap-2">
                                            @csrf
                                            <select name="adviser_id" required class="flex-1 rounded-xl border border-gray-200 px-3 py-1.5 text-xs focus:border-[#0e5c3a] focus:outline-none">
                                                <option value="">Select Adviser...</option>
                                                @foreach ($adviserOptions as $adv)
                                                    <option value="{{ $adv->id }}">{{ $adv->name }} ({{ $adv->department ?? 'Faculty' }})</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="rounded-xl bg-[#0e5c3a] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#0a4a2e]">Send Request</button>
                                        </form>
                                    @endif
                                </div>

                                <!-- Members List & Leader Selector -->
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Group Members</p>
                                        @if ($members->isNotEmpty())
                                            <form method="POST" action="{{ route('facilitator.classes.groups.leader.assign', [$researchClass, $grp]) }}" class="flex items-center gap-1">
                                                @csrf
                                                @method('PUT')
                                                <select
                                                    name="student_id"
                                                    required
                                                    onchange="this.form.submit()"
                                                    aria-label="{{ $grp->leader_student_id ? 'Change Group Leader' : 'Assign Group Leader' }}"
                                                    class="rounded-lg border border-gray-200 px-2 py-1 text-[10px] font-bold text-gray-700 bg-white focus:border-[#0e5c3a] focus:outline-none cursor-pointer"
                                                >
                                                    <option value="" selected disabled>
                                                        {{ $grp->leader_student_id ? 'Change Group Leader...' : 'Assign Group Leader...' }}
                                                    </option>
                                                    @foreach ($members as $mbOpt)
                                                        <option value="{{ $mbOpt->student_id }}" @disabled((int) $grp->leader_student_id === (int) $mbOpt->student_id)>
                                                            {{ $mbOpt->student?->name }}{{ (int) $grp->leader_student_id === (int) $mbOpt->student_id ? ' (Current Leader)' : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        @endif
                                    </div>
                                    @if ($members->isEmpty())
                                        <p class="text-xs text-gray-400 italic">No members assigned yet.</p>
                                    @else
                                        <ul class="divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden">
                                            @foreach ($members as $mb)
                                                @php
                                                    $isGroupLeader = (int) $grp->leader_student_id === (int) $mb->student_id;
                                                @endphp
                                                <li @class([
                                                    'flex items-center justify-between p-3',
                                                    'bg-amber-50/70' => $isGroupLeader,
                                                    'bg-white' => ! $isGroupLeader,
                                                ])>
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <p class="text-xs font-bold text-gray-800">{{ $mb->student?->name }}</p>
                                                            @if ($isGroupLeader)
                                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                                                                    <i class="ph ph-star-fill" aria-hidden="true"></i>
                                                                    Group Leader
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <p class="text-[10px] text-gray-500">{{ $mb->student?->email }}</p>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <!-- Unassigned Students Section -->
            @if ($unassignedCollection->isNotEmpty())
                <section class="overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/50 shadow-sm p-6 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-7 rounded-full bg-amber-500"></div>
                        <div>
                            <h2 class="text-base font-bold text-amber-900">Unassigned Enrolled Students ({{ $unassignedCollection->count() }})</h2>
                            <p class="text-xs text-amber-700">These active class members are not yet assigned to any research group.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($unassignedCollection as $unEnr)
                            <div class="flex items-center justify-between bg-white rounded-xl p-4 border border-amber-150 shadow-2xs">
                                <div>
                                    <p class="text-xs font-bold text-gray-800">{{ $unEnr->student?->name }}</p>
                                    <p class="text-[10px] text-gray-500">{{ $unEnr->student?->email }}</p>
                                </div>
                                @if ($groupsCollection->isNotEmpty())
                                    <form method="POST" action="{{ route('facilitator.classes.groups.students.assign', [$researchClass, $groupsCollection->first(), $unEnr]) }}" x-data="{ targetGroup: '{{ $groupsCollection->first()?->id }}' }" :action="'/facilitator/classes/{{ $researchClass->id }}/groups/' + targetGroup + '/students/{{ $unEnr->id }}'" class="flex items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <select x-model="targetGroup" class="rounded-lg border border-gray-200 px-2 py-1 text-[11px] focus:outline-none">
                                            @foreach ($groupsCollection as $grpOpt)
                                                <option value="{{ $grpOpt->id }}">{{ $grpOpt->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="rounded-lg bg-[#0e5c3a] px-2.5 py-1 text-[11px] font-bold text-white hover:bg-[#0a4a2e]">Assign</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- Student Roster -->
            <section class="overflow-hidden rounded-2xl border border-gray-150 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-gray-150 p-6 md:flex-row md:items-center md:justify-between bg-gradient-to-r from-gray-50 to-white">
                    <div class="flex items-center gap-3">
                        <div class="w-2.5 h-8 rounded-full bg-[#0e5c3a]"></div>
                        <div>
                            <h2 class="text-xl font-extrabold text-[#0e5c3a]">Student Roster</h2>
                            <p class="mt-0.5 text-xs text-gray-500">Approved enrolled students in this research class.</p>
                        </div>
                    </div>
                    <form method="GET" action="{{ route('facilitator.classes.show', $researchClass) }}" class="relative w-full md:w-80">
                        <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Search name or email..." class="w-full rounded-xl border border-gray-200 py-2.5 pl-10 pr-4 text-xs focus:border-[#0e5c3a] focus:outline-none shadow-2xs">
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gradient-to-r from-[#0e5c3a] to-[#0a4a2e] text-white text-[10px] font-black uppercase tracking-wider shadow-xs">
                            <tr>
                                <th class="px-6 py-4">Student Details</th>
                                <th class="px-6 py-4">Student ID</th>
                                <th class="px-6 py-4">Academic Program</th>
                                <th class="px-6 py-4">Date Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($enrollments as $enrollment)
                                @php($student = $enrollment->student)
                                <tr class="hover:bg-emerald-50/40 transition-colors duration-150">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-emerald-100 text-[#0e5c3a] flex items-center justify-center font-bold text-xs shrink-0 border border-emerald-200">
                                                {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($student?->name ?? 'S', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-850">{{ $student?->name ?? 'Deleted account' }}</p>
                                                <p class="text-[11px] text-gray-500">{{ $student?->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-semibold text-gray-700">{{ $student?->student_id ?: 'Not available' }}</td>
                                    <td class="px-6 py-4 text-xs font-medium text-gray-700">{{ $student?->program ?: 'Not available' }}</td>
                                    <td class="px-6 py-4 text-xs text-gray-500">{{ $enrollment->joined_at?->format('M j, Y') ?: 'Not available' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">{{ $search !== '' ? 'No students matched your search.' : 'No approved students have joined this class.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @if ($enrollments->hasPages())<div class="border-t border-gray-100 px-6 py-4">{{ $enrollments->links() }}</div>@endif
            </section>

        </main>
    </div>

    <!-- Create Group Modal -->
    <div x-show="showCreateGroupModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showCreateGroupModal = false"></div>
        <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden" @click.stop>
            <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100">
                <div>
                    <h2 class="font-bold text-lg text-gray-850">Create Research Group</h2>
                    <p class="text-xs text-gray-500 mt-1">Organize students into capstone groups (1-4 members).</p>
                </div>
                <button type="button" @click="showCreateGroupModal = false" class="w-8 h-8 rounded-full hover:bg-gray-100 text-gray-500">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('facilitator.classes.groups.store', $researchClass) }}" class="p-6 space-y-5">
                @csrf
                <input type="hidden" name="creation_token" value="{{ Illuminate\Support\Str::uuid() }}">
                <div>
                    <label for="group_name" class="text-xs font-bold text-gray-700 block mb-2">Group Name</label>
                    <input
                        id="group_name"
                        name="name"
                        type="text"
                        required
                        minlength="2"
                        maxlength="120"
                        placeholder="e.g. Capstone Group 1"
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:outline-none"
                    >
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" @click="showCreateGroupModal = false" class="px-4 py-2.5 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl">
                        Create Group
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
