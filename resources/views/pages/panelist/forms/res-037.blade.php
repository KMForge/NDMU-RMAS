@php
    $instance = $officialFormInstance ?? null;
    $round = $instance?->source instanceof \App\Models\DefenseEvaluationRound ? $instance->source : null;
    $schedule = $instance?->source instanceof \App\Models\DefenseSchedule ? $instance->source : ($round?->defense_schedule_id ? \App\Models\DefenseSchedule::find($round->defense_schedule_id) : null);
    $defense = $schedule?->defense ?? $round?->defense;
    $group = $instance?->group ?? $round?->group ?? $defense?->group;

    $defenseTypeRaw = $payload['res_037_defense_type'] ?? $defense?->defense_type ?? '';
    $currentDefenseType = in_array($defenseTypeRaw, ['proposal_defense', 'title_proposal', 'proposal'], true)
        ? 'proposal'
        : (in_array($defenseTypeRaw, ['pre_final_defense', 'pre_final', 'pre-final'], true) ? 'pre_final' : 'final');
    $currentDate = $payload['res_037_date'] ?? $schedule?->starts_at?->format('Y-m-d') ?? now()->format('Y-m-d');
    $currentTime = $payload['res_037_time'] ?? $schedule?->starts_at?->format('H:i') ?? '';
    $currentVenue = $payload['res_037_venue'] ?? $schedule?->room?->name ?? $schedule?->room?->code ?? '';
    $currentResearchTitle = $payload['res_037_research_title'] ?? $defense?->researchTitle?->title ?? $group?->researchGroup?->currentProject?->title ?? '';

    // Paper ratings across 3 panelists
    $paperScores = $payload['res_037_paper_scores'] ?? [];
    if (empty($paperScores) && isset($payload['panelist_evaluations']) && is_array($payload['panelist_evaluations'])) {
        foreach ($payload['panelist_evaluations'] as $pe) {
            $paperScores[] = (string) ($pe['research_paper_total'] ?? '');
        }
    }
    $paperAverage = $payload['res_037_paper_average'] ?? $payload['research_paper_average'] ?? '';

    // Student presentations
    $studentsList = $payload['res_037_students'] ?? [];
    $studentScores = $payload['res_037_student_scores'] ?? [];
    $studentAverages = $payload['res_037_student_averages'] ?? [];

    if (isset($payload['student_summaries']) && is_array($payload['student_summaries'])) {
        $sIdx = 1;
        foreach ($payload['student_summaries'] as $ss) {
            if (empty($studentsList[$sIdx])) {
                $studentsList[$sIdx] = $ss['student_name'] ?? '';
            }
            if (empty($studentAverages[$sIdx - 1])) {
                $studentAverages[$sIdx - 1] = (string) ($ss['presentation_average'] ?? '');
            }
            if (empty($studentScores[$sIdx]) && isset($payload['panelist_evaluations'])) {
                foreach ($payload['panelist_evaluations'] as $pe) {
                    $matchingScore = collect($pe['student_scores'] ?? [])->firstWhere('student_id', $ss['student_id']);
                    $studentScores[$sIdx][] = (string) ($matchingScore['presentation_total'] ?? '');
                }
            }
            $sIdx++;
        }
    }

    if (empty($studentsList) && $group && $group->members) {
        $idx = 1;
        foreach ($group->members as $m) {
            $studentsList[$idx++] = $m->student?->name ?? '';
        }
    }

    $signerName = $payload['res_037_panelist_printed_name'] ?? ($round?->summary_signer_user_id ? \App\Models\User::find($round->summary_signer_user_id)?->name : auth()->user()?->name ?? 'Panel Member');
    $submittedAt = $payload['res_037_submitted_at'] ?? now()->format('Y-m-d');
@endphp

