@php
    $instance = $officialFormInstance ?? null;
    $group = $instance?->group;
    $schedule = $instance?->source instanceof \App\Models\DefenseSchedule ? $instance->source : null;
    $defense = $schedule?->defense;
    $currentDefenseType = $payload['res_036_defense_type'] ?? (in_array($defense?->defense_type, ['proposal_defense', 'title_proposal', 'proposal']) ? 'proposal' : 'final');
    $currentDate = $payload['res_036_date'] ?? $schedule?->starts_at?->format('Y-m-d') ?? now()->format('Y-m-d');
    $currentTime = $payload['res_036_time'] ?? $schedule?->starts_at?->format('H:i') ?? '';
    $currentVenue = $payload['res_036_venue'] ?? $schedule?->room?->name ?? $schedule?->room?->code ?? '';
    $currentResearchTitle = $payload['res_036_research_title'] ?? $group?->researchGroup?->currentProject?->title ?? '';
    $panelistName = $payload['res_036_panelist_printed_name'] ?? auth()->user()->name;
    $signedAt = $payload['res_036_signed_at'] ?? now()->format('Y-m-d');

    $presentersData = $payload['res_036_presenters'] ?? [];
    if (empty($presentersData) && $group && $group->members) {
        $idx = 1;
        foreach ($group->members as $m) {
            $presentersData[$idx++] = [
                'name' => $m->student?->name ?? '',
                'communication' => '',
                'organization' => '',
                'effectiveness' => '',
                'total' => '',
            ];
        }
    }
@endphp

