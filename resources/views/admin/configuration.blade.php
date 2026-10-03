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
            <div class="mb-5 flex items-center gap-3"><span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-[#0e5c3a]"><i class="ph ph-buildings"></i></span><div><h2 class="font-heading text-lg font-black text-slate-900">Add Department</h2><p class="text-xs text-slate-500">Create a department under {{ $configurationCollege?->name ?? 'the configured college' }}.</p></div></div>
            <div class="grid gap-4 sm:grid-cols-[.45fr_1fr]">
                <div><label class="mb-2 block text-[10px] font-black uppercase tracking-wider text-slate-500">Code</label><input wire:model="newDepartmentCode" maxlength="30" placeholder="CSD" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm uppercase focus:border-[#0e5c3a] focus:outline-none"></div>
                <div><label class="mb-2 block text-[10px] font-black uppercase tracking-wider text-slate-500">Name</label><input wire:model="newDepartmentName" maxlength="255" placeholder="Computer Studies Department" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:outline-none"></div>
            </div>
            @error('newDepartmentCode') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('newDepartmentName') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            <button class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-xs font-black text-white disabled:opacity-60" wire:loading.attr="disabled" wire:target="createDepartment"><i class="ph ph-plus"></i>Add Department</button>
        </form>

        <form wire:submit="createProgram" class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-3"><span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-xl text-blue-700"><i class="ph ph-graduation-cap"></i></span><div><h2 class="font-heading text-lg font-black text-slate-900">Add Program</h2><p class="text-xs text-slate-500">Programs are assigned to one active department.</p></div></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <select wire:model="newProgramDepartmentId" class="rounded-xl border border-slate-200 px-4 py-3 text-sm"><option value="">Select department</option>@foreach (($configurationCollege?->departments ?? collect())->where('is_active', true) as $department)<option value="{{ $department->id }}">{{ $department->code }} — {{ $department->name }}</option>@endforeach</select>
                <input wire:model="newProgramCode" maxlength="30" placeholder="BSIT" class="rounded-xl border border-slate-200 px-4 py-3 text-sm uppercase">
                <input wire:model="newProgramName" maxlength="255" placeholder="Bachelor of Science in Information Technology" class="rounded-xl border border-slate-200 px-4 py-3 text-sm sm:col-span-2">
                <input wire:model="newProgramDegreeLevel" maxlength="50" placeholder="Bachelor" class="rounded-xl border border-slate-200 px-4 py-3 text-sm">
            </div>
            @error('newProgramDepartmentId') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('newProgramCode') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('newProgramName') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            <button class="mt-5 inline-flex items-center gap-2 rounded-xl bg-blue-700 px-5 py-2.5 text-xs font-black text-white disabled:opacity-60" wire:loading.attr="disabled" wire:target="createProgram"><i class="ph ph-plus"></i>Add Program</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5"><h2 class="font-heading text-lg font-black text-slate-900">Academic Structure</h2><p class="mt-1 text-xs text-slate-500">Institutional records are deactivated, never deleted. Active assignments must be moved first.</p></div>
        <div class="divide-y divide-slate-100">
            @forelse ($configurationCollege?->departments ?? collect() as $department)
                @php($departmentUsage = (int) $department->active_programs_count + (int) $department->active_faculty_count)
                <div class="p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2"><span class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-black text-slate-700">{{ $department->code }}</span><h3 class="font-bold text-slate-900">{{ $department->name }}</h3>@if ($departmentUsage > 0)<span class="rounded-full bg-amber-100 px-2 py-1 text-[9px] font-black uppercase text-amber-800"><i class="ph ph-lock-key mr-1"></i>In use</span>@endif</div>
                            <p class="mt-1 text-xs text-slate-500">{{ $department->programs->count() }} program(s) · {{ $department->active_faculty_count }} active faculty</p>
                        </div>
                        @if ($department->is_active && $departmentUsage > 0)
                            <button type="button" disabled title="Reassign active programs and faculty first." class="cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-[10px] font-black text-slate-400">In use</button>
                        @else
                            <button type="button" wire:click="setDepartmentActive({{ $department->id }}, {{ $department->is_active ? 'false' : 'true' }})" wire:confirm="{{ $department->is_active ? 'Deactivate this unused department?' : 'Activate this department?' }}" class="rounded-xl border px-3 py-2 text-[10px] font-black {{ $department->is_active ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}">{{ $department->is_active ? 'Deactivate' : 'Activate' }}</button>
                        @endif
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @forelse ($department->programs as $program)
                            @php($programUsage = (int) $program->active_students_count + (int) $program->research_groups_count)
                            <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                                <div><div class="flex flex-wrap items-center gap-2"><p class="text-xs font-black text-slate-800">{{ $program->code }}</p>@if ($programUsage > 0)<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[8px] font-black uppercase text-amber-800">In use</span>@endif</div><p class="mt-1 text-[11px] text-slate-500">{{ $program->name }}</p><p class="mt-1 text-[9px] font-bold uppercase text-slate-400">{{ $program->active_students_count }} active student(s) · {{ $program->research_groups_count }} group(s)</p></div>
                                @if ($program->is_active && $programUsage > 0)
                                    <button type="button" disabled title="Reassign active students and research groups first." class="cursor-not-allowed rounded-lg bg-slate-200 px-2.5 py-1.5 text-[9px] font-black text-slate-500">In use</button>
                                @else
                                    <button type="button" wire:click="setProgramActive({{ $program->id }}, {{ $program->is_active ? 'false' : 'true' }})" wire:confirm="{{ $program->is_active ? 'Disable this unused program?' : 'Enable this program?' }}" class="rounded-lg px-2.5 py-1.5 text-[9px] font-black {{ $program->is_active ? 'bg-red-50 text-red-600' : 'bg-emerald-100 text-emerald-700' }}">{{ $program->is_active ? 'Disable' : 'Enable' }}</button>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">No programs configured.</p>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-sm text-slate-500">No departments are configured for this college.</div>
            @endforelse
        </div>
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-[.8fr_1.2fr]">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-heading text-lg font-black text-slate-900">Defense Rooms</h2><p class="mt-1 text-xs text-slate-500">Rooms with upcoming schedules or active defense sessions cannot be disabled.</p>
            <form wire:submit="createDefenseRoom" class="mt-5 grid gap-3"><input wire:model="newDefenseRoomCode" maxlength="50" placeholder="CEAC-101" class="rounded-xl border border-slate-200 px-4 py-3 text-sm uppercase"><input wire:model="newDefenseRoomName" maxlength="255" placeholder="CEAC Conference Room" class="rounded-xl border border-slate-200 px-4 py-3 text-sm"><textarea wire:model="newDefenseRoomLocation" maxlength="1000" rows="2" placeholder="Location notes" class="rounded-xl border border-slate-200 px-4 py-3 text-sm"></textarea><button class="rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-xs font-black text-white">Add Room</button></form>
            <div class="mt-5 space-y-2">
                @foreach ($configurationDefenseRooms as $room)
                    @php($roomUsage = (int) $room->upcoming_schedules_count + (int) $room->upcoming_sessions_count)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 p-3">
                        <div><div class="flex flex-wrap items-center gap-2"><p class="text-xs font-black text-slate-800">{{ $room->code }} · {{ $room->name }}</p>@if ($roomUsage > 0)<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[8px] font-black uppercase text-amber-800">In use</span>@endif</div><p class="mt-1 text-[10px] text-slate-400">{{ $room->schedules_count }} total · {{ $roomUsage }} upcoming/active · {{ $room->location_notes ?: 'No location notes' }}</p></div>
                        @if ($room->is_active && $roomUsage > 0)
                            <button type="button" disabled title="Reschedule upcoming defenses first." class="cursor-not-allowed rounded-lg bg-slate-200 px-2.5 py-1.5 text-[9px] font-black text-slate-500">In use</button>
                        @else
                            <button type="button" wire:click="setDefenseRoomActive({{ $room->id }}, {{ $room->is_active ? 'false' : 'true' }})" wire:confirm="{{ $room->is_active ? 'Disable this unused room?' : 'Activate this room?' }}" class="rounded-lg px-2.5 py-1.5 text-[9px] font-black {{ $room->is_active ? 'bg-red-50 text-red-600' : 'bg-emerald-100 text-emerald-700' }}">{{ $room->is_active ? 'Disable' : 'Enable' }}</button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5"><h2 class="font-heading text-lg font-black text-slate-900">Official Form Catalog</h2><p class="mt-1 text-xs text-slate-500">Forms with unfinished records stay enabled. Completed history remains available after a form is disabled.</p></div>
            <div class="max-h-[38rem] divide-y divide-slate-100 overflow-y-auto">
                @forelse ($configurationFormDefinitions as $definition)
                    <div class="flex items-center justify-between gap-4 px-6 py-4">
                        <div><div class="flex flex-wrap items-center gap-2"><span class="rounded-md bg-emerald-50 px-2 py-1 text-[9px] font-black text-emerald-800">{{ $definition->code }}</span><p class="text-xs font-bold text-slate-800">{{ $definition->title }}</p>@if ($definition->active_instances_count > 0)<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[8px] font-black uppercase text-amber-800"><i class="ph ph-lock-key mr-1"></i>In use</span>@endif</div><p class="mt-1 text-[10px] text-slate-400">{{ $definition->active_instances_count }} active · {{ $definition->instances_count }} total record(s) · {{ str($definition->ownership_scope)->headline() }}</p></div>
                        @if ($definition->is_active && $definition->active_instances_count > 0)
                            <button type="button" disabled title="Complete or cancel active records first." class="cursor-not-allowed rounded-lg bg-slate-200 px-2.5 py-1.5 text-[9px] font-black text-slate-500">In use</button>
                        @else
                            <button type="button" wire:click="setOfficialFormActive({{ $definition->id }}, {{ $definition->is_active ? 'false' : 'true' }})" wire:confirm="{{ $definition->is_active ? 'Disable this form for new records?' : 'Enable this form?' }}" class="rounded-lg px-2.5 py-1.5 text-[9px] font-black {{ $definition->is_active ? 'bg-red-50 text-red-600' : 'bg-emerald-100 text-emerald-700' }}">{{ $definition->is_active ? 'Disable' : 'Enable' }}</button>
                        @endif
                    </div>
                @empty
                    <div class="p-10 text-center text-sm text-slate-500">No official form definitions found. Run the catalog synchronization during deployment.</div>
                @endforelse
            </div>
        </div>
    </section>
</div>
