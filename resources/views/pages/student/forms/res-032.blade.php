@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
@endphp
<div x-show="activeOfficialForm === 'RES-032'" x-cloak><x-student-official-form code="RES-Form-032" title="Consultation Sheet (With Other Consultants)" guidebook-page="112">
    <div class="grid gap-4 md:grid-cols-2">
        <label>Name of Student/s:
            <textarea class="mt-1 min-h-20 w-full" readonly>{{ $officialFormInstance?->group?->members?->pluck('student.name')->filter()->join(', ') }}</textarea>
        </label>
        <div class="space-y-4">
            <label class="block">Degree Program:<input name="payload[degree_program]" value="{{ $payload['degree_program'] ?? '' }}" class="w-full"></label>
            <label class="block">Date:<input type="date" name="payload[date]" value="{{ $payload['date'] ?? '' }}" class="w-full"></label>
        </div>
    </div>
    <fieldset><legend class="font-bold">Type of Consultant (Please check):</legend>
        <div class="mt-2 flex flex-wrap gap-5">
            @foreach (['Panelist','Language Editor','Statistician','College Dean','Validator','Others'] as $type)
                <label><input type="checkbox" name="payload[consultant_types][]" value="{{ $type }}" @checked(in_array($type, $payload['consultant_types'] ?? [], true))> {{ $type }}</label>
            @endforeach
        </div>
    </fieldset>
    <label class="block font-bold">Specific Concern/s:<textarea name="payload[specific_concerns]" class="mt-2 min-h-28 w-full">{{ $payload['specific_concerns'] ?? '' }}</textarea></label>
    <label class="block font-bold">Consultant's Recommendations:<textarea name="payload[recommendations]" class="mt-2 min-h-36 w-full">{{ $payload['recommendations'] ?? '' }}</textarea></label>
    <div class="grid gap-6 md:grid-cols-2">
        <label>Follow-up consultation on:<input type="date" name="payload[follow_up_date]" value="{{ $payload['follow_up_date'] ?? '' }}" class="w-full"></label>
        <x-official-signature-field label="Consultant" />
    </div>
</x-student-official-form></div>
