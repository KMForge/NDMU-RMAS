@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
@endphp
<div x-show="activeOfficialForm === 'RES-043A'" x-cloak>
    <x-student-official-form code="RES-Form-043A" title="Research Instrument Item Validation" guidebook-page="128">
        <div class="grid gap-4 md:grid-cols-2"><label>Student Researcher/s:<textarea class="mt-1 min-h-16 w-full" readonly>{{ $officialFormInstance?->group?->members?->pluck('student.name')->join(', ') }}</textarea></label><label>Research Title:<textarea class="mt-1 min-h-16 w-full" readonly>{{ $officialFormInstance?->group?->researchGroup?->title }}</textarea></label></div>
        <label class="block font-bold">Statement of the Problem:<textarea name="payload[problem]" class="mt-1 min-h-24 w-full font-normal">{{ $payload['problem'] ?? '' }}</textarea></label>
        <div class="border border-[#173c30] p-3 text-[10px] leading-4"><strong>Instruction for Validator:</strong> Assess the alignment of each item with the statement of the problem. Check ACCEPT when the item is appropriate and aligned, REVISE when changes are needed, or REJECT when the item is unsuitable. Enter comments, suggested revisions, or justification for rejected items.</div>
        <table class="official-form-table text-[10px]"><thead><tr><th class="w-8">No.</th><th>Question / Instrument Item</th><th class="w-14">Accept</th><th class="w-14">Revise</th><th class="w-14">Reject</th><th>Comment / Justification</th></tr></thead><tbody>@for ($i=1; $i<=10; $i++)<tr><td>{{ $i }}</td><td><textarea name="payload[items][{{ $i }}][question]" class="min-h-12 w-full border-0">{{ $payload['items'][$i]['question'] ?? '' }}</textarea></td>@foreach (['accept','revise','reject'] as $decision)<td class="text-center"><input type="radio" name="payload[items][{{ $i }}][decision]" value="{{ $decision }}" {{ ($payload['items'][$i]['decision'] ?? null) === $decision ? 'checked' : '' }}></td>@endforeach<td><textarea name="payload[items][{{ $i }}][comment]" class="min-h-12 w-full border-0">{{ $payload['items'][$i]['comment'] ?? '' }}</textarea></td></tr>@endfor</tbody></table>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2"><x-official-signature-field name-field="res_043a_validator_printed_name" label="Validated by" /><label>Date Validated:<input type="date" name="payload[date]" value="{{ $payload['date'] ?? '' }}" class="w-full"></label></div>
    </x-student-official-form>
</div>
