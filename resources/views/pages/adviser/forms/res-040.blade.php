@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $group = $officialFormInstance?->group;
    $class = $officialFormInstance?->researchClass ?? $group?->researchClass;
    $currentResearchTitle = $payload['research_title']
        ?? $payload['title']
        ?? $group?->researchGroup?->currentProject?->title
        ?? $group?->titlePresentation?->approved_title
        ?? '';
    $members = $group?->members?->values() ?? collect();
    $adviserName = $group?->adviser?->name ?? '';
@endphp
<div x-show="activeOfficialForm === 'RES-040'" x-cloak>
    <x-student-official-form code="RES-Form-040" title="Endorsement of the Revised Research Proposal to the Research Instructor" guidebook-page="125">
        <h3 class="text-center font-bold uppercase">To the Research Instructor</h3>
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? now()->format('Y-m-d') }}"></label>
        <p>Dear Sir/Madam:</p>
        <p class="leading-7">This is to officially endorse the hard copy of the Revised Research Proposal Paper entitled:</p>
        <input value="{{ $currentResearchTitle }}" class="w-full text-center font-bold" aria-label="Research title" readonly>
        <p>of the following student researchers:</p>
        <div class="grid gap-3 md:grid-cols-2">
            @for ($i = 1; $i <= 4; $i++)
                <label class="flex gap-2">
                    <span>{{ $i }}.</span>
                    <input value="{{ $members->get($i - 1)?->student?->name }}" class="flex-1" readonly placeholder="Researcher {{ $i }}">
                </label>
            @endfor
        </div>
        <p>approved by the panel of reviewers.</p>
        <p>Thank you very much.</p>
        <p>Sincerely yours,</p>
        <div class="ml-auto max-w-sm pt-8 text-center">
            <x-official-signature-field name-field="res_040_adviser_printed_name" label="Research Adviser" />
            <p class="mt-1 text-xs font-bold text-slate-800">{{ $adviserName }}</p>
        </div>
    </x-student-official-form>
</div>
