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
    $adviserName = $group?->adviser?->name ?? 'Research Adviser';
    $programCoordinator = $resolver->programCoordinatorForGroup($group) ?? $resolver->programCoordinatorForClass($class);
    $programCoordinatorName = $programCoordinator?->name ?? 'Program Coordinator';
    $dean = $resolver->dean();
    $deanName = $dean?->name ?? 'Dr. Lourdes Castillo';
@endphp
<div x-show="activeOfficialForm === 'RES-044'" x-cloak>
    <x-student-official-form code="RES-Form-044" title="Endorsement of Student Researcher for Data Gathering" guidebook-page="130">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? now()->format('Y-m-d') }}"></label>
        <div>
            <input value="{{ $deanName }}" class="w-72 font-bold" readonly>
            <br>
            <span>Dean, College of <input value="Engineering, Architecture, and Computing" class="w-80" readonly></span>
        </div>
        <p class="mt-2">Dear <input name="payload[salutation]" value="{{ $payload['salutation'] ?? 'Dr. Castillo' }}" class="w-56" placeholder="Sir / Madam">:</p>
        <div class="grid gap-3 md:grid-cols-2">
            @for ($i = 1; $i <= 4; $i++)
                <label class="flex gap-2">
                    <span>{{ $i }}.</span>
                    <input value="{{ $members->get($i - 1)?->student?->name }}" class="flex-1 font-medium" readonly placeholder="Researcher {{ $i }}">
                </label>
            @endfor
        </div>
        <label class="block font-bold">whose research title is:<input value="{{ $currentResearchTitle }}" class="mt-1 w-full font-bold" readonly></label>
        <p>has/have fully accomplished the following:</p>
        <ol class="list-[lower-alpha] space-y-2 pl-5 text-xs leading-5">
            <li>Full revision of the research proposal paper, duly approved by the members of the panel.</li>
            <li>Acquired a Validation Rating of not less than 4.00 (Very Good), with 1.00 as the lowest and 5.00 as the highest rating.</li>
        </ol>
        <p>Therefore, the above-mentioned student researcher/s is/are highly endorsed to proceed to the data-gathering phase.</p>
        <div>
            <strong>Endorsed by Panel of Examiners:</strong>
            <div class="mt-4 grid gap-6 text-center md:grid-cols-3">
                @for ($i = 1; $i <= 3; $i++)
                    <x-official-signature-field :label="'Panelist '.$i" />
                @endfor
            </div>
        </div>
        <div class="mx-auto max-w-sm pt-6 text-center">
            <x-official-signature-field label="Research Adviser" />
            <p class="mt-1 text-xs font-bold text-slate-800">{{ $adviserName }}</p>
        </div>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2">
            <div>
                <x-official-signature-field label="Program Coordinator" />
                <p class="mt-1 text-xs font-bold text-slate-800">{{ $programCoordinatorName }}</p>
            </div>
            <div>
                <x-official-signature-field label="College Dean" />
                <p class="mt-1 text-xs font-bold text-slate-800">{{ $deanName }}</p>
            </div>
        </div>
    </x-student-official-form>
</div>
