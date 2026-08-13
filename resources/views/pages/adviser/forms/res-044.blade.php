@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
@endphp
<div x-show="activeOfficialForm === 'RES-044'" x-cloak>
    <x-student-official-form code="RES-Form-044" title="Endorsement of Student Researcher for Data Gathering" guidebook-page="130">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? '' }}"></label>
        <p><input placeholder="College Dean" class="w-72" readonly><br>College of <input value="Engineering, Architecture, and Computing" class="w-80" readonly></p>
        <p>Dear <input name="payload[salutation]" value="{{ $payload['salutation'] ?? '' }}" class="w-56" placeholder="Sir / Madam">,</p>
        <div class="grid gap-3 md:grid-cols-2">@for ($i=1; $i<=4; $i++)<label class="flex gap-2"><span>{{ $i }}.</span><input value="{{ $officialFormInstance?->group?->members?->values()?->get($i - 1)?->student?->name }}" class="flex-1" readonly></label>@endfor</div>
        <label class="block">whose research title is:<input value="{{ $officialFormInstance?->group?->title }}" class="mt-1 w-full" readonly></label>
        <p>has/have fully accomplished the following:</p>
        <ol class="list-[lower-alpha] space-y-2 pl-5 text-xs leading-5"><li>Full revision of the research proposal paper, duly approved by the members of the panel.</li><li>Acquired a Validation Rating of not less than 4.00 (Very Good), with 1.00 as the lowest and 5.00 as the highest rating.</li></ol>
        <p>Therefore, the above-mentioned student researcher/s is/are highly endorsed to proceed to the data-gathering phase.</p>
        <div><strong>Endorsed by:</strong><div class="mt-5 grid gap-6 text-center md:grid-cols-3">@for ($i=1; $i<=3; $i++)<x-official-signature-field :label="'Panelist '.$i" />@endfor</div></div>
        <x-official-signature-field label="Research Adviser" class="mx-auto max-w-sm pt-8" />
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2"><x-official-signature-field label="Program Coordinator" /><x-official-signature-field label="College Dean" /></div>
    </x-student-official-form>
</div>
