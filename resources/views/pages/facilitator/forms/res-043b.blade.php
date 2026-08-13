@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
@endphp
<div x-show="activeOfficialForm === 'RES-043B'" x-cloak>
    <x-student-official-form code="RES-Form-043B" title="Research Instrument Validation Rating" guidebook-page="129">
        <div class="grid gap-4 md:grid-cols-2"><label>Student Researcher/s:<textarea class="mt-1 min-h-16 w-full" readonly>{{ $officialFormInstance?->group?->members?->pluck('student.name')->join(', ') }}</textarea></label><label>Research Title:<textarea class="mt-1 min-h-16 w-full" readonly>{{ $officialFormInstance?->group?->researchGroup?->title }}</textarea></label></div>
        <table class="official-form-table text-[10px]"><thead><tr><th>Scale</th><th>Verbal Description</th></tr></thead><tbody>@foreach ([5=>'Excellent',4=>'Very Good',3=>'Good',2=>'Fair',1=>'Poor'] as $scale=>$description)<tr><td class="text-center">{{ $scale }}</td><td>{{ $description }}</td></tr>@endforeach</tbody></table>
        @php
            $validationCriteria = [
                'Clarity of Items — The vocabulary level of the questions suits the respondents.',
                'Presentation / Organization of Items — Items are presented in a logical manner.',
                'Suitability of Items — Items represent the substance of the research and intended measures.',
                'Adequateness / Coverage — The number of questions per category sufficiently covers the research.',
                'Attainment of Purpose — The items elicit the information required by the study.',
            ];
        @endphp
        @php($ratingMean = collect($payload['ratings'] ?? [])->filter(static fn (mixed $value): bool => is_numeric($value))->avg())
        <table class="official-form-table text-[10px]"><thead><tr><th>Criteria</th>@for ($rating=1; $rating<=5; $rating++)<th class="w-10">{{ $rating }}</th>@endfor</tr></thead><tbody>@foreach ($validationCriteria as $index=>$criterion)<tr><td><strong>{{ $index + 1 }}.</strong> {{ $criterion }}</td>@for ($rating=1; $rating<=5; $rating++)<td class="text-center"><input type="radio" name="payload[ratings][{{ $index }}]" value="{{ $rating }}" {{ (int) ($payload['ratings'][$index] ?? 0) === $rating ? 'checked' : '' }}></td>@endfor</tr>@endforeach<tr><th>Mean</th><td colspan="5"><input type="number" min="1" max="5" step="0.01" value="{{ $ratingMean }}" class="w-full" readonly></td></tr></tbody></table>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2"><label>Date Validated:<input type="date" name="payload[date]" value="{{ $payload['date'] ?? '' }}" class="w-full"></label><x-official-signature-field name-field="res_043b_validator_printed_name" label="Validated by" /></div>
    </x-student-official-form>
</div>
