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
            <span class="text-xs font-bold text-gray-800">{{ $facilitator->name }}</span>
        </header>

        <main class="space-y-8 p-8">
            @if (session('class_success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('class_success') }}</div>
            @endif
            @if ($errors->has('group'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('group') }}</div>
            @endif

            <section class="flex flex-col justify-between gap-6 lg:flex-row lg:items-start">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-850">{{ $researchClass->name }}</h1>
                        <span class="rounded-full px-3 py-1 text-[9px] font-bold uppercase {{ $researchClass->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">
                            {{ $researchClass->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    @if ($researchClass->description)<p class="mt-2 text-sm text-gray-500">{{ $researchClass->description }}</p>@endif
                </div>

                <div class="flex items-center gap-4">
                    <button type="button" @click="showCreateGroupModal = true" class="inline-flex items-center gap-2 rounded-xl bg-[#0e5c3a] px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#0a4a2e]">
                        <i class="ph ph-plus-circle text-base"></i><span>Create Research Group</span>
                    </button>
                    <div class="min-w-64 rounded-2xl border border-gray-100 bg-white px-5 py-4 shadow-sm">
                        <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Student Join Code</p>
                        <div class="mt-2 flex items-center justify-between gap-4">
                            <code class="text-lg font-extrabold tracking-widest text-[#0e5c3a]">{{ $joinCode ?? 'Unavailable' }}</code>
                            <button type="button" @if ($joinCode) @click="navigator.clipboard?.writeText(@js($joinCode)).then(() => { copied = true; setTimeout(() => copied = false, 1800) })" @endif class="h-9 w-9 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200" @disabled(! $joinCode)>
                                <i class="ph" :class="copied ? 'ph-check text-emerald-600' : 'ph-copy'"></i>
                            </button>
                        </div>
                        <p x-show="copied" x-cloak class="mt-2 text-[10px] font-bold text-emerald-600">Code copied.</p>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Facilitator</p>
                    <p class="mt-3 text-sm font-bold text-gray-850">{{ $facilitator->name }}</p>
                    <p class="mt-1 text-[10px] text-gray-500">{{ $facilitator->email }}</p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Enrollment</p>
                    <p class="mt-3 text-2xl font-bold text-gray-850">{{ $activeStudents }} / {{ $researchClass->max_students }}</p>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100">
                        <div
                            class="h-full rounded-full bg-[#0e5c3a]"
                            x-data="{ percentage: @js($capacityPercentage) }"
                            x-bind:style="{ width: percentage + '%' }"
                        ></div>
                    </div>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Research Groups</p>
                    <p class="mt-3 text-2xl font-bold text-gray-850">{{ $groupsCollection->count() }} Active</p>
                    <p class="mt-1 text-[10px] text-gray-500">{{ $unassignedCollection->count() }} Unassigned Student(s)</p>
                </div>
            </section>

            <!-- Research Groups List -->
            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-850">Research Groups (Phase 12)</h2>
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

                                <!-- Members List -->
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-2">Group Members</p>
                                    @if ($members->isEmpty())
                                        <p class="text-xs text-gray-400 italic">No members assigned yet.</p>
                                    @else
                                        <ul class="divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden">
                                            @foreach ($members as $mb)
                                                <li class="flex items-center justify-between p-3 bg-white">
                                                    <div>
                                                        <p class="text-xs font-bold text-gray-800">{{ $mb->student?->name }}</p>
                                                        <p class="text-[10px] text-gray-400">{{ $mb->student?->email }}</p>
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
                    <div>
                        <h2 class="text-base font-bold text-amber-900">Unassigned Enrolled Students ({{ $unassignedCollection->count() }})</h2>
                        <p class="text-xs text-amber-700">These active class members are not yet assigned to any research group.</p>
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
            <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-gray-100 p-6 md:flex-row md:items-center md:justify-between">
                    <div><h2 class="text-lg font-bold text-gray-850">Student Roster</h2><p class="mt-1 text-xs text-gray-500">Approved students in this class.</p></div>
                    <form method="GET" action="{{ route('facilitator.classes.show', $researchClass) }}" class="relative w-full md:w-80">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Search name or email" class="w-full rounded-xl border border-gray-200 py-2.5 pl-9 pr-4 text-xs focus:border-[#0e5c3a] focus:outline-none">
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[9px] uppercase tracking-wider text-gray-400"><tr><th class="px-6 py-3">Student</th><th class="px-6 py-3">Student ID</th><th class="px-6 py-3">Program</th><th class="px-6 py-3">Joined</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($enrollments as $enrollment)
                                @php($student = $enrollment->student)
                                <tr>
                                    <td class="px-6 py-4"><p class="text-sm font-bold text-gray-800">{{ $student?->name ?? 'Deleted account' }}</p><p class="mt-0.5 text-[10px] text-gray-500">{{ $student?->email }}</p></td>
                                    <td class="px-6 py-4 text-xs text-gray-600">{{ $student?->student_id ?: 'Not available' }}</td>
                                    <td class="px-6 py-4 text-xs text-gray-600">{{ $student?->program ?: 'Not available' }}</td>
                                    <td class="px-6 py-4 text-xs text-gray-600">{{ $enrollment->joined_at?->format('M j, Y') ?: 'Not available' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">{{ $search !== '' ? 'No students matched your search.' : 'No approved students have joined this class.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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
