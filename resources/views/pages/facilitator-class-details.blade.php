@extends('layouts.blank')

@php
    $joinCode = rescue(fn () => $researchClass->revealJoinCode(), null, report: false);
    $capacityPercentage = $researchClass->max_students > 0
        ? min(100, (int) round(($activeStudents / $researchClass->max_students) * 100))
        : 0;
@endphp

@section('content')
<style>[x-cloak] { display: none !important; }</style>

<div class="min-h-screen bg-[#f4f7f6] font-sans" x-data="{ copied: false, showGroupModal: @js($errors->hasAny(['group', 'creation_token', 'name'])) }">
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
            @if (session('group_success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('group_success') }}</div>
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
                    <p class="mt-3 text-2xl font-bold text-gray-850">{{ $groups->count() }}</p>
                    <button type="button" @click="showGroupModal = true" class="mt-3 text-xs font-bold text-[#0e5c3a] hover:underline">+ Create Group</button>
                </div>
            </section>

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
                        <thead class="bg-gray-50 text-[9px] uppercase tracking-wider text-gray-400"><tr><th class="px-6 py-3">Student</th><th class="px-6 py-3">Student ID</th><th class="px-6 py-3">Current Group</th><th class="px-6 py-3">Assign Group</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($enrollments as $enrollment)
                                @php($student = $enrollment->student)
                                <tr>
                                    <td class="px-6 py-4"><p class="text-sm font-bold text-gray-800">{{ $student?->name ?? 'Deleted account' }}</p><p class="mt-0.5 text-[10px] text-gray-500">{{ $student?->email }}</p></td>
                                    <td class="px-6 py-4 text-xs text-gray-600">{{ $student?->student_id ?: 'Not available' }}</td>
                                    <td class="px-6 py-4 text-xs font-semibold text-gray-700">{{ $enrollment->groupMembership?->group?->name ?? 'Unassigned' }}</td>
                                    <td class="px-6 py-4">
                                        @if ($groups->isNotEmpty())
                                            <form method="POST" action="{{ route('facilitator.classes.groups.students.assign', [$researchClass, '__group__', $enrollment]) }}" onsubmit="this.action = this.action.replace('__group__', this.querySelector('select').value)" class="flex gap-2">
                                                @csrf @method('PUT')
                                                <select required class="rounded-lg border border-gray-200 px-2 py-2 text-xs" onchange="this.form.action = this.form.action.replace('__group__', this.value)">
                                                    <option value="">Select group</option>
                                                    @foreach ($groups as $group)<option value="{{ $group->getKey() }}" @selected($enrollment->groupMembership?->research_class_group_id === $group->getKey())>{{ $group->name }}</option>@endforeach
                                                </select>
                                                <button type="submit" class="rounded-lg bg-[#0e5c3a] px-3 py-2 text-[10px] font-bold text-white">Assign</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-400">Create a group first</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">{{ $search !== '' ? 'No students matched your search.' : 'No approved students have joined this class.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($enrollments->hasPages())<div class="border-t border-gray-100 px-6 py-4">{{ $enrollments->links() }}</div>@endif
            </section>

            <section class="space-y-4">
                <div class="flex items-center justify-between"><div><h2 class="text-lg font-bold text-gray-850">Research Groups</h2><p class="mt-1 text-xs text-gray-500">Assign one adviser to each Capstone group.</p></div><button type="button" @click="showGroupModal = true" class="rounded-xl bg-[#0e5c3a] px-4 py-2.5 text-xs font-bold text-white"><i class="ph ph-plus mr-1"></i>Create Group</button></div>
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    @forelse ($groups as $group)
                        <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between"><div><h3 class="font-bold text-gray-850">{{ $group->name }}</h3><p class="mt-1 text-[10px] text-gray-500">{{ $group->members_count }} member(s)</p></div><i class="ph ph-users-three text-xl text-[#0e5c3a]"></i></div>
                            <form method="POST" action="{{ route('facilitator.classes.groups.adviser.assign', [$researchClass, $group]) }}" class="mt-4 flex gap-2 border-t border-gray-100 pt-4">
                                @csrf @method('PUT')
                                <select name="adviser_id" required class="min-w-0 flex-1 rounded-lg border border-gray-200 px-3 py-2 text-xs">
                                    <option value="">Select adviser</option>
                                    @foreach ($availableAdvisers as $adviser)<option value="{{ $adviser->getKey() }}" @selected($group->adviser_id === $adviser->getKey())>{{ $adviser->name }}</option>@endforeach
                                </select>
                                <button type="submit" class="rounded-lg bg-[#0e5c3a] px-3 py-2 text-[10px] font-bold text-white">Save</button>
                            </form>
                            <div class="mt-4 space-y-2">
                                @forelse ($group->members as $member)<div class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2"><div class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-[10px] font-bold text-emerald-700">{{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($member->student?->name ?? '?', 0, 1)) }}</div><span class="text-xs font-semibold text-gray-700">{{ $member->student?->name ?? 'Deleted account' }}</span></div>@empty<p class="text-xs text-gray-400">No students assigned.</p>@endforelse
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-gray-200 bg-white p-10 text-center text-sm text-gray-500">No groups created yet.</div>
                    @endforelse
                </div>
            </section>
        </main>
    </div>

    <div x-show="showGroupModal" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-xs">
        <div @click.away="showGroupModal = false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-start justify-between"><div><h2 class="font-bold text-gray-800">Create Research Group</h2><p class="mt-1 text-xs text-gray-500">Students can be assigned after the group is created.</p></div><button type="button" @click="showGroupModal = false" class="text-gray-400"><i class="ph ph-x text-lg"></i></button></div>
            <form method="POST" action="{{ route('facilitator.classes.groups.store', $researchClass) }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="creation_token" value="{{ old('creation_token', (string) Illuminate\Support\Str::uuid()) }}">
                <div><label for="group_name" class="mb-1.5 block text-xs font-bold text-gray-600">Group name</label><input id="group_name" name="name" value="{{ old('name') }}" required minlength="2" maxlength="120" placeholder="e.g. Group 1" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-xs focus:border-[#0e5c3a] focus:outline-none">@error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div class="flex justify-end gap-3"><button type="button" @click="showGroupModal = false" class="rounded-xl bg-gray-100 px-4 py-2 text-xs font-bold text-gray-700">Cancel</button><button type="submit" class="rounded-xl bg-[#0e5c3a] px-4 py-2 text-xs font-bold text-white">Create Group</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
