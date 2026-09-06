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
@endphp
<div x-show="activeOfficialForm === 'RES-038'" x-cloak>
    <x-student-official-form code="RES-Form-038" title="Endorsement of Student Researcher/s to Research Adviser" guidebook-page="122">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? now()->format('Y-m-d') }}"></label>
        <p>Dear <input value="{{ $adviserName }}" class="w-72 font-bold" placeholder="Name of Research Adviser" readonly>,</p>
        <p>This is to officially endorse the following student researchers as your Research Advisees:</p>
        <div class="grid gap-3 md:grid-cols-2">
            @for ($i = 1; $i <= 4; $i++)
                <label class="flex gap-2">
                    <span>{{ $i }}.</span>
                    <input value="{{ $members->get($i - 1)?->student?->name }}" class="flex-1 font-medium" readonly placeholder="Researcher {{ $i }}">
                </label>
            @endfor
        </div>
        <label class="block">whose research paper is entitled:<input value="{{ $currentResearchTitle }}" class="mt-1 w-full font-bold" readonly></label>
        <label class="flex flex-wrap items-center gap-2">Endorsed this <input type="number" min="1" max="31" name="payload[day]" value="{{ $payload['day'] ?? now()->format('j') }}" class="w-20"> day of <input type="text" name="payload[month_year]" value="{{ $payload['month_year'] ?? now()->format('F Y') }}" class="w-48"></label>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2">
            <div>
                <x-official-signature-field label="Dean / Program Head" />
                <p class="mt-1 text-xs font-bold text-slate-800">{{ $programCoordinatorName }}</p>
            </div>
            <div>
                <x-official-signature-field label="Research Adviser Acceptance" />
                <p class="mt-1 text-xs font-bold text-slate-800">{{ $adviserName }}</p>
            </div>
        </div>
    </x-student-official-form>
</div>
