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
    $adviserName = $group?->adviser?->name ?? 'N/A';
@endphp
<div x-show="activeOfficialForm === 'RES-034'" x-cloak>
    <x-student-official-form code="RES-Form-034" title="Research Proposal / Pre-Final / Final Oral Defense Pre-Conference" guidebook-page="114">
        <div class="grid gap-4 md:grid-cols-2">
            <label>Name & Course of Student/s:
                <textarea class="mt-1 min-h-20 w-full font-bold" readonly>{{ $joinedResearchers }}</textarea>
            </label>
            <div class="space-y-4">
                <label class="block">Date:<input type="date" name="payload[date]" value="{{ $payload['date'] ?? now()->format('Y-m-d') }}" class="w-full"></label>
                <label class="block">Time:<input type="time" name="payload[time]" value="{{ $payload['time'] ?? '10:00' }}" class="w-full"></label>
            </div>
        </div>
        <label class="block">Research Adviser:<input value="{{ $adviserName }}" class="w-full font-semibold" readonly></label>
        <label class="block font-bold">Research Title:<input value="{{ $currentResearchTitle }}" class="w-full font-bold" readonly></label>
        <fieldset>
            <legend class="font-bold">Type of Defense:</legend>
            <div class="mt-2 flex flex-wrap gap-6">
                <label><input type="radio" name="payload[defense_type]" value="proposal" @checked(($payload['defense_type'] ?? 'proposal') === 'proposal')> Research Proposal Defense</label>
                <label><input type="radio" name="payload[defense_type]" value="pre_final" @checked(($payload['defense_type'] ?? '') === 'pre_final' || ($payload['defense_type'] ?? '') === 'pre_final_defense')> Research Pre-Final Defense</label>
                <label><input type="radio" name="payload[defense_type]" value="final" @checked(($payload['defense_type'] ?? '') === 'final')> Research Final Oral Defense</label>
            </div>
        </fieldset>
        <table class="official-form-table text-xs">
            <thead>
                <tr>
                    <th>Issues / Concerns</th>
                    <th class="w-20">Page</th>
                </tr>
            </thead>
            <tbody>
                @foreach (['General Concern','Title','Preliminaries','Introduction','Method','Results','Discussion'] as $area)
                    @php
                        $areaKey = strtolower($area);
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $area }}</strong>
                            <textarea name="payload[issues][{{ $areaKey }}]" class="mt-1 min-h-16 w-full border-0 text-xs" placeholder="Discussion & issues...">{{ $payload['issues'][$areaKey] ?? '' }}</textarea>
                        </td>
                        <td>
                            <input name="payload[pages][{{ $areaKey }}]" value="{{ $payload['pages'][$areaKey] ?? '' }}" class="w-full text-center">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-student-official-form>
</div>
