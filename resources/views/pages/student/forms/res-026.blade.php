@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
@endphp
<div x-show="activeOfficialForm === 'RES-026'" x-cloak><x-student-official-form code="RES-Form-026" title="Research Title Approval" guidebook-page="101">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div class="border border-[#173c30] p-3 text-xs leading-5">
                <strong>Requirements for Research Title Proposal:</strong><br>
                • Three (3) proposed research titles<br>
                • Each title must contain the following: Background/Rationale (at least 150 words); Objectives/Statement of the Problem; and Brief Methodology Plan (Research Design, Locale, Respondents, and Sample Size).
            </div>
            <label class="flex items-center gap-2 whitespace-nowrap">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? '' }}"></label>
        </div>
        <fieldset><legend class="mb-2 font-bold">Name of Student/s:</legend><div class="grid grid-cols-1 gap-3 md:grid-cols-2">@for ($i = 1; $i <= 4; $i++)<label class="flex gap-2"><span>{{ $i }}.</span><input type="text" value="{{ $officialFormInstance?->group?->members?->values()?->get($i - 1)?->student?->name }}" class="min-w-0 flex-1" readonly></label>@endfor</div></fieldset>
        <fieldset><legend class="mb-2 font-bold">Proposed Research Topics:</legend><div class="space-y-3">@for ($i = 1; $i <= 3; $i++)<label class="flex items-start gap-2"><span>{{ $i }}.</span><textarea class="min-h-14 w-full" name="payload[topics][]">{{ $payload['topics'][$i - 1] ?? '' }}</textarea></label>@endfor</div></fieldset>
        <label class="flex items-center gap-2 font-bold">Approved Research Title No. <input class="w-32" readonly></label>
        <label class="block font-bold">Remarks:<textarea class="mt-2 min-h-24 w-full" readonly></textarea></label>
        <div class="grid grid-cols-2 gap-x-6 gap-y-4 pt-3 text-center"><div class="border-b border-[#173c30] pb-1 font-bold">Name</div><div class="border-b border-[#173c30] pb-1 font-bold">Signature</div><div class="col-span-2 text-left font-bold">Panel of Examiners:</div>@foreach (['Chairman','Member 1','Member 2'] as $role)<label class="flex items-center gap-2 text-left italic"><span class="shrink-0">{{ $role }}:</span><input type="text" class="min-w-0 flex-1" readonly></label><x-official-signature-field :label="$role.' signature'" />@endforeach</div>
        <div class="grid grid-cols-2 gap-8 pt-8 text-center"><x-official-signature-field actor-type="program_coordinator" name-field="program_coordinator_name" label="Program Coordinator" /><x-official-signature-field actor-type="dean" name-field="college_dean_name" label="College Dean" /></div>
    </x-student-official-form></div>
