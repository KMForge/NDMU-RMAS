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
<div x-show="activeOfficialForm === 'RES-033'" x-cloak>
    <x-student-official-form code="RES-Form-033" title="Endorsement for Research Proposal / Final Oral Defense" guidebook-page="113">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? now()->format('Y-m-d') }}"></label>
        <p>Dear <input type="text" value="{{ $programCoordinatorName }}" class="w-72 font-bold" readonly>,</p>
        <p>This is to endorse the research paper of the following student researchers:</p>
        <div class="grid gap-3 md:grid-cols-2">
            @for ($i = 1; $i <= 4; $i++)
                <label class="flex gap-2">
                    <span>{{ $i }}.</span>
                    <input type="text" value="{{ $members->get($i - 1)?->student?->name }}" class="flex-1 font-medium" readonly placeholder="Researcher {{ $i }}">
                </label>
            @endfor
        </div>
        <label class="block font-bold">Research Title:<input type="text" value="{{ $currentResearchTitle }}" class="mt-1 w-full font-bold" readonly></label>
        <fieldset class="space-y-3">
            <legend class="mb-2 font-bold">For Research (please check):</legend>
            <label class="flex flex-wrap items-center gap-2">
                <input type="radio" name="payload[defense_type]" value="proposal" @checked(($payload['defense_type'] ?? 'proposal') === 'proposal')> Proposal Defense
            </label>
            <label class="flex flex-wrap items-center gap-2">
                <input type="radio" name="payload[defense_type]" value="final" @checked(($payload['defense_type'] ?? '') === 'final')> Final Defense
            </label>
            <div class="mt-2 grid gap-3 md:grid-cols-2">
                <label class="block">Defense Date: <input type="date" name="payload[defense_date]" value="{{ $payload['defense_date'] ?? now()->format('Y-m-d') }}" class="w-full"></label>
                <label class="block">Defense Time: <input type="time" name="payload[time]" value="{{ $payload['time'] ?? '09:00' }}" class="w-full"></label>
            </div>
        </fieldset>
        <p class="text-xs leading-5">Attached are four (4) copies of the research paper: three copies for the panelists and one for the Research Adviser.</p>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2">
            <div>
                <x-official-signature-field label="Instructor / Research Adviser" />
                <p class="mt-1 text-xs font-bold text-slate-800">{{ $adviserName }}</p>
            </div>
            <div>
                <input type="text" value="{{ $programCoordinatorName }}" class="w-full text-center font-bold" readonly>
                <span class="block text-xs text-slate-500">Received by: Program Coordinator</span>
            </div>
        </div>
    </x-student-official-form>
</div>
