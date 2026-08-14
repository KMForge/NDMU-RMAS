@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $source = $officialFormInstance?->source;
    $isDocReview = $source instanceof \App\Models\DocumentReview;
    $isRevRequest = $source instanceof \App\Models\RevisionRequest;
@endphp
<div x-show="activeOfficialForm === 'RES-039'" x-cloak><x-student-official-form code="RES-Form-039" title="Research Revision Chart" guidebook-page="123">
    <label class="block">Research Title:<input value="{{ $officialFormInstance?->group?->title }}" class="w-full" readonly></label>
    <div class="grid gap-4 md:grid-cols-2">
        <label>Researcher/s:
            <textarea class="mt-1 min-h-16 w-full" readonly>{{ $officialFormInstance?->group?->members?->pluck('student.name')->filter()->join(', ') }}</textarea>
        </label>
        <div class="space-y-3">
            <label class="block">Course:<input value="{{ $officialFormInstance?->group?->researchClass?->name }}" class="w-full" readonly></label>
            @if ($isDocReview)
                <label class="block">Source Reviewer:<input value="{{ $source->reviewer?->name }} (Decision: {{ str($source->decision)->headline() }})" class="w-full" readonly></label>
            @elseif ($isRevRequest)
                <label class="block">Source Requester:<input value="{{ $source->requester?->name }} (Title: {{ $source->title }})" class="w-full" readonly></label>
            @else
                <label class="block">Chairman of the Panel:<input placeholder="Panel Chairman" class="w-full" readonly></label>
            @endif
        </div>
    </div>
    @if ($isDocReview && $source->review_notes)
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs">
            <p class="font-bold text-[#0e5c3a]">Source Review Notes:</p>
            <p class="mt-1 text-gray-700">{{ $source->review_notes }}</p>
        </div>
    @elseif ($isRevRequest && $source->instructions)
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs">
            <p class="font-bold text-[#0e5c3a]">Source Revision Instructions:</p>
            <p class="mt-1 text-gray-700">{{ $source->instructions }}</p>
        </div>
    @endif
    <table class="official-form-table text-xs">
        <thead>
            <tr>
                <th class="w-28">Research Area</th>
                <th>Suggestions and Recommendations</th>
                <th>Recommended Revision Made</th>
                <th class="w-16">New Page/s</th>
                <th class="w-24">Approval (Signature)</th>
            </tr>
        </thead>
        <tbody>
            @foreach (['Title', 'Introduction', 'Method', 'Results', 'Discussion', 'References', 'Others'] as $area)
                @php
                    $areaKey = strtolower($area);
                    $areaPayload = $payload['revisions'][$areaKey] ?? [];
                @endphp
                <tr>
                    <td class="font-bold">{{ $area }}</td>
                    <td><textarea name="payload[revisions][{{ $areaKey }}][suggestions]" class="min-h-16 w-full border-0">{{ $areaPayload['suggestions'] ?? '' }}</textarea></td>
                    <td><textarea name="payload[revisions][{{ $areaKey }}][revision_made]" class="min-h-16 w-full border-0">{{ $areaPayload['revision_made'] ?? '' }}</textarea></td>
                    <td><input name="payload[revisions][{{ $areaKey }}][pages]" value="{{ $areaPayload['pages'] ?? '' }}" class="w-full"></td>
                    <td><x-official-signature-field :label="'Panel approval for '.$area" /></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <fieldset><legend class="font-bold">Recommendation:</legend>
        <div class="mt-2 flex flex-wrap gap-5">
            <label><input type="radio" name="payload[recommendation]" value="proposal" @checked(($payload['recommendation'] ?? '') === 'proposal')> Research Proposal Defense</label>
            <label><input type="radio" name="payload[recommendation]" value="final" @checked(($payload['recommendation'] ?? '') === 'final')> Research Final Oral Defense</label>
        </div>
    </fieldset>
    <div class="grid gap-6 pt-8 md:grid-cols-2">
        <x-official-signature-field label="Research Adviser" />
        <label>Date Reviewed:<input type="date" name="payload[date]" value="{{ $payload['date'] ?? '' }}" class="w-full"></label>
    </div>
</x-student-official-form></div>
