@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
@endphp
<div x-show="activeOfficialForm === 'RES-030'" x-cloak><x-student-official-form code="RES-Form-030" title="Request for Change of Research Adviser / Panelist / Language Editor" guidebook-page="105">
    <div class="grid gap-4 md:grid-cols-2">
        <label>Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? '' }}" class="w-full"></label>
        <label>Degree Program: <input name="payload[degree_program]" value="{{ $payload['degree_program'] ?? '' }}" class="w-full"></label>
    </div>
    <label class="block">Name of Student/s:
        <textarea class="mt-1 min-h-16 w-full" readonly>{{ $officialFormInstance?->group?->members?->pluck('student.name')->filter()->join(', ') }}</textarea>
    </label>
    <label class="block">Research Title:<input name="payload[research_title]" value="{{ $payload['research_title'] ?? ($officialFormInstance?->group?->title ?? '') }}" class="w-full"></label>
    <fieldset><legend class="font-bold">Specific Request (Please check):</legend>
        <div class="mt-2 grid gap-3 md:grid-cols-2">
            @foreach (['Change of Research Adviser', 'Change of Panelist', 'Change of Language Editor'] as $index => $role)
                <label><input type="checkbox" name="payload[personnel_type][]" value="{{ $role }}" @checked(in_array($role, $payload['personnel_type'] ?? [], true))> {{ $role }}</label>
                <label>NAME: <input name="payload[current_names][]" value="{{ $payload['current_names'][$index] ?? '' }}" class="w-full"></label>
            @endforeach
        </div>
    </fieldset>
    <label class="block font-bold">Proposed Adviser / Panelist / Language Editor:<input name="payload[proposed_replacement]" value="{{ $payload['proposed_replacement'] ?? '' }}" class="w-full"></label>
    <label class="block font-bold">Reason/s for replacement:<textarea name="payload[reasons]" class="mt-2 min-h-32 w-full">{{ $payload['reasons'] ?? '' }}</textarea></label>
    <div class="pt-6"><p class="mb-3 font-bold">Requested by:</p><div class="grid gap-4 md:grid-cols-2">@for ($i=1;$i<=4;$i++)<x-official-signature-field :label="'Student '.$i" />@endfor</div></div>
    <div class="grid grid-cols-2 gap-8 pt-10 text-center"><x-official-signature-field label="Approved by: College Dean" /><x-official-signature-field label="Noted by: Program Coordinator" /></div>
</x-student-official-form></div>
