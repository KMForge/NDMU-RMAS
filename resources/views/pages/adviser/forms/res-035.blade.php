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
    $adviserName = $group?->adviser?->name ?? 'Research Adviser';
@endphp
<div x-show="activeOfficialForm === 'RES-035'" x-cloak>
    <x-student-official-form code="RES-Form-035" title="Research Proposal / Final Oral Defense Proceedings" guidebook-page="117">
        <div class="grid gap-4 md:grid-cols-2">
            <fieldset>
                <legend class="mb-2 font-bold">Name & Course of Student/s:</legend>
                @for ($i = 1; $i <= 4; $i++)
                    <label class="mb-2 flex gap-2">
                        <span>{{ $i }}.</span>
                        <input value="{{ $members->get($i - 1)?->student?->name }}" class="flex-1 font-medium" readonly placeholder="Researcher {{ $i }}">
                    </label>
                @endfor
            </fieldset>
            <div class="space-y-4">
                <label class="block">Date:<input type="date" name="payload[date]" value="{{ $payload['date'] ?? now()->format('Y-m-d') }}" class="w-full"></label>
                <label class="block">Research Adviser:<input value="{{ $adviserName }}" class="w-full font-semibold" readonly></label>
            </div>
        </div>
        <label class="block font-bold">Research Title:<input value="{{ $currentResearchTitle }}" class="mt-1 w-full font-bold" readonly></label>
        <fieldset class="flex flex-wrap gap-6">
            <legend class="mb-2 font-bold">Type of Defense:</legend>
            <label><input type="radio" name="payload[defense_type]" value="proposal" @checked(($payload['defense_type'] ?? 'proposal') === 'proposal')> Research Proposal Defense</label>
            <label><input type="radio" name="payload[defense_type]" value="final" @checked(($payload['defense_type'] ?? '') === 'final')> Research Final Oral Defense</label>
        </fieldset>
        <table class="official-form-table text-xs">
            <thead>
                <tr>
                    <th>Area</th>
                    <th>Comments / Corrections / Suggestions</th>
                </tr>
            </thead>
            <tbody>
                @foreach (['Title','Introduction','Method','Results','Discussion','References','Others'] as $area)
                    @php
                        $areaKey = strtolower($area);
                    @endphp
                    <tr>
                        <th class="w-1/4 text-left font-bold">{{ $area }}</th>
                        <td><textarea name="payload[comments][{{ $areaKey }}]" class="min-h-16 w-full border-0 text-xs" placeholder="Comments & suggestions...">{{ $payload['comments'][$areaKey] ?? '' }}</textarea></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2">
            <div>
                <x-official-signature-field label="Research Adviser" />
                <p class="mt-1 text-xs font-bold text-slate-800">{{ $adviserName }}</p>
            </div>
            <div>
                <x-official-signature-field label="Panel Chair" />
            </div>
        </div>
    </x-student-official-form>
</div>