<div x-show="activeOfficialForm === 'RES-037'" x-cloak>
    <x-student-official-form code="RES-Form-037" title="Evaluation Summary of Research Defense" guidebook-page="121">
        <fieldset class="flex flex-wrap gap-6">
            <legend class="mb-2 font-bold">Type of Defense:</legend>
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="radio" name="payload[res_037_defense_type]" value="proposal" {{ $currentDefenseType === 'proposal' ? 'checked' : '' }}>
                <span>Research Proposal Defense</span>
            </label>
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="radio" name="payload[res_037_defense_type]" value="pre_final" {{ $currentDefenseType === 'pre_final' ? 'checked' : '' }}>
                <span>Research Pre-Final Defense</span>
            </label>
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="radio" name="payload[res_037_defense_type]" value="final" {{ $currentDefenseType === 'final' ? 'checked' : '' }}>
                <span>Research Final Oral Defense</span>
            </label>
        </fieldset>

        <div class="grid gap-3 md:grid-cols-3">
            <label class="block text-xs font-semibold">Date:
                <input type="date" name="payload[res_037_date]" value="{{ $currentDate }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs">
            </label>
            <label class="block text-xs font-semibold">Time:
                <input type="time" name="payload[res_037_time]" value="{{ $currentTime }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs">
            </label>
            <label class="block text-xs font-semibold">Venue:
                <input name="payload[res_037_venue]" value="{{ $currentVenue }}" placeholder="Defense venue" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs">
            </label>
        </div>

        <label class="block font-bold text-xs">Research Title:
            <input name="payload[res_037_research_title]" value="{{ $currentResearchTitle }}" placeholder="Full research title" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold">
        </label>

        <table class="official-form-table text-[11px] w-full border-collapse">
            <thead>
                <tr class="bg-emerald-50">
                    <th class="border border-gray-300 px-3 py-2 text-left">Area</th>
                    <th class="border border-gray-300 px-2 py-2 text-center w-24">Panelist 1</th>
                    <th class="border border-gray-300 px-2 py-2 text-center w-24">Panelist 2</th>
                    <th class="border border-gray-300 px-2 py-2 text-center w-24">Panelist 3</th>
                    <th class="border border-gray-300 px-2 py-2 text-center w-28">Average Rating<br><small>(1 + 2 + 3) ÷ 3</small></th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th class="border border-gray-300 px-3 py-2 text-left bg-gray-50">
                        I – Research Paper Evaluation (100%)<br>
                        <small class="font-normal text-gray-500">Quality, Originality & Relevance</small>
                    </th>
                    <td class="border border-gray-300 p-1">
                        <input type="number" min="0" max="100" step="0.01" id="res_037_paper_0" name="payload[res_037_paper_scores][]" value="{{ $paperScores[0] ?? '' }}" oninput="calcRes037()" onchange="calcRes037()" onkeyup="calcRes037()" class="w-full text-center rounded border border-gray-200 py-1.5 text-xs font-semibold focus:ring-1 focus:ring-emerald-500">
                    </td>
                    <td class="border border-gray-300 p-1">
                        <input type="number" min="0" max="100" step="0.01" id="res_037_paper_1" name="payload[res_037_paper_scores][]" value="{{ $paperScores[1] ?? '' }}" oninput="calcRes037()" onchange="calcRes037()" onkeyup="calcRes037()" class="w-full text-center rounded border border-gray-200 py-1.5 text-xs font-semibold focus:ring-1 focus:ring-emerald-500">
                    </td>
                    <td class="border border-gray-300 p-1">
                        <input type="number" min="0" max="100" step="0.01" id="res_037_paper_2" name="payload[res_037_paper_scores][]" value="{{ $paperScores[2] ?? '' }}" oninput="calcRes037()" onchange="calcRes037()" onkeyup="calcRes037()" class="w-full text-center rounded border border-gray-200 py-1.5 text-xs font-semibold focus:ring-1 focus:ring-emerald-500">
                    </td>
                    <td class="border border-gray-300 p-1 text-center bg-emerald-50/50">
                        <input type="text" id="res_037_paper_avg" name="payload[res_037_paper_average]" value="{{ $paperAverage }}" readonly class="w-full text-center bg-transparent py-1.5 font-black text-xs text-emerald-800 border-0">
                    </td>
                    <td class="border border-gray-300 p-1">
                        <textarea name="payload[res_037_paper_remarks]" rows="2" placeholder="Summary remarks..." class="w-full text-xs rounded border border-gray-200 p-1.5 resize-none focus:ring-1 focus:ring-emerald-500">{{ $payload['res_037_paper_remarks'] ?? '' }}</textarea>
                    </td>
                </tr>

                @for ($student = 1; $student <= 4; $student++)
                    @php
                        $stName = $studentsList[$student] ?? '';
                        $sScores = $studentScores[$student] ?? [];
                        $sAvg = $studentAverages[$student - 1] ?? '';
                    @endphp
                    <tr>
                        <th class="border border-gray-300 p-2 text-left bg-gray-50">
                            <span>II – Student {{ $student }} Presentation Evaluation</span><br>
                            <input name="payload[res_037_students][]" value="{{ $stName }}" placeholder="Student Researcher {{ $student }}"
                                class="mt-1 w-full rounded border border-gray-200 px-2 py-1 text-xs font-semibold {{ $stName ? 'bg-emerald-50/40' : '' }}">
                        </th>
                        <td class="border border-gray-300 p-1">
                            <input type="number" min="0" max="100" step="0.01" id="res_037_s_{{ $student }}_0" name="payload[res_037_student_scores][{{ $student }}][]" value="{{ $sScores[0] ?? '' }}" oninput="calcRes037()" onchange="calcRes037()" onkeyup="calcRes037()" class="w-full text-center rounded border border-gray-200 py-1.5 text-xs font-semibold focus:ring-1 focus:ring-emerald-500">
                        </td>
                        <td class="border border-gray-300 p-1">
                            <input type="number" min="0" max="100" step="0.01" id="res_037_s_{{ $student }}_1" name="payload[res_037_student_scores][{{ $student }}][]" value="{{ $sScores[1] ?? '' }}" oninput="calcRes037()" onchange="calcRes037()" onkeyup="calcRes037()" class="w-full text-center rounded border border-gray-200 py-1.5 text-xs font-semibold focus:ring-1 focus:ring-emerald-500">
                        </td>
                        <td class="border border-gray-300 p-1">
                            <input type="number" min="0" max="100" step="0.01" id="res_037_s_{{ $student }}_2" name="payload[res_037_student_scores][{{ $student }}][]" value="{{ $sScores[2] ?? '' }}" oninput="calcRes037()" onchange="calcRes037()" onkeyup="calcRes037()" class="w-full text-center rounded border border-gray-200 py-1.5 text-xs font-semibold focus:ring-1 focus:ring-emerald-500">
                        </td>
                        <td class="border border-gray-300 p-1 text-center bg-emerald-50/50">
                            <input type="text" id="res_037_s_avg_{{ $student }}" name="payload[res_037_student_averages][]" value="{{ $sAvg }}" readonly class="w-full text-center bg-transparent py-1.5 font-black text-xs text-emerald-800 border-0">
                        </td>
                        <td class="border border-gray-300 p-1">
                            <textarea name="payload[res_037_student_remarks][]" rows="2" placeholder="Student {{ $student }} remarks..." class="w-full text-xs rounded border border-gray-200 p-1.5 resize-none focus:ring-1 focus:ring-emerald-500">{{ $payload['res_037_student_remarks'][$student - 1] ?? '' }}</textarea>
                        </td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <div class="grid gap-8 pt-8 text-center md:grid-cols-2">
            <label class="block text-xs font-semibold text-left">Date Submitted:
                <input type="date" name="payload[res_037_submitted_at]" value="{{ $submittedAt }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs">
            </label>
            <x-official-signature-field name-field="payload[res_037_panelist_printed_name]" label="Submitted by" actor-type="panel_chair" academic-action="sign" :instance="$instance" />
        </div>
    </x-student-official-form>
