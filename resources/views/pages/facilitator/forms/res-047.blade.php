@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
@endphp
<div x-show="activeOfficialForm === 'RES-047'" x-cloak>
    <x-student-official-form code="RES-Form-047" title="Endorsement for Reproduction of the Research Paper" guidebook-page="133">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? '' }}"></label><p><input value="{{ $officialFormInstance?->group?->researchClass?->officialFormActorAssignments?->firstWhere('actor_type', 'dean')?->user?->name }}" class="w-72" readonly><br>College of <input value="Engineering, Architecture, and Computing" class="w-80" readonly></p><p>Dear <input name="payload[salutation]" value="{{ $payload['salutation'] ?? '' }}" class="w-56">,</p>
        <p>After a thorough examination of the revised research paper entitled:</p><input value="{{ $officialFormInstance?->group?->researchGroup?->title }}" class="w-full text-center" aria-label="Research title" readonly><p>of the following students:</p><div class="grid gap-3 md:grid-cols-2">@for ($i=1; $i<=4; $i++)<label class="flex gap-2"><span>{{ $i }}.</span><input value="{{ $officialFormInstance?->group?->members?->values()?->get($i - 1)?->student?->name }}" class="flex-1" readonly></label>@endfor</div>
        <p class="leading-7">I further attest that the research paper has incorporated the corrections and suggestions of the panel of examiners and was duly edited by proficient language and technical editors. Hence, I highly endorse the attached hard copy for reproduction and hard binding.</p><p>For your approval.</p>
        <div class="grid gap-8 pt-10 text-center md:grid-cols-2"><div><x-official-signature-field name-field="res_047_adviser_printed_name" label="Endorsed: Research Adviser" /><label class="mt-2 block">Date Endorsed:<input type="text" value="Recorded by the endorsement workflow" class="w-full" readonly></label></div><x-official-signature-field name-field="res_047_college_dean_name" label="Approved: College Dean" /></div>
    </x-student-official-form>
</div>
