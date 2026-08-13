@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
@endphp
<div x-show="activeOfficialForm === 'RES-038'" x-cloak>
    <x-student-official-form code="RES-Form-038" title="Endorsement of Student Researcher/s to Research Adviser" guidebook-page="122">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? '' }}"></label>
        <p>Dear <input value="{{ $officialFormInstance?->group?->adviser?->name }}" class="w-72" placeholder="Name of Research Adviser" readonly>,</p>
        <p>This is to officially endorse the following student researchers as your Research Advisees:</p>
        <div class="grid gap-3 md:grid-cols-2">@for ($i=1; $i<=4; $i++)<label class="flex gap-2"><span>{{ $i }}.</span><input value="{{ $officialFormInstance?->group?->members?->values()?->get($i - 1)?->student?->name }}" class="flex-1" readonly></label>@endfor</div>
        <label class="block">whose research paper is entitled:<input value="{{ $officialFormInstance?->group?->title }}" class="mt-1 w-full" readonly></label>
        <label class="flex flex-wrap items-center gap-2">Endorsed this <input type="number" min="1" max="31" name="payload[day]" value="{{ $payload['day'] ?? '' }}" class="w-20"> day of <input type="text" name="payload[month_year]" value="{{ $payload['month_year'] ?? '' }}" class="w-48"></label>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2"><x-official-signature-field label="Dean / Program Head" /><x-official-signature-field label="Research Adviser Acceptance" /></div>
    </x-student-official-form>
</div>
