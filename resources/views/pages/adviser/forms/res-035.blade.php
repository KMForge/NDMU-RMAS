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
    $defense = $group ? \App\Models\Defense::query()->with(['currentSchedule', 'activePanelAssignments.user'])->where('research_class_group_id', $group->id)->latest('id')->first() : null;
    $panelists = $defense?->activePanelAssignments?->sortBy('panel_position')?->values() ?? collect();
@endphp
<div x-show="activeOfficialForm === 'RES-035'" x-cloak>
    <x-student-official-form code="RES-Form-035" title="Research Proposal / Pre-Final / Final Oral Defense Proceedings" guidebook-page="117">
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
                <label class="block">Date:<input type="date" name="payload[date]" value="{{ $payload['date'] ?? $defense?->currentSchedule?->starts_at?->format('Y-m-d') ?? now()->format('Y-m-d') }}" class="w-full"></label>
                <label class="block">Time:<input type="time" name="payload[time]" value="{{ $payload['time'] ?? $defense?->currentSchedule?->starts_at?->format('H:i') }}" class="w-full"></label>
                <label class="block">Research Adviser:<input value="{{ $adviserName }}" class="w-full font-semibold" readonly></label>
            </div>
        </div>
        <label class="block font-bold">Research Title:<input value="{{ $currentResearchTitle }}" class="mt-1 w-full font-bold" readonly></label>
        <fieldset class="flex flex-wrap gap-6">
            <legend class="mb-2 font-bold">Type of Defense:</legend>
            <label><input type="radio" name="payload[defense_type]" value="proposal" @checked(($payload['defense_type'] ?? 'proposal') === 'proposal')> Research Proposal Defense</label>
            <label><input type="radio" name="payload[defense_type]" value="pre_final" @checked(($payload['defense_type'] ?? '') === 'pre_final' || ($payload['defense_type'] ?? '') === 'pre_final_defense')> Research Pre-Final Defense</label>
            <label><input type="radio" name="payload[defense_type]" value="final" @checked(($payload['defense_type'] ?? '') === 'final')> Research Final Oral Defense</label>
        </fieldset>
        <h3 class="font-bold italic">Comments / Corrections / Suggestions:</h3>
        <table class="official-form-table text-xs">
            <thead>
                <tr>
                    <th>Area</th>
                    <th>Comments / Corrections / Suggestions</th>
                </tr>
            </thead>
            <tbody>
                @foreach (['Title','Introduction','Method','Results','Discussion','References','Appendices','Others'] as $area)
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

        <details class="rounded-lg border border-slate-200 p-3 print:hidden">
            <summary class="cursor-pointer font-bold">System defense decision</summary>
            <div class="mt-3 flex flex-wrap gap-5 text-xs">
                <label><input type="radio" name="payload[decision]" value="passed" @checked(($payload['decision'] ?? '') === 'passed')> Passed</label>
                <label><input type="radio" name="payload[decision]" value="passed_with_revisions" @checked(($payload['decision'] ?? '') === 'passed_with_revisions')> Passed with revisions</label>
                <label><input type="radio" name="payload[decision]" value="failed" @checked(($payload['decision'] ?? '') === 'failed')> Failed</label>
            </div>
        </details>

        <div class="space-y-3 pt-5 text-xs">
            <strong>PANELISTS:</strong>
            @foreach (['Chairman', 'Member', 'Member'] as $index => $position)
                <div class="grid grid-cols-[7rem_1fr_1fr] gap-5"><span>{{ $position }}:</span><span class="border-b border-slate-700 text-center">{{ $panelists->get($index)?->user?->name ?? 'Pending assignment' }}</span><span class="border-b border-slate-700 text-center">Signature</span></div>
            @endforeach
            <div class="official-signature-row grid grid-cols-[7rem_1fr] gap-5 pt-5"><strong>Prepared by:</strong><div class="text-center"><x-official-signature-field :instance="$officialFormInstance" label="Research Adviser" :value="$adviserName" actor-type="adviser" academic-action="record" /></div></div>
        </div>
    </x-student-official-form>
</div>