</div>

<script>
function calcRes037() {
    // Part I Average
    const p0 = parseFloat(document.getElementById('res_037_paper_0')?.value);
    const p1 = parseFloat(document.getElementById('res_037_paper_1')?.value);
    const p2 = parseFloat(document.getElementById('res_037_paper_2')?.value);
    let validPaper = [];
    if (!isNaN(p0)) validPaper.push(p0);
    if (!isNaN(p1)) validPaper.push(p1);
    if (!isNaN(p2)) validPaper.push(p2);
    const paperAvgEl = document.getElementById('res_037_paper_avg');
    if (paperAvgEl) {
        paperAvgEl.value = validPaper.length > 0 ? (validPaper.reduce((a, b) => a + b, 0) / validPaper.length).toFixed(2) : '';
    }

    // Part II Student Averages
    for (let s = 1; s <= 4; s++) {
        const s0 = parseFloat(document.getElementById('res_037_s_' + s + '_0')?.value);
        const s1 = parseFloat(document.getElementById('res_037_s_' + s + '_1')?.value);
        const s2 = parseFloat(document.getElementById('res_037_s_' + s + '_2')?.value);
        let validS = [];
        if (!isNaN(s0)) validS.push(s0);
        if (!isNaN(s1)) validS.push(s1);
        if (!isNaN(s2)) validS.push(s2);
        const sAvgEl = document.getElementById('res_037_s_avg_' + s);
        if (sAvgEl) {
            sAvgEl.value = validS.length > 0 ? (validS.reduce((a, b) => a + b, 0) / validS.length).toFixed(2) : '';
        }
    }
}
document.addEventListener('DOMContentLoaded', calcRes037);
setTimeout(calcRes037, 100);
</script>
