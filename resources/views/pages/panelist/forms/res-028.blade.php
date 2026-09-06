@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $group = $officialFormInstance?->group;
    $class = $officialFormInstance?->researchClass ?? $group?->researchClass;
    $resolver = app(\App\Modules\OfficialForms\Services\InstitutionalActorResolver::class);

    $currentResearchTitle = $payload['research_title']
        ?? $payload['title']
        ?? $group?->researchGroup?->currentProject?->title
        ?? $group?->titlePresentation?->approved_title
        ?? '';

    $members = $group?->members?->values() ?? collect();
    $program = $members->first()?->student?->studentProfile?->program;
    $programName = $payload['course'] ?? $program?->name ?? $members->first()?->student?->program ?? ($class?->name ?? 'Information Technology');

    $assignedPanelist = $officialFormInstance?->actorAssignments?->firstWhere('actor_type', 'panelist')?->user
        ?? $officialFormInstance?->currentVersion?->creator;

    $programCoordinator = $resolver->programCoordinatorForGroup($group) ?? $resolver->programCoordinatorForClass($class);
    $programCoordinatorName = $programCoordinator?->name ?? 'Program Coordinator';
    $dean = $resolver->dean();
    $deanName = $dean?->name ?? 'Dr. Lourdes Castillo';
@endphp
<div x-show="activeOfficialForm === 'RES-028'" x-cloak>
    <x-student-official-form code="RES-Form-028" title="Invitation to Research Examination Panelist" guidebook-page="103">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? now()->format('Y-m-d') }}"></label>
        <p>To: <input type="text" value="{{ $assignedPanelist?->name }}" class="w-72 font-bold" placeholder="Name of Research Panelist" readonly></p>
        <p class="leading-7">May I invite you to serve as
            <label><input type="radio" name="payload[panel_role]" value="chairman" @checked(($payload['panel_role'] ?? 'chairman') === 'chairman')> Chairman</label> /
            <label><input type="radio" name="payload[panel_role]" value="member" @checked(($payload['panel_role'] ?? '') === 'member')> Member</label>
            of the Panel of Examiners for the
            <input type="text" name="payload[defense]" value="{{ $payload['defense'] ?? 'Research Proposal Defense' }}" class="w-56 font-semibold" placeholder="type of defense">
            of the <input type="text" name="payload[course]" value="{{ $programName }}" class="w-64 font-semibold" placeholder="course" readonly> students.
        </p>
        <div class="grid gap-3 md:grid-cols-3">
            <label>Date:<input type="date" name="payload[defense_date]" value="{{ $payload['defense_date'] ?? now()->format('Y-m-d') }}" class="w-full"></label>
            <label>Time:<input type="time" name="payload[time]" value="{{ $payload['time'] ?? '10:00' }}" class="w-full"></label>
            <label>Venue:<input type="text" name="payload[venue]" value="{{ $payload['venue'] ?? 'CEAC AVR / Conference Room' }}" class="w-full"></label>
        </div>
        <fieldset>
            <legend class="mb-2 font-bold">Name of Students:</legend>
            <div class="grid gap-3 md:grid-cols-2">
                @for ($i = 1; $i <= 4; $i++)
                    <label class="flex gap-2">
                        <span>{{ $i }}.</span>
                        <input type="text" value="{{ $members->get($i - 1)?->student?->name }}" class="flex-1 font-medium" readonly placeholder="Researcher {{ $i }}">
                    </label>
                @endfor
            </div>
        </fieldset>
        <label class="block font-bold">Research Title:<input type="text" name="payload[research_title]" value="{{ $currentResearchTitle }}" class="mt-1 w-full font-bold" readonly></label>
        <div class="space-y-2 text-xs leading-5">
            <p>As Research Panelist, please be guided by the following:</p>
            <ol class="list-decimal space-y-1 pl-5">
                <li>Review the research manuscript before the scheduled defense.</li>
                <li>Coordinate any necessary rescheduling with the program coordinator, adviser, and other panel members.</li>
                <li>Maintain the assigned panel composition through the required oral-defense stages whenever possible.</li>
                <li>Discuss substantial recommendations with the adviser and other panelists for approval.</li>
            </ol>
        </div>
        <p>Thank you very much.</p>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-3">
            <div>
                <x-official-signature-field label="Program Coordinator" />
                <p class="mt-1 text-xs font-bold text-slate-800">{{ $programCoordinatorName }}</p>
            </div>
            <div>
                <x-official-signature-field label="Research Panelist Conforme" />
                <p class="mt-1 text-xs font-bold text-slate-800">{{ $assignedPanelist?->name }}</p>
            </div>
            <div>
                <x-official-signature-field label="College Dean" />
                <p class="mt-1 text-xs font-bold text-slate-800">{{ $deanName }}</p>
            </div>
        </div>
    </x-student-official-form>
</div>