<div x-show="activeOfficialForm === 'RES-036'" x-cloak>
    <x-student-official-form code="RES-Form-036" title="Evaluation of Research Defense" guidebook-page="120">
        <fieldset class="flex flex-wrap gap-6">
            <legend class="mb-2 font-bold">Type of Defense:</legend>
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="radio" name="payload[res_036_defense_type]" value="proposal" {{ $currentDefenseType === 'proposal' ? 'checked' : '' }}>
                <span>Research Proposal Defense</span>
            </label>
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="radio" name="payload[res_036_defense_type]" value="final" {{ $currentDefenseType === 'final' ? 'checked' : '' }}>
                <span>Research Final Oral Defense</span>
            </label>
        </fieldset>

        <div class="grid gap-3 md:grid-cols-3">
            <label class="block text-xs font-semibold">Date:
                <input type="date" name="payload[res_036_date]" value="{{ $currentDate }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs">
            </label>
            <label class="block text-xs font-semibold">Time:
                <input type="time" name="payload[res_036_time]" value="{{ $currentTime }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs">
            </label>
            <label class="block text-xs font-semibold">Venue:
                <input name="payload[res_036_venue]" value="{{ $currentVenue }}" placeholder="Defense venue" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs">
            </label>
        </div>

        <label class="block font-bold text-xs">Research Title:
            <input name="payload[res_036_research_title]" value="{{ $currentResearchTitle }}" placeholder="Full research title" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold">
        </label>

        <h3 class="font-bold text-sm text-[#0e5c3a]">I – Research Paper Evaluation</h3>
        <table class="official-form-table text-[11px] w-full border-collapse">
            <thead>
                <tr class="bg-emerald-50">
                    <th class="border border-gray-300 px-3 py-2 text-left">Criteria</th>
                    <th class="border border-gray-300 px-3 py-2 text-center w-28">Rating</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">General Comments / Suggestions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border border-gray-300 px-3 py-2 font-bold text-gray-800">Research Paper Quality (50%)</td>
                    <td class="border border-gray-300 p-1">
                        <input type="number" min="0" max="50" step="0.01" id="res_036_paper_0"
                            name="payload[res_036_paper_ratings][]"
                            value="{{ $payload['res_036_paper_ratings'][0] ?? '' }}"
                            oninput="calcRes036()" onchange="calcRes036()" onkeyup="calcRes036()"
                            placeholder="0-50"
                            class="w-full text-center rounded border border-gray-200 py-1.5 font-bold text-xs focus:ring-1 focus:ring-emerald-500">
                    </td>
                    <td class="border border-gray-300 p-1">
                        <textarea name="payload[res_036_paper_comments][]" rows="2" placeholder="Comments on research paper quality..."
                            class="w-full text-xs rounded border border-gray-200 p-1.5 resize-none focus:ring-1 focus:ring-emerald-500">{{ $payload['res_036_paper_comments'][0] ?? '' }}</textarea>
                    </td>
                </tr>
                <tr>
                    <td class="border border-gray-300 px-3 py-2 font-bold text-gray-800">Originality (25%)</td>
                    <td class="border border-gray-300 p-1">
                        <input type="number" min="0" max="25" step="0.01" id="res_036_paper_1"
                            name="payload[res_036_paper_ratings][]"
                            value="{{ $payload['res_036_paper_ratings'][1] ?? '' }}"
                            oninput="calcRes036()" onchange="calcRes036()" onkeyup="calcRes036()"
                            placeholder="0-25"
                            class="w-full text-center rounded border border-gray-200 py-1.5 font-bold text-xs focus:ring-1 focus:ring-emerald-500">
                    </td>
                    <td class="border border-gray-300 p-1">
                        <textarea name="payload[res_036_paper_comments][]" rows="2" placeholder="Comments on originality..."
                            class="w-full text-xs rounded border border-gray-200 p-1.5 resize-none focus:ring-1 focus:ring-emerald-500">{{ $payload['res_036_paper_comments'][1] ?? '' }}</textarea>
                    </td>
                </tr>
                <tr>
                    <td class="border border-gray-300 px-3 py-2 font-bold text-gray-800">Relevance (25%)</td>
                    <td class="border border-gray-300 p-1">
                        <input type="number" min="0" max="25" step="0.01" id="res_036_paper_2"
                            name="payload[res_036_paper_ratings][]"
                            value="{{ $payload['res_036_paper_ratings'][2] ?? '' }}"
                            oninput="calcRes036()" onchange="calcRes036()" onkeyup="calcRes036()"
                            placeholder="0-25"
                            class="w-full text-center rounded border border-gray-200 py-1.5 font-bold text-xs focus:ring-1 focus:ring-emerald-500">
                    </td>
                    <td class="border border-gray-300 p-1">
                        <textarea name="payload[res_036_paper_comments][]" rows="2" placeholder="Comments on relevance..."
                            class="w-full text-xs rounded border border-gray-200 p-1.5 resize-none focus:ring-1 focus:ring-emerald-500">{{ $payload['res_036_paper_comments'][2] ?? '' }}</textarea>
                    </td>
                </tr>
                <tr class="bg-gray-50 font-black">
                    <td class="border border-gray-300 px-3 py-2 text-right">Total (100%):</td>
                    <td class="border border-gray-300 p-1 text-center">
                        <input type="text" id="res_036_paper_total" name="payload[res_036_paper_total]" readonly
                            value="{{ $payload['res_036_paper_total'] ?? '' }}"
                            class="w-full text-center bg-gray-100 py-1.5 font-black text-xs text-emerald-800 rounded border-0">
                    </td>
                    <td class="border border-gray-300 px-3 py-2"></td>
                </tr>
            </tbody>
        </table>

        <h3 class="font-bold text-sm text-[#0e5c3a] pt-2">II – Student's Presentation Evaluation</h3>
        <table class="official-form-table text-[11px] w-full border-collapse">
            <thead>
                <tr class="bg-emerald-50">
                    <th class="border border-gray-300 px-3 py-2 text-left">Name of Student Presenter</th>
                    <th class="border border-gray-300 px-2 py-2 text-center w-24">Communication Skills (20%)</th>
                    <th class="border border-gray-300 px-2 py-2 text-center w-24">Work Organization (30%)</th>
                    <th class="border border-gray-300 px-2 py-2 text-center w-24">Effectiveness (50%)</th>
                    <th class="border border-gray-300 px-2 py-2 text-center w-24">Total (100%)</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 1; $i <= 4; $i++)
                    @php
                        $pres = $presentersData[$i] ?? [];
                        $pName = $pres['name'] ?? '';
                        $cScore = $pres['communication'] ?? '';
                        $oScore = $pres['organization'] ?? '';
                        $eScore = $pres['effectiveness'] ?? '';
                        $tScore = $pres['total'] ?? '';
                    @endphp
                    <tr>
                        <td class="border border-gray-300 p-1.5">
                            <input name="payload[res_036_presenters][{{ $i }}][name]" value="{{ $pName }}" placeholder="Student Researcher {{ $i }}"
                                class="w-full rounded border border-gray-200 px-2 py-1.5 text-xs font-semibold {{ $pName ? 'bg-emerald-50/30' : '' }}">
                        </td>
                        <td class="border border-gray-300 p-1">
                            <input type="number" min="0" max="20" step="0.01" id="res_036_comm_{{ $i }}"
                                name="payload[res_036_presenters][{{ $i }}][communication]"
                                value="{{ $cScore }}"
                                oninput="calcRes036()" onchange="calcRes036()" onkeyup="calcRes036()"
                                placeholder="0-20"
                                class="w-full text-center rounded border border-gray-200 py-1.5 text-xs focus:ring-1 focus:ring-emerald-500">
                        </td>
                        <td class="border border-gray-300 p-1">
                            <input type="number" min="0" max="30" step="0.01" id="res_036_org_{{ $i }}"
                                name="payload[res_036_presenters][{{ $i }}][organization]"
                                value="{{ $oScore }}"
                                oninput="calcRes036()" onchange="calcRes036()" onkeyup="calcRes036()"
                                placeholder="0-30"
                                class="w-full text-center rounded border border-gray-200 py-1.5 text-xs focus:ring-1 focus:ring-emerald-500">
                        </td>
                        <td class="border border-gray-300 p-1">
                            <input type="number" min="0" max="50" step="0.01" id="res_036_eff_{{ $i }}"
                                name="payload[res_036_presenters][{{ $i }}][effectiveness]"
                                value="{{ $eScore }}"
                                oninput="calcRes036()" onchange="calcRes036()" onkeyup="calcRes036()"
                                placeholder="0-50"
                                class="w-full text-center rounded border border-gray-200 py-1.5 text-xs focus:ring-1 focus:ring-emerald-500">
                        </td>
                        <td class="border border-gray-300 p-1 text-center bg-gray-50">
                            <input type="text" id="res_036_total_{{ $i }}"
                                name="payload[res_036_presenters][{{ $i }}][total]"
                                value="{{ $tScore }}" readonly
                                class="w-full text-center bg-transparent py-1.5 font-black text-xs text-emerald-800 border-0">
                        </td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <div class="grid gap-8 pt-8 text-center md:grid-cols-2">
            <x-official-signature-field :instance="$officialFormInstance ?? ($instance ?? null)" name-field="payload[res_036_panelist_printed_name]" label="Panelist" :value="$panelistName" actor-type="panelist" academic-action="evaluate" />
            <label class="block text-xs font-semibold text-left">Date:
                <input type="date" name="payload[res_036_signed_at]" value="{{ $signedAt }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs">
            </label>
        </div>
    </x-student-official-form>
