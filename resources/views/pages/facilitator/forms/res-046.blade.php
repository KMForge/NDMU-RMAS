@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
@endphp
<div x-show="activeOfficialForm === 'RES-046'" x-cloak>
    <x-student-official-form code="RES-Form-046" title="Certificate of Technical Editing" guidebook-page="132">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? '' }}"></label><p><input value="{{ $officialFormInstance?->group?->researchClass?->officialFormActorAssignments?->firstWhere('actor_type', 'dean')?->user?->name }}" class="w-72" readonly><br>College of <input value="Engineering, Architecture, and Computing" class="w-80" readonly></p><p>Dear Sir/Madam,</p>
        <p>This is to certify that the research paper entitled:</p><input value="{{ $officialFormInstance?->group?->researchGroup?->title }}" class="w-full text-center" aria-label="Research title" readonly><p>of the student researcher/s whose name/s appear below:</p><div class="grid gap-3 md:grid-cols-2">@for ($i=1; $i<=4; $i++)<label class="flex gap-2"><span>{{ $i }}.</span><input value="{{ $officialFormInstance?->group?->members?->values()?->get($i - 1)?->student?->name }}" class="flex-1" readonly></label>@endfor</div>
        <p class="leading-7">has been edited technically. The content and the authors' intention were not altered in any way during the editing process. The paper follows the prescribed research format stipulated in the NDMU Undergraduate Research Manual.</p>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2"><x-official-signature-field name-field="res_046_editor_printed_name" label="Technical Editor" /><label>Date Edited:<input type="date" name="payload[date]" value="{{ $payload['date'] ?? '' }}" class="w-full"></label></div>
    </x-student-official-form>
</div>
