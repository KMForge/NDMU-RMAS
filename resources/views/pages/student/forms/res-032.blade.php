@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $group = $officialFormInstance?->group;
    $class = $officialFormInstance?->researchClass ?? $group?->researchClass;

    $members = $group?->members?->values() ?? collect();
    $program = $members->first()?->student?->studentProfile?->program;
    $programName = $payload['degree_program'] ?? $program?->name ?? $members->first()?->student?->program ?? 'Information Technology';
@endphp
<div x-show="activeOfficialForm === 'RES-032'" x-cloak>
    <x-student-official-form code="RES-Form-032" title="Consultation Sheet (With Other Consultants)" guidebook-page="112">
        <div class="flex justify-end">
            <label class="flex w-64 items-center gap-2">Date:<input type="date" name="payload[date]" value="{{ $payload['date'] ?? now()->format('Y-m-d') }}" class="w-full"></label>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <fieldset class="space-y-1">
                <legend class="font-bold">Name of Student/s:</legend>
                @for ($i = 1; $i <= 4; $i++)
                    <label class="flex items-center gap-2"><span>{{ $i }}.</span><input value="{{ $members->get($i - 1)?->student?->name }}" class="w-full font-semibold" readonly></label>
                @endfor
            </fieldset>
            <label class="block">Degree Program:<input name="payload[degree_program]" value="{{ $programName }}" class="w-full font-semibold" readonly></label>
        </div>
        <fieldset>
            <legend class="font-bold">Type of Consultant (Please check):</legend>
            <div class="mt-2 flex flex-wrap gap-5">
                @foreach (['Panelist','Language Editor','Statistician','College Dean','Validator','Others'] as $type)
                    <label><input type="checkbox" name="payload[consultant_types][]" value="{{ $type }}" @checked(in_array($type, $payload['consultant_types'] ?? [], true))> {{ $type }}</label>
                @endforeach
            </div>
        </fieldset>
        <label class="block font-bold">Specific Concern/s:<textarea name="payload[specific_concerns]" class="mt-2 min-h-28 w-full">{{ $payload['specific_concerns'] ?? '' }}</textarea></label>
        <label class="block font-bold">Consultant's Recommendations:<textarea name="payload[recommendations]" class="mt-2 min-h-36 w-full">{{ $payload['recommendations'] ?? '' }}</textarea></label>
        <div class="official-signature-row grid gap-6 pt-4 md:grid-cols-2">
            <label>Follow-up consultation on:<input type="date" name="payload[follow_up_date]" value="{{ $payload['follow_up_date'] ?? '' }}" class="w-full"></label>
            <x-official-signature-field label="Consultant Signature" />
        </div>
    </x-student-official-form>
</div>
