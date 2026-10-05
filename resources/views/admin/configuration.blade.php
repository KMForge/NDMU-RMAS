<div class="space-y-7">
    @error('configuration')
        <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
            <i class="ph ph-warning-circle mt-0.5 text-xl"></i><span>{{ $message }}</span>
        </div>
    @enderror

    <div class="flex gap-3 rounded-2xl border border-blue-200 bg-blue-50/70 p-4 text-xs text-blue-900">
        <i class="ph ph-shield-check mt-0.5 text-xl text-blue-700"></i>
        <div>
            <p class="font-extrabold">Dependency protection is enabled</p>
            <p class="mt-1 leading-5 text-blue-900/75">Items currently used by people, active workflows, or upcoming defenses cannot be disabled. The usage labels explain what must be reassigned or completed first.</p>
        </div>
    </div>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <form wire:submit="createDepartment" class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-[#0e5c3a]">
                    <i class="ph ph-buildings"></i>
                </span>
                <div>
                    <h2 class="font-heading text-lg font-black text-slate-900">Add Department</h2>
                    <p class="text-xs text-slate-500">Create a department under {{ $configurationCollege?->name ?? 'the configured college' }}.</p>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-[.45fr_1fr]">
                <div>
                    <label class="mb-2 block text-[10px] font-black uppercase tracking-wider text-slate-500">Code</label>
                    <input wire:model="newDepartmentCode" maxlength="30" placeholder="CSD" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm uppercase focus:border-[#0e5c3a] focus:outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-[10px] font-black uppercase tracking-wider text-slate-500">Name</label>
                    <input wire:model="newDepartmentName" maxlength="255" placeholder="Computer Studies Department" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:outline-none">
                </div>
            </div>
            @error('newDepartmentCode') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('newDepartmentName') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            <button class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#0e5c3a] hover:bg-[#073823] px-5 py-2.5 text-xs font-black text-white shadow-2xs transition-colors disabled:opacity-60" wire:loading.attr="disabled" wire:target="createDepartment">
                <i class="ph ph-plus"></i>Add Department
            </button>
        </form>

        <form wire:submit="createProgram" class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-[#0e5c3a]">
                    <i class="ph ph-graduation-cap"></i>
                </span>
                <div>
                    <h2 class="font-heading text-lg font-black text-slate-900">Add Program</h2>
                    <p class="text-xs text-slate-500">Programs are assigned to one active department.</p>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <select wire:model="newProgramDepartmentId" class="rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:outline-none">
                    <option value="">Select department</option>
                    @foreach (($configurationCollege?->departments ?? collect())->where('is_active', true) as $department)
                        <option value="{{ $department->id }}">{{ $department->code }} — {{ $department->name }}</option>
                    @endforeach
                </select>
                <input wire:model="newProgramCode" maxlength="30" placeholder="BSIT" class="rounded-xl border border-slate-200 px-4 py-3 text-sm uppercase focus:border-[#0e5c3a] focus:outline-none">
                <input wire:model="newProgramName" maxlength="255" placeholder="Bachelor of Science in Information Technology" class="rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:outline-none sm:col-span-2">
                <input wire:model="newProgramDegreeLevel" maxlength="50" placeholder="Bachelor" class="rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:outline-none">
            </div>
            @error('newProgramDepartmentId') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('newProgramCode') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('newProgramName') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            <button class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#0e5c3a] hover:bg-[#073823] px-5 py-2.5 text-xs font-black text-white shadow-2xs transition-colors disabled:opacity-60" wire:loading.attr="disabled" wire:target="createProgram">
                <i class="ph ph-plus"></i>Add Program
            </button>
        </form>
    </section>

    <!-- ACADEMIC STRUCTURE -->
    <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/50 px-6 py-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-[#0e5c3a]">
                        <i class="ph ph-tree-structure"></i>
                    </span>
                    <div>
                        <h2 class="font-heading text-lg font-black text-slate-900">Academic Structure</h2>
                        <p class="text-xs text-slate-500">Institutional records are deactivated, never deleted. Active assignments must be moved first.</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 font-bold text-slate-700 shadow-2xs">
                        <i class="ph ph-buildings text-[#0e5c3a]"></i>
                        {{ ($configurationCollege?->departments ?? collect())->count() }} Departments
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 font-bold text-slate-700 shadow-2xs">
                        <i class="ph ph-graduation-cap text-[#0e5c3a]"></i>
                        {{ ($configurationCollege?->departments ?? collect())->sum(fn($d) => $d->programs->count()) }} Programs
                    </span>
                </div>
            </div>
        </div>

        <div class="space-y-5 p-6 bg-slate-50/40">
            @forelse ($configurationCollege?->departments ?? collect() as $department)
                @php($departmentUsage = (int) $department->active_programs_count + (int) $department->active_faculty_count)
                <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xs transition-all duration-200 hover:border-slate-300 hover:shadow-xs">
                    <!-- Department Header Bar -->
                    <div class="border-b border-slate-100 bg-white px-5 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <span class="flex h-10 min-w-10 px-2.5 items-center justify-center rounded-xl bg-gradient-to-br from-[#0e5c3a] to-[#073823] text-xs font-black text-white shadow-xs tracking-wider">
                                    {{ $department->code }}
                                </span>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-heading text-sm sm:text-base font-black text-slate-900 truncate">
                                            {{ $department->name }}
                                        </h3>
                                        @if ($department->is_active)
                                            @if ($departmentUsage > 0)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-black uppercase text-amber-800 border border-amber-200/70" title="Active programs or faculty assigned">
                                                    <i class="ph ph-lock-key text-xs text-amber-600"></i> In use
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-black uppercase text-[#0e5c3a] border border-emerald-200/70">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Active
                                                </span>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-black uppercase text-slate-500 border border-slate-200">
                                                Deactivated
                                            </span>
                                        @endif
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                        <span class="inline-flex items-center gap-1 font-medium">
                                            <i class="ph ph-graduation-cap text-[#0e5c3a]"></i>
                                            <strong>{{ $department->programs->count() }}</strong> program(s)
                                        </span>
                                        <span class="text-slate-300">·</span>
                                        <span class="inline-flex items-center gap-1 font-medium">
                                            <i class="ph ph-users text-[#0e5c3a]"></i>
                                            <strong>{{ $department->active_faculty_count }}</strong> active faculty
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                @if ($department->is_active && $departmentUsage > 0)
                                    <button type="button" disabled title="Reassign active programs and faculty first." class="cursor-not-allowed inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100/80 px-3.5 py-2 text-xs font-bold text-slate-400 opacity-70">
                                        <i class="ph ph-lock text-xs"></i> Deactivate
                                    </button>
                                @elseif ($department->is_active)
                                    <button type="button" wire:click="setDepartmentActive({{ $department->id }}, false)" wire:confirm="Deactivate this unused department?" class="inline-flex items-center gap-1.5 rounded-xl border border-red-200 bg-white px-3.5 py-2 text-xs font-bold text-red-600 hover:bg-red-50 hover:border-red-300 transition-colors shadow-2xs">
                                        <i class="ph ph-power text-xs"></i> Deactivate
                                    </button>
                                @else
                                    <button type="button" wire:click="setDepartmentActive({{ $department->id }}, true)" wire:confirm="Activate this department?" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-bold text-[#0e5c3a] hover:bg-[#0e5c3a] hover:text-white transition-all shadow-2xs">
                                        <i class="ph ph-check-circle text-xs"></i> Activate
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Programs Container -->
                    <div class="bg-slate-50/50 p-4 sm:p-5">
                        <div class="mb-3 flex items-center justify-between">
                            <h4 class="text-[11px] font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                                <i class="ph ph-stack text-slate-400"></i> Degree Programs ({{ $department->programs->count() }})
                            </h4>
                        </div>

                        @if ($department->programs->isNotEmpty())
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($department->programs as $program)
                                    @php($programUsage = (int) $program->active_students_count + (int) $program->research_groups_count)
                                    <div class="group relative flex flex-col justify-between rounded-xl border border-slate-200/90 bg-white p-4 shadow-2xs transition-all duration-200 hover:border-emerald-300 hover:shadow-xs">
                                        <div>
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-1.5 min-w-0">
                                                    <span class="rounded-lg bg-emerald-50 border border-emerald-200/70 px-2 py-0.5 text-xs font-black text-[#0e5c3a] tracking-wide">
                                                        {{ $program->code }}
                                                    </span>
                                                    @if ($program->is_active)
                                                        @if ($programUsage > 0)
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200/70 px-2 py-0.5 text-[9px] font-black uppercase text-amber-800" title="Active students or research groups assigned">
                                                                <i class="ph ph-lock-key text-[10px]"></i> In use
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 text-[9px] font-black uppercase text-emerald-700">
                                                                Active
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="inline-flex items-center rounded-full bg-slate-100 border border-slate-200 px-2 py-0.5 text-[9px] font-black uppercase text-slate-500">
                                                            Disabled
                                                        </span>
                                                    @endif
                                                </div>

                                                <div>
                                                    @if ($program->is_active && $programUsage > 0)
                                                        <button type="button" disabled title="Reassign active students and research groups first." class="cursor-not-allowed inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-100/90 px-2.5 py-1 text-[10px] font-bold text-slate-400 opacity-70">
                                                            <i class="ph ph-lock text-[10px]"></i> Disable
                                                        </button>
                                                    @elseif ($program->is_active)
                                                        <button type="button" wire:click="setProgramActive({{ $program->id }}, false)" wire:confirm="Disable this unused program?" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-2.5 py-1 text-[10px] font-bold text-red-600 hover:bg-red-50 hover:border-red-300 transition-colors shadow-2xs">
                                                            <i class="ph ph-prohibit text-[10px]"></i> Disable
                                                        </button>
                                                    @else
                                                        <button type="button" wire:click="setProgramActive({{ $program->id }}, true)" wire:confirm="Enable this program?" class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-[#0e5c3a] hover:bg-[#0e5c3a] hover:text-white transition-all shadow-2xs">
                                                            <i class="ph ph-check-circle text-[10px]"></i> Enable
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>

                                            <p class="mt-2.5 text-xs font-bold text-slate-800 leading-snug line-clamp-2" title="{{ $program->name }}">
                                                {{ $program->name }}
                                            </p>
                                        </div>

                                        <div class="mt-3.5 pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2 text-[10px] text-slate-500">
                                            <span class="inline-flex items-center gap-1 font-semibold text-slate-600">
                                                <i class="ph ph-student text-[#0e5c3a] text-xs"></i>
                                                {{ $program->active_students_count }} active student(s)
                                            </span>
                                            <span class="inline-flex items-center gap-1 font-semibold text-slate-600">
                                                <i class="ph ph-users-three text-[#0e5c3a] text-xs"></i>
                                                {{ $program->research_groups_count }} group(s)
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-xl border border-dashed border-slate-200 bg-white p-6 text-center text-xs text-slate-400">
                                <i class="ph ph-folder-open text-2xl text-slate-300 block mb-1"></i>
                                No programs configured in this department yet.
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-sm text-slate-500">No departments are configured for this college.</div>
            @endforelse
        </div>
    </section>

    <!-- DEFENSE ROOMS & OFFICIAL FORM CATALOG -->
    <section class="grid grid-cols-1 gap-6 xl:grid-cols-[.8fr_1.2fr]">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-[#0e5c3a]">
                    <i class="ph ph-chalkboard-teacher"></i>
                </span>
                <div>
                    <h2 class="font-heading text-lg font-black text-slate-900">Defense Rooms</h2>
                    <p class="text-xs text-slate-500">Rooms with upcoming schedules or active defense sessions cannot be disabled.</p>
                </div>
            </div>
            <form wire:submit="createDefenseRoom" class="grid gap-3">
                <input wire:model="newDefenseRoomCode" maxlength="50" placeholder="CEAC-101" class="rounded-xl border border-slate-200 px-4 py-3 text-sm uppercase focus:border-[#0e5c3a] focus:outline-none">
                <input wire:model="newDefenseRoomName" maxlength="255" placeholder="CEAC Conference Room" class="rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:outline-none">
                <textarea wire:model="newDefenseRoomLocation" maxlength="1000" rows="2" placeholder="Location notes" class="rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:outline-none"></textarea>
                <button class="rounded-xl bg-[#0e5c3a] hover:bg-[#073823] px-5 py-2.5 text-xs font-black text-white shadow-2xs transition-colors">Add Room</button>
            </form>
            <div class="mt-5 space-y-2.5">
                @foreach ($configurationDefenseRooms as $room)
                    @php($roomUsage = (int) $room->upcoming_schedules_count + (int) $room->upcoming_sessions_count)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200/90 bg-white p-3.5 shadow-2xs transition-all hover:border-slate-300">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-black text-[#0e5c3a] border border-emerald-200/70">{{ $room->code }}</span>
                                <p class="text-xs font-bold text-slate-800 truncate">{{ $room->name }}</p>
                                @if ($room->is_active)
                                    @if ($roomUsage > 0)
                                        <span class="rounded-full bg-amber-50 border border-amber-200/70 px-2 py-0.5 text-[9px] font-black uppercase text-amber-800">In use</span>
                                    @else
                                        <span class="rounded-full bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 text-[9px] font-black uppercase text-emerald-700">Active</span>
                                    @endif
                                @else
                                    <span class="rounded-full bg-slate-100 border border-slate-200 px-2 py-0.5 text-[9px] font-black uppercase text-slate-500">Disabled</span>
                                @endif
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">{{ $room->schedules_count }} total · {{ $roomUsage }} upcoming/active · {{ $room->location_notes ?: 'No location notes' }}</p>
                        </div>
                        <div>
                            @if ($room->is_active && $roomUsage > 0)
                                <button type="button" disabled title="Reschedule upcoming defenses first." class="cursor-not-allowed inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-100/90 px-2.5 py-1.5 text-[10px] font-bold text-slate-400 opacity-70">
                                    <i class="ph ph-lock text-xs"></i> Disable
                                </button>
                            @elseif ($room->is_active)
                                <button type="button" wire:click="setDefenseRoomActive({{ $room->id }}, false)" wire:confirm="Disable this unused room?" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-[10px] font-bold text-red-600 hover:bg-red-50 hover:border-red-300 transition-colors shadow-2xs">
                                    <i class="ph ph-prohibit text-xs"></i> Disable
                                </button>
                            @else
                                <button type="button" wire:click="setDefenseRoomActive({{ $room->id }}, true)" wire:confirm="Activate this room?" class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[10px] font-bold text-[#0e5c3a] hover:bg-[#0e5c3a] hover:text-white transition-all shadow-2xs">
                                    <i class="ph ph-check-circle text-xs"></i> Enable
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-[#0e5c3a]">
                        <i class="ph ph-file-text"></i>
                    </span>
                    <div>
                        <h2 class="font-heading text-lg font-black text-slate-900">Official Form Catalog</h2>
                        <p class="text-xs text-slate-500">Forms with unfinished records stay enabled. Completed history remains available after a form is disabled.</p>
                    </div>
                </div>
            </div>
            <div class="max-h-[38rem] divide-y divide-slate-100 overflow-y-auto">
                @forelse ($configurationFormDefinitions as $definition)
                    <div class="flex items-center justify-between gap-4 px-6 py-4 transition-colors hover:bg-slate-50/50">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-md bg-emerald-50 border border-emerald-200/70 px-2 py-0.5 text-[10px] font-black text-[#0e5c3a]">{{ $definition->code }}</span>
                                <p class="text-xs font-bold text-slate-800 truncate">{{ $definition->title }}</p>
                                @if ($definition->is_active)
                                    @if ($definition->active_instances_count > 0)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200/70 px-2 py-0.5 text-[9px] font-black uppercase text-amber-800">
                                            <i class="ph ph-lock-key text-[10px]"></i> In use
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 text-[9px] font-black uppercase text-emerald-700">
                                            Active
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 border border-slate-200 px-2 py-0.5 text-[9px] font-black uppercase text-slate-500">
                                        Disabled
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">{{ $definition->active_instances_count }} active · {{ $definition->instances_count }} total record(s) · {{ str($definition->ownership_scope)->headline() }}</p>
                        </div>
                        <div>
                            @if ($definition->is_active && $definition->active_instances_count > 0)
                                <button type="button" disabled title="Complete or cancel active records first." class="cursor-not-allowed inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-100/90 px-2.5 py-1.5 text-[10px] font-bold text-slate-400 opacity-70">
                                    <i class="ph ph-lock text-xs"></i> Disable
                                </button>
                            @elseif ($definition->is_active)
                                <button type="button" wire:click="setOfficialFormActive({{ $definition->id }}, false)" wire:confirm="Disable this form for new records?" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-[10px] font-bold text-red-600 hover:bg-red-50 hover:border-red-300 transition-colors shadow-2xs">
                                    <i class="ph ph-prohibit text-xs"></i> Disable
                                </button>
                            @else
                                <button type="button" wire:click="setOfficialFormActive({{ $definition->id }}, true)" wire:confirm="Enable this form?" class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[10px] font-bold text-[#0e5c3a] hover:bg-[#0e5c3a] hover:text-white transition-all shadow-2xs">
                                    <i class="ph ph-check-circle text-xs"></i> Enable
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-10 text-center text-sm text-slate-500">No official form definitions found. Run the catalog synchronization during deployment.</div>
                @endforelse
            </div>
        </div>
    </section>
</div>
