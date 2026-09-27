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
    $adviserName = $group?->adviser?->name ?? 'Research Adviser';

    $programCoordinator = $resolver->programCoordinatorForGroup($group) ?? $resolver->programCoordinatorForClass($class);
    $programCoordinatorName = $programCoordinator?->name ?? 'Program Coordinator';
    $deanName = $resolver->dean()?->name ?? 'College Dean';
@endphp
<div x-show="activeOfficialForm === 'RES-027'" x-cloak>
    <x-student-official-form code="RES-Form-027" title="Invitation to Research Adviser" guidebook-page="102">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? now()->format('Y-m-d') }}"></label>
        <p>Dear <input type="text" value="{{ $adviserName }}" class="w-72 font-bold" placeholder="Name of Research Adviser" readonly>,</p>
        <p class="leading-7">May I invite you to be the <strong>RESEARCH ADVISER</strong> of the following
            <input type="text" name="payload[course]" value="{{ $programName }}" class="w-72 font-semibold" placeholder="course" readonly> student/s:</p>
        <fieldset>
            <legend class="mb-2 font-bold">Name/s:</legend>
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
            <p>As Research Adviser, please be guided by the following:</p>
            <ol class="list-decimal space-y-1 pl-5">
                <li>Guide the student-advisees in all stages of the approved research study and ensure proper research-writing protocol.</li>
                <li>Regularly monitor their progress through face-to-face or online consultation schedules.</li>
                <li>Endorse the research for final oral defense.</li>
                <li>Guide preparation of the research presentation for the final oral defense.</li>
                <li>Document the panel members' comments, suggestions, and recommendations during the defense.</li>
                <li>Guide the student-advisees in revising and finalizing their research based on the panel members' comments, suggestions, and recommendations.</li>
                <li>Ensure that the student-advisees submit two hardbound copies and one soft copy of the completed research to the College Research Facilitator on or before the deadline.</li>
            </ol>
        </div>
        <p>Thank you very much.</p>
        <div class="ml-auto w-full max-w-sm space-y-4 pt-5 text-center">
            <x-official-signature-field actor-type="program_coordinator" label="Program Coordinator (Name & Signature)" />
            <x-official-signature-field actor-type="dean" label="Noted: Dean (Name & Signature)" />
            <x-official-signature-field actor-type="adviser" label="Conforme: Research Adviser (Name & Signature)" />
        </div>
    </x-student-official-form>
</div>
