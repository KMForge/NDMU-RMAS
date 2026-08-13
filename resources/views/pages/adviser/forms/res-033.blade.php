@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
@endphp
<div x-show="activeOfficialForm === 'RES-033'" x-cloak>
    <x-student-official-form code="RES-Form-033" title="Endorsement for Research Proposal / Final Oral Defense" guidebook-page="113">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? '' }}"></label>
        <p>Dear <input type="text" placeholder="Name of Program Coordinator" class="w-72" readonly>,</p>
        <p>This is to endorse the research paper of the following student researchers:</p>
        <div class="grid gap-3 md:grid-cols-2">
            @for ($i = 1; $i <= 4; $i++)
                <label class="flex gap-2"><span>{{ $i }}.</span><input type="text" value="{{ $officialFormInstance?->group?->members?->values()?->get($i - 1)?->student?->name }}" class="flex-1" readonly></label>
            @endfor
        </div>
        <label class="block font-bold">Research Title:<input type="text" value="{{ $officialFormInstance?->group?->title }}" class="mt-1 w-full font-normal" readonly></label>
        <fieldset class="space-y-3">
            <legend class="mb-2 font-bold">For Research (please check):</legend>
            <label class="flex flex-wrap items-center gap-2">
                <input type="radio" name="payload[defense_type]" value="proposal" @checked(($payload['defense_type'] ?? '') === 'proposal')> Proposal Defense
            </label>
            <label class="flex flex-wrap items-center gap-2">
                <input type="radio" name="payload[defense_type]" value="final" @checked(($payload['defense_type'] ?? '') === 'final')> Final Defense
            </label>
            <div class="mt-2 grid gap-3 md:grid-cols-2">
                <label class="block">Defense Date: <input type="date" name="payload[defense_date]" value="{{ $payload['defense_date'] ?? '' }}" class="w-full"></label>
                <label class="block">Defense Time: <input type="time" name="payload[time]" value="{{ $payload['time'] ?? '' }}" class="w-full"></label>
            </div>
        </fieldset>
        <p class="text-xs leading-5">Attached are four (4) copies of the research paper: three copies for the panelists and one for the Research Adviser.</p>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2">
            <x-official-signature-field label="Instructor / Research Adviser" />
            <label><input type="text" placeholder="Received by" class="w-full text-center" readonly><span class="block">Received by</span></label>
        </div>
    </x-student-official-form>
</div>
