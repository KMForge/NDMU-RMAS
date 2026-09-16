@extends('layouts.blank')

@php
    $joinCode = rescue(fn () => $researchClass->revealJoinCode(), null, report: false);
    $capacityPercentage = $researchClass->max_students > 0
        ? min(100, (int) round(($activeStudents / $researchClass->max_students) * 100))
        : 0;
    $groupsCollection = $groups ?? collect();
    $disbandedGroupsCollection = $disbandedGroups ?? collect();
    $unassignedCollection = $unassignedStudents ?? collect();
    $adviserOptions = $classAdviserOptions ?? collect();
@endphp

@section('content')
<style>[x-cloak] { display: none !important; }</style>

<div class="min-h-screen bg-[#f4f7f6] font-sans flex" x-data="{ copied: false, showCreateGroupModal: false }">
    <x-facilitator-sidebar :user="$facilitator" active="classes" />

    <!-- Main Content Area -->
    <div class="min-h-screen flex-1 pl-72">
        <!-- Top Bar -->
        <header class="sticky top-0 z-40 flex h-20 items-center justify-between border-b border-slate-200/80 bg-white/95 backdrop-blur-md px-8">
            <a href="{{ route('facilitator.dashboard', ['tab' => 'classes']) }}" class="inline-flex items-center gap-2 text-xs font-black text-slate-700 hover:text-[#0e5c3a] transition-colors">
                <i class="ph ph-arrow-left text-sm text-[#0e5c3a]"></i>
                <span>Back to Capstone Classes</span>
            </a>
            <div class="flex items-center gap-3">
                <x-workspace-switcher current="facilitator" />
                <span class="text-xs font-bold text-slate-800">{{ $facilitator->name }}</span>
            </div>
        </header>

        <main class="space-y-8 p-6 sm:p-8">
            <!-- Flash Messages -->
            @if (session('class_success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-xs sm:text-sm font-semibold text-emerald-800 flex items-center gap-2.5 animate-fade-in" role="status">
                    <i class="ph ph-check-circle text-base text-emerald-600 shrink-0"></i>
                    <span>{{ session('class_success') }}</span>
                </div>
            @endif
            @if ($errors->has('group'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-xs sm:text-sm font-semibold text-rose-700 flex items-center gap-2.5 animate-fade-in" role="alert">
                    <i class="ph ph-warning-circle text-base text-rose-600 shrink-0"></i>
                    <span>{{ $errors->first('group') }}</span>
                </div>
            @endif
            @if (session('class_actor_success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-xs sm:text-sm font-semibold text-emerald-800 flex items-center gap-2.5 animate-fade-in" role="status">
                    <i class="ph ph-check-circle text-base text-emerald-600 shrink-0"></i>
                    <span>{{ session('class_actor_success') }}</span>
                </div>
            @endif
            @if ($errors->has('class_actor'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-xs sm:text-sm font-semibold text-rose-700 flex items-center gap-2.5 animate-fade-in" role="alert">
                    <i class="ph ph-warning-circle text-base text-rose-600 shrink-0"></i>
                    <span>{{ $errors->first('class_actor') }}</span>
                </div>
            @endif

            <!-- Class Hero Banner -->
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
                            <p class="text-xs sm:text-sm text-emerald-100/90 max-w-2xl leading-relaxed font-medium">{{ $researchClass->description }}</p>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <button
                            type="button"
                            @click="showCreateGroupModal = true"
                            class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-[#eebc3f] to-[#f4c542] hover:brightness-105 px-5 py-3 text-xs font-black text-[#073823] shadow-md hover:shadow-lg transition-all duration-200 cursor-pointer"
                        >
                            <i class="ph ph-plus-circle text-lg"></i>
                            <span>Create Research Group</span>
                        </button>

                        <div class="min-w-64 rounded-2xl border border-white/20 bg-white/10 backdrop-blur-md px-5 py-3.5 text-white shadow-inner">
                            <p class="text-[9px] font-extrabold uppercase tracking-widest text-[#eebc3f]">Student Join Code</p>
                            <div class="mt-1 flex items-center justify-between gap-4">
                                <code class="text-xl font-black tracking-widest text-white font-mono">{{ $joinCode ?? 'Unavailable' }}</code>
                                <button
                                    type="button"
                                    @if ($joinCode) @click="navigator.clipboard?.writeText(@js($joinCode)).then(() => { copied = true; setTimeout(() => copied = false, 1800) })" @endif
                                    class="h-9 w-9 rounded-xl bg-white/15 text-white hover:bg-white/25 transition-all flex items-center justify-center cursor-pointer"
                                    @disabled(! $joinCode)
                                >
                                    <i class="ph" :class="copied ? 'ph-check text-[#eebc3f] font-bold' : 'ph-copy'"></i>
                                </button>
                            </div>
                            <p x-show="copied" x-cloak class="mt-1 text-[10px] font-bold text-[#eebc3f]">Join code copied to clipboard!</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3 KPI Cards Row -->
            <section class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#0e5c3a]"></div>
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Lead Facilitator</p>
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center font-bold text-base"><i class="ph ph-user-gear"></i></span>
                    </div>
                    <p class="mt-3 text-base font-black text-slate-900">{{ $facilitator->name }}</p>
                    <p class="mt-0.5 text-xs text-slate-500 font-medium">{{ $facilitator->email }}</p>
                </div>

                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#eebc3f]"></div>
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Enrollment Capacity</p>
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center font-bold text-base"><i class="ph ph-users"></i></span>
                    </div>
                    <p class="mt-3 text-2xl font-black text-slate-900">{{ $activeStudents }} <span class="text-xs font-bold text-slate-400">/ {{ $researchClass->max_students }} Enrolled</span></p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-[#073823] via-[#0e5c3a] to-[#eebc3f] transition-all duration-500"
                            style="width: {{ $capacityPercentage }}%"
                        ></div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#0e5c3a] to-emerald-400"></div>
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">Research Groups</p>
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center font-bold text-base"><i class="ph ph-folder-user"></i></span>
                    </div>
                    <p class="mt-3 text-2xl font-black text-slate-900">{{ $groupsCollection->count() }} <span class="text-xs font-bold text-emerald-700">Active Cohorts</span></p>
                    <p class="mt-1 text-xs font-bold text-amber-700">{{ $unassignedCollection->count() }} Unassigned Student(s)</p>
                </div>
            </section>

            <!-- Institutional Actors Configuration -->
            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-sm">
                <div class="mb-5 flex flex-col justify-between gap-3 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-lg sm:text-xl font-black font-heading text-slate-900">Official Forms Institutional Signatories</h2>
                        <p class="mt-0.5 text-xs text-slate-500 font-medium">Designate the authorized faculty members for this specific class section routing slip.</p>
                    </div>
                    <span class="rounded-full bg-amber-50 border border-amber-200/80 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-amber-800">Class Scoped</span>
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
                        <article class="rounded-2xl border border-slate-200/80 bg-slate-50/50 p-5 flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a] block">{{ $actorLabel }}</span>
                                @if ($assignment)
                                    <p class="mt-2 text-sm font-black text-slate-900">{{ $assignment->user->name }}</p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500 font-medium">{{ $assignment->user->email }}</p>
                                    <p class="mt-2 text-[10px] font-bold text-slate-400">
                                        Assigned {{ $assignment->assigned_at?->format('M j, Y') }}
                                        @if ($assignment->assigner) by {{ $assignment->assigner->name }} @endif
                                    </p>
                                @else
                                    <p class="mt-2 text-xs font-semibold text-slate-400 italic">No active assignment</p>
                                @endif
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-200/80 space-y-2">
                                <form method="POST" action="{{ route('facilitator.classes.form-actors.store', $researchClass) }}" class="space-y-2">
                                    @csrf
                                    <input type="hidden" name="actor_type" value="{{ $actorType }}">
                                    <select name="user_id" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium focus:border-[#0e5c3a] focus:outline-none">
                                        <option value="">{{ $assignment ? 'Replace assignment...' : 'Select eligible faculty...' }}</option>
                                        @foreach ($candidates as $candidate)
                                            <option value="{{ $candidate->id }}">{{ $candidate->name }} ({{ $candidate->email }})</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="w-full rounded-xl bg-[#0e5c3a] hover:bg-[#073823] px-3 py-2 text-xs font-bold text-white transition-colors cursor-pointer">
                                        {{ $assignment ? 'Replace' : 'Assign' }} {{ $actorLabel }}
                                    </button>
                                </form>

                                @if ($assignment)
                                    <form method="POST" action="{{ route('facilitator.classes.form-actors.destroy', [$researchClass, $assignment]) }}" onsubmit="return confirm('Deactivate this signatory assignment?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full rounded-xl border border-rose-200 bg-rose-50/80 hover:bg-rose-100 text-rose-700 px-3 py-1.5 text-xs font-bold transition-colors cursor-pointer">
                                            Deactivate
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <!-- Research Groups List -->
            <section class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2 h-6 rounded-full bg-[#0e5c3a]"></span>
                        <h2 class="text-lg sm:text-xl font-black font-heading text-slate-900">Research Groups</h2>
                    </div>
                    <span class="text-xs font-bold text-slate-400">Max 4 students per group</span>
                </div>

                @if ($groupsCollection->isEmpty())
                    <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-white p-8 text-center text-xs text-slate-500 space-y-2">
                        <i class="ph ph-folder-dashed text-3xl text-slate-300"></i>
                        <p class="font-bold text-slate-700">No research groups created yet.</p>
                        <p class="text-slate-400">Click <strong>Create Research Group</strong> to organize your enrolled students.</p>
                    </div>
                @else
                    <div class="grid min-w-0 grid-cols-1 gap-6 xl:grid-cols-2">
                        @foreach ($groupsCollection as $grp)
                            @php
                                $members = $grp->members;
                                $pendingReq = $grp->adviserRequests->first();
                            @endphp
                            <div class="min-w-0 rounded-3xl border border-slate-200/80 bg-white p-4 shadow-sm space-y-4 flex flex-col justify-between sm:p-6">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-3">
                                        <div class="min-w-0">
                                            <h3 class="font-black font-heading text-base text-slate-900">{{ $grp->name }}</h3>
                                            <span class="text-[10px] font-bold text-slate-400">Members: {{ $members->count() }} / 4</span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <form method="POST" action="{{ route('facilitator.classes.groups.reset-progress', [$researchClass, $grp]) }}" onsubmit="return confirm('Reset all progress, forms, and defense schedules for {{ $grp->name }}? This will give the group a fresh slate for a dry run.')">
                                                @csrf
                                                <button type="submit" class="text-xs text-amber-600 hover:text-amber-800 font-bold transition-colors cursor-pointer" title="Reset progress, forms, and defense schedules">Reset Progress</button>
                                            </form>
                                            <form method="POST" action="{{ route('facilitator.classes.groups.disband', [$researchClass, $grp]) }}" onsubmit="return confirm('Disband and archive this group? Active memberships and adviser access will end, but the topic, progress, documents, forms, reviews, schedules, evaluations, and history will be preserved.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-bold transition-colors cursor-pointer">Disband &amp; Archive</button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Adviser Status -->
                                    <div class="mt-3.5 rounded-2xl bg-slate-50 p-4 border border-slate-200/80 space-y-2">
                                        <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Assigned Adviser</p>
                                        @if ($grp->adviser)
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="min-w-0">
                                                    <p class="break-words text-xs font-black text-slate-900">{{ $grp->adviser->name }}</p>
                                                    <p class="break-all text-[10px] text-slate-500">{{ $grp->adviser->email }}</p>
                                                </div>
                                                <span class="text-left text-[10px] font-semibold leading-4 text-amber-700 sm:max-w-44 sm:text-right">Change requires a group-leader RES-030 request and authorized approval.</span>
                                            </div>
                                        @elseif ($pendingReq)
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-xs font-bold text-amber-800">Pending Request: {{ $pendingReq->adviser?->name }}</p>
                                                    <p class="text-[10px] text-slate-400">Awaiting adviser acceptance</p>
                                                </div>
                                                <form method="POST" action="{{ route('facilitator.classes.groups.adviser-requests.cancel', [$researchClass, $grp, $pendingReq]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-slate-500 hover:text-slate-700 font-bold">Cancel</button>
                                                </form>
                                            </div>
                                        @else
                                            <form method="POST" action="{{ route('facilitator.classes.groups.adviser-requests.store', [$researchClass, $grp]) }}" class="grid min-w-0 grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                                                @csrf
                                                <select name="adviser_id" required class="block min-w-0 w-full max-w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs focus:border-[#0e5c3a] focus:outline-none">
                                                    <option value="">Select Adviser...</option>
                                                    @foreach ($adviserOptions as $adv)
                                                        <option value="{{ $adv->id }}">{{ $adv->name }} ({{ $adv->department ?? 'Faculty' }})</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="w-full shrink-0 whitespace-nowrap rounded-xl bg-[#0e5c3a] px-3.5 py-1.5 text-xs font-bold text-white hover:bg-[#073823] transition-colors cursor-pointer sm:w-auto">
                                                    Request
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    <!-- Members List -->
                                    <div class="mt-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Group Members</p>
                                            @if ($members->isNotEmpty())
                                                <form method="POST" action="{{ route('facilitator.classes.groups.leader.assign', [$researchClass, $grp]) }}" class="flex items-center gap-1">
                                                    @csrf
                                                    @method('PUT')
                                                    <select
                                                        name="student_id"
                                                        required
                                                        onchange="this.form.submit()"
                                                        aria-label="{{ $grp->leader_student_id ? 'Change Group Leader' : 'Assign Group Leader' }}"
                                                        class="rounded-lg border border-slate-200 px-2 py-1 text-[10px] font-bold text-slate-700 bg-white focus:border-[#0e5c3a] focus:outline-none cursor-pointer"
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
                                            <p class="text-xs text-slate-400 italic">No members assigned yet.</p>
                                        @else
                                            <ul class="divide-y divide-slate-100 border border-slate-200/80 rounded-2xl overflow-hidden">
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
                                                                <p class="text-xs font-bold text-slate-900">{{ $mb->student?->name }}</p>
                                                                @if ($isGroupLeader)
                                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                                                                        <i class="ph ph-crown text-amber-600"></i>
                                                                        <span>Leader</span>
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <p class="text-[10px] text-slate-500 font-medium">{{ $mb->student?->email }}</p>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>

                                    @if ($members->count() > 1)
                                        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50/70 p-4">
                                            <p class="text-[10px] font-black uppercase tracking-wider text-amber-800">One member will continue this topic</p>
                                            <p class="mt-1 text-[10px] leading-4 text-amber-700">Keeps this project ID and all progress. Other members are archived from this group.</p>
                                            <form method="POST" action="{{ route('facilitator.classes.groups.continue-with-member', [$researchClass, $grp]) }}" class="mt-3 grid min-w-0 grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_auto]" onsubmit="return confirm('Continue this same research project with only the selected member? All existing research records will remain attached to the project.')">
                                                @csrf
                                                <select name="student_id" required class="block min-w-0 w-full rounded-xl border border-amber-200 bg-white px-3 py-2 text-xs focus:border-[#0e5c3a] focus:outline-none">
                                                    <option value="">Select continuing member...</option>
                                                    @foreach ($members as $member)
                                                        <option value="{{ $member->student_id }}">{{ $member->student?->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="w-full rounded-xl bg-amber-500 px-4 py-2 text-xs font-black text-amber-950 transition-colors hover:bg-amber-400 sm:w-auto">
                                                    Continue Project
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            @if ($disbandedGroupsCollection->isNotEmpty())
                <section class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <span class="h-6 w-2 rounded-full bg-slate-400"></span>
                        <div>
                            <h2 class="text-lg font-black font-heading text-slate-900 sm:text-xl">Archived Research Projects</h2>
                            <p class="text-xs text-slate-500">Restore the same project when one former member will continue the topic.</p>
                        </div>
                    </div>

                    <div class="grid min-w-0 grid-cols-1 gap-4 xl:grid-cols-2">
                        @foreach ($disbandedGroupsCollection as $archivedGroup)
                            <article class="min-w-0 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h3 class="break-words text-base font-black text-slate-900">{{ $archivedGroup->name }}</h3>
                                        <p class="text-[10px] font-semibold text-slate-400">Archived {{ $archivedGroup->disbanded_at?->diffForHumans() }}</p>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-slate-600">Records preserved</span>
                                </div>

                                @if ($archivedGroup->memberHistories->isEmpty())
                                    <p class="mt-4 rounded-xl bg-slate-50 p-3 text-xs text-slate-500">No eligible former member is currently available to restore this project.</p>
                                @else
                                    <form method="POST" action="{{ route('facilitator.classes.groups.restore-with-member', [$researchClass, $archivedGroup]) }}" class="mt-4 grid min-w-0 grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_auto]" onsubmit="return confirm('Restore this exact project for the selected former member? Previous progress and records will remain intact. A new adviser assignment may be required.')">
                                        @csrf
                                        <select name="student_id" required class="block min-w-0 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0e5c3a] focus:outline-none">
                                            <option value="">Select former member...</option>
                                            @foreach ($archivedGroup->memberHistories as $history)
                                                <option value="{{ $history->student_id }}">{{ $history->student?->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="w-full rounded-xl bg-[#0e5c3a] px-4 py-2 text-xs font-black text-white transition-colors hover:bg-[#073823] sm:w-auto">
                                            Restore &amp; Continue
                                        </button>
                                    </form>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- Unassigned Students Section -->
            @if ($unassignedCollection->isNotEmpty())
                <section
                    class="overflow-hidden rounded-3xl border border-amber-200 bg-amber-50/60 shadow-sm p-6 sm:p-7 space-y-4"
                    x-data="{
                        selectedStudents: [],
                        allIds: {{ json_encode($unassignedCollection->pluck('id')->all()) }},
                        toggleAll() {
                            if (this.selectedStudents.length === this.allIds.length) {
                                this.selectedStudents = [];
                            } else {
                                this.selectedStudents = [...this.allIds];
                            }
                        }
                    }"
                >
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="w-2.5 h-8 rounded-full bg-amber-500"></span>
                            <div>
                                <h2 class="text-base font-black text-amber-950">Unassigned Enrolled Students ({{ $unassignedCollection->count() }})</h2>
                                <p class="text-xs text-amber-800 font-medium">Select students to bulk-assign them into a research group.</p>
                            </div>
                        </div>

                        @if ($groupsCollection->isNotEmpty())
                            <form method="POST" action="{{ route('facilitator.classes.groups.students.bulk-assign', $researchClass) }}" class="flex flex-wrap items-center gap-2 bg-white/90 p-2 rounded-2xl border border-amber-200 shadow-2xs">
                                @csrf
                                <template x-for="id in selectedStudents" :key="id">
                                    <input type="hidden" name="enrollment_ids[]" :value="id">
                                </template>

                                <select name="group_id" required class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold focus:border-[#0e5c3a] focus:outline-none">
                                    @foreach ($groupsCollection as $grpOpt)
                                        <option value="{{ $grpOpt->id }}">{{ $grpOpt->name }} ({{ $grpOpt->members->count() }}/4 members)</option>
                                    @endforeach
                                </select>

                                <button
                                    type="submit"
                                    :disabled="selectedStudents.length === 0"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-[#0e5c3a] px-4 py-1.5 text-xs font-bold text-white hover:bg-[#073823] disabled:opacity-50 disabled:cursor-not-allowed transition-all cursor-pointer"
                                >
                                    <i class="ph ph-user-plus text-sm"></i>
                                    <span>Assign Selected (<span x-text="selectedStudents.length">0</span>)</span>
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-amber-200/80">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-amber-900 select-none">
                            <input
                                type="checkbox"
                                :checked="selectedStudents.length === allIds.length && allIds.length > 0"
                                @change="toggleAll()"
                                class="rounded border-amber-300 text-[#0e5c3a] focus:ring-[#0e5c3a] h-4 w-4"
                            >
                            <span>Select All ({{ $unassignedCollection->count() }})</span>
                        </label>
                        <span x-show="selectedStudents.length > 0" class="text-xs font-bold text-[#0e5c3a]" x-cloak>
                            <span x-text="selectedStudents.length"></span> student(s) selected
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($unassignedCollection as $unEnr)
                            <div class="flex items-center justify-between bg-white rounded-2xl p-4 border border-amber-200 shadow-2xs hover:border-amber-400 transition-colors">
                                <div class="flex items-center gap-3 min-w-0 pr-2">
                                    <input
                                        type="checkbox"
                                        value="{{ $unEnr->id }}"
                                        x-model.number="selectedStudents"
                                        class="rounded border-slate-300 text-[#0e5c3a] focus:ring-[#0e5c3a] h-4 w-4 cursor-pointer shrink-0"
                                    >
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-900 truncate">{{ $unEnr->student?->name }}</p>
                                        <p class="text-[10px] text-slate-500 font-medium truncate">{{ $unEnr->student?->email }}</p>
                                    </div>
                                </div>

                                @if ($groupsCollection->isNotEmpty())
                                    <form method="POST" action="{{ route('facilitator.classes.groups.students.bulk-assign', $researchClass) }}" class="flex items-center gap-1.5 shrink-0">
                                        @csrf
                                        <input type="hidden" name="enrollment_ids[]" value="{{ $unEnr->id }}">
                                        <select name="group_id" class="rounded-lg border border-slate-200 px-2 py-1 text-[11px] focus:outline-none max-w-28 truncate">
                                            @foreach ($groupsCollection as $grpOpt)
                                                <option value="{{ $grpOpt->id }}">{{ $grpOpt->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="rounded-lg bg-[#0e5c3a] px-2.5 py-1 text-[11px] font-bold text-white hover:bg-[#073823] transition-colors shrink-0">Assign</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- Student Roster -->
            <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-100 p-6 md:flex-row md:items-center md:justify-between bg-slate-50/60">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-7 rounded-full bg-[#0e5c3a]"></span>
                        <div>
                            <h2 class="text-lg sm:text-xl font-black font-heading text-slate-900">Student Roster</h2>
                            <p class="mt-0.5 text-xs text-slate-500 font-medium">All approved students currently enrolled in this Capstone class.</p>
                        </div>
                    </div>
                    <form method="GET" action="{{ route('facilitator.classes.show', $researchClass) }}" class="relative w-full md:w-80">
                        <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input
                            type="search"
                            name="q"
                            value="{{ $search }}"
                            maxlength="100"
                            placeholder="Search name, ID or email..."
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
                                <th class="px-6 py-3.5">Academic Program</th>
                                <th class="px-6 py-3.5">Date Enrolled</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-xs">
                            @forelse ($enrollments as $enrollment)
                                @php($student = $enrollment->student)
                                <tr class="hover:bg-emerald-50/40 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-emerald-100 text-[#0e5c3a] flex items-center justify-center font-black text-xs shrink-0 border border-emerald-200">
                                                {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($student?->name ?? 'S', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-slate-900">{{ $student?->name ?? 'Student Account' }}</p>
                                                <p class="text-[10px] text-slate-500 font-medium">{{ $student?->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-mono font-bold text-slate-700">{{ $student?->student_id ?: 'Not specified' }}</td>
                                    <td class="px-6 py-4 font-medium text-slate-700">{{ $student?->program ?: 'Not specified' }}</td>
                                    <td class="px-6 py-4 text-slate-500 font-medium">{{ $enrollment->joined_at?->format('M j, Y') ?: 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-xs text-slate-400">
                                        {{ $search !== '' ? 'No students matched your search criteria.' : 'No approved students have joined this class yet.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($enrollments->hasPages())
                    <div class="border-t border-slate-100 px-6 py-4">{{ $enrollments->links() }}</div>
                @endif
            </section>
        </main>
    </div>

    <!-- Create Group Modal -->
    <div x-show="showCreateGroupModal" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
        <div @click.away="showCreateGroupModal = false" class="w-full max-w-md rounded-3xl bg-white p-6 sm:p-7 shadow-2xl space-y-5 border border-slate-200 relative overflow-hidden">
            <!-- Top Accent Stripe -->
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>

            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-black font-heading text-slate-900 tracking-tight">Create Research Group</h2>
                    <p class="mt-0.5 text-xs text-slate-500 font-medium">Organize students into capstone cohorts (1-4 members).</p>
                </div>
                <button type="button" @click="showCreateGroupModal = false" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center text-base cursor-pointer">
                    <i class="ph ph-x font-bold"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('facilitator.classes.groups.store', $researchClass) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="creation_token" value="{{ Illuminate\Support\Str::uuid() }}">
                <div class="space-y-1.5">
                    <label for="group_name" class="text-[11px] font-bold uppercase tracking-wider text-slate-700 block">Group Name</label>
                    <input
                        id="group_name"
                        name="name"
                        type="text"
                        required
                        minlength="2"
                        maxlength="120"
                        placeholder="e.g. Capstone Research Group 1"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 hover:bg-white focus:bg-white px-4 py-2.5 text-xs font-medium text-slate-900 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 focus:outline-none transition-all"
                    >
                </div>

                <div class="flex justify-end gap-2.5 pt-2">
                    <button type="button" @click="showCreateGroupModal = false" class="rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 px-4.5 py-2.5 text-xs font-bold text-slate-700 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-xl bg-gradient-to-r from-[#073823] to-[#0e5c3a] hover:brightness-110 px-5 py-2.5 text-xs font-black text-white shadow-md shadow-emerald-950/20 transition-all cursor-pointer">
                        Create Group
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