</div>

<script>
function calcRes036() {
    // Part I
    const p0 = parseFloat(document.getElementById('res_036_paper_0')?.value);
    const p1 = parseFloat(document.getElementById('res_036_paper_1')?.value);
    const p2 = parseFloat(document.getElementById('res_036_paper_2')?.value);
    let paperSum = 0;
    let hasPaper = false;
    if (!isNaN(p0)) { paperSum += p0; hasPaper = true; }
    if (!isNaN(p1)) { paperSum += p1; hasPaper = true; }
    if (!isNaN(p2)) { paperSum += p2; hasPaper = true; }

    const paperTotalEl = document.getElementById('res_036_paper_total');
    if (paperTotalEl) {
        paperTotalEl.value = hasPaper ? paperSum.toFixed(2) : '';
    }

    // Part II
    for (let i = 1; i <= 4; i++) {
        const comm = parseFloat(document.getElementById('res_036_comm_' + i)?.value);
        const org = parseFloat(document.getElementById('res_036_org_' + i)?.value);
        const eff = parseFloat(document.getElementById('res_036_eff_' + i)?.value);
        let rowSum = 0;
        let hasRow = false;
        if (!isNaN(comm)) { rowSum += comm; hasRow = true; }
        if (!isNaN(org)) { rowSum += org; hasRow = true; }
        if (!isNaN(eff)) { rowSum += eff; hasRow = true; }

        const totalEl = document.getElementById('res_036_total_' + i);
        if (totalEl) {
            totalEl.value = hasRow ? rowSum.toFixed(2) : '';
        }
    }
}

document.addEventListener('input', function(e) {
    if (e.target && (e.target.id?.startsWith('res_036_') || e.target.name?.includes('res_036_'))) {
        calcRes036();
    }
});

document.addEventListener('DOMContentLoaded', calcRes036);
window.addEventListener('load', calcRes036);
calcRes036();
setTimeout(calcRes036, 50);
setTimeout(calcRes036, 300);
</script>
