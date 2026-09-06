@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $group = $officialFormInstance?->group;
    $class = $officialFormInstance?->researchClass ?? $group?->researchClass;

    $currentResearchTitle = $payload['research_title']
        ?? $payload['title']
        ?? $group?->researchGroup?->currentProject?->title
        ?? $group?->titlePresentation?->approved_title
        ?? '';

    $members = $group?->members?->values() ?? collect();
    $joinedResearchers = $members->map(fn($m) => $m->student?->name)->filter()->join(', ');
    $program = $members->first()?->student?->studentProfile?->program;
    $programName = $payload['course'] ?? $program?->name ?? $members->first()?->student?->program ?? ($class?->name ?? 'N/A');

    $source = $officialFormInstance?->source;
    $isDocReview = $source instanceof \App\Models\DocumentReview;
    $isRevRequest = $source instanceof \App\Models\RevisionRequest;
@endphp
<div x-show="activeOfficialForm === 'RES-039'" x-cloak>
    <x-student-official-form code="RES-Form-039" title="Research Revision Chart" guidebook-page="123">
        <label class="block font-bold">Research Title:<input value="{{ $currentResearchTitle }}" class="w-full font-bold" readonly></label>
        <div class="grid gap-4 md:grid-cols-2">
            <label>Researcher/s:
                <textarea class="mt-1 min-h-16 w-full font-bold" readonly>{{ $joinedResearchers }}</textarea>
            </label>
            <div class="space-y-3">
                <label class="block">Course / Program:<input value="{{ $programName }}" class="w-full font-semibold" readonly></label>
                @if ($isDocReview)
                    <label class="block">Source Reviewer:<input value="{{ $source->reviewer?->name }} (Decision: {{ str($source->decision)->headline() }})" class="w-full font-semibold" readonly></label>
                @elseif ($isRevRequest)
                    <label class="block">Source Requester:<input value="{{ $source->requester?->name }} (Title: {{ $source->title }})" class="w-full font-semibold" readonly></label>
                @else
                    <label class="block">Chairman of the Panel:<input value="{{ $group?->adviser?->name ?? 'Panel Chairman' }}" class="w-full" readonly></label>
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
                        <td><textarea name="payload[revisions][{{ $areaKey }}][suggestions]" class="min-h-16 w-full border-0 text-xs" placeholder="Panel recommendations...">{{ $areaPayload['suggestions'] ?? '' }}</textarea></td>
                        <td><textarea name="payload[revisions][{{ $areaKey }}][revision_made]" class="min-h-16 w-full border-0 text-xs" placeholder="Revisions made...">{{ $areaPayload['revision_made'] ?? '' }}</textarea></td>
                        <td><input name="payload[revisions][{{ $areaKey }}][pages]" value="{{ $areaPayload['pages'] ?? '' }}" class="w-full text-center"></td>
                        <td><x-official-signature-field :label="'Panel approval for '.$area" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-student-official-form>
</div>
