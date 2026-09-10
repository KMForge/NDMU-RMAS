@php
    use App\Modules\Evaluations\Services\Res036Rubric;
    $instance = $officialFormInstance ?? null;
    $group = $instance?->group;
    $schedule = $instance?->source instanceof \App\Models\DefenseSchedule ? $instance->source : null;
    $snapshot = $instance?->currentVersion?->source_snapshot ?? [];
    $defense = $schedule?->defense;
    $defenseTypeRaw = $snapshot['defense_type'] ?? $defense?->defense_type ?? $payload['res_036_defense_type'] ?? '';
    $currentDefenseType = in_array($defenseTypeRaw, ['proposal_defense', 'title_proposal', 'proposal'], true)
        ? 'proposal'
        : (in_array($defenseTypeRaw, ['pre_final_defense', 'pre_final', 'pre-final'], true) ? 'pre_final' : 'final');
    $currentDate = isset($snapshot['starts_at']) ? \Carbon\CarbonImmutable::parse($snapshot['starts_at'])->format('Y-m-d') : ($schedule?->starts_at?->format('Y-m-d') ?? now()->format('Y-m-d'));
    $currentTime = isset($snapshot['starts_at']) ? \Carbon\CarbonImmutable::parse($snapshot['starts_at'])->format('H:i') : ($schedule?->starts_at?->format('H:i') ?? '');
    $currentVenue = $snapshot['room_name'] ?? $snapshot['room_code'] ?? $schedule?->room?->name ?? $schedule?->room?->code ?? '';
    $currentResearchTitle = $snapshot['research_title'] ?? $group?->researchGroup?->currentProject?->title ?? '';
    $panelistName = auth()->user()?->name ?? 'Panel Member';
    $signedAt = now()->format('Y-m-d');
    $programCode = $snapshot['program_code'] ?? $instance?->defenseEvaluation?->round?->program_code ?? app(Res036Rubric::class)->resolveProgramCode($group);
    $discipline = $programCode === 'BSIT' ? 'information technology' : ($programCode === 'BSCS' ? 'computer science' : 'computing');
    $paperScores = $payload['res_036_paper_scores'] ?? $instance?->defenseEvaluation?->paper_criterion_scores ?? [];
    $paperRows = [
        'relevance_of_topic' => ['Relevance of Topic', 20, "Clear alignment with current trends and challenges in {$discipline}."],
        'literature_review_background' => ['Literature Review and Background', 15, 'Comprehensive review of related research, demonstrating knowledge of the field.'],
        'problem_definition_objectives' => ['Problem Definition and Objectives', 15, 'Well-defined problem statement with clear, achievable objectives.'],
        'technical_depth_innovation' => ['Technical Depth and Innovation', 20, 'Evidence of technical rigor and innovation in the proposed approach.'],
        'methodology_feasibility' => ['Methodology and Feasibility', 20, 'Clear, and appropriate research methods and procedures. Feasibility in terms of resources, time, and available technology.'],
        'expected_outcomes_contributions' => ['Expected Outcomes and Contributions', 10, "Demonstrates potential for practical or theoretical advancement in {$discipline}."],
    ];
    $presentationRows = [
        ['Communication Skills', 'voice_projection_pronunciation', 'Voice Projection/Pronunciation', 10],
        [null, 'grammar_sentence_structure', 'Grammar/Sentence Structure', 10],
        ['Work Organization', 'assigned_topic_clarity', 'Ability to present the assigned topic clearly', 15],
        [null, 'participation_in_defense', 'Participation in the defense', 15],
        ['Effectiveness', 'ability_to_answer_questions', 'Ability to answer questions', 20],
        [null, 'mastery_of_study_details', 'Mastery of the details of the study', 15],
        [null, 'ability_to_convince_panelists', 'Ability to convince the panelists of the ideas being presented', 15],
    ];

    $presentersData = $payload['res_036_presenters'] ?? [];
    if (!empty($snapshot['presenters'])) {
        $submittedPresenters = array_values($presentersData);
        $presentersData = [];
        foreach (array_values($snapshot['presenters']) as $index => $presenter) {
            $presentersData[$index + 1] = [
                'student_id' => $presenter['student_id'],
                'name' => $presenter['name'],
                'scores' => $submittedPresenters[$index]['scores'] ?? [],
            ];
        }
    }
    if (empty($presentersData) && $group && $group->members) {
        $idx = 1;
        foreach ($group->members as $m) {
            $presentersData[$idx++] = [
                'student_id' => $m->student_id,
                'name' => $m->student?->name ?? '',
                'scores' => [],
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
                <input type="radio" name="payload[res_036_defense_type]" value="pre_final" {{ $currentDefenseType === 'pre_final' ? 'checked' : '' }}>
                <span>Research Pre-Final Defense</span>
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

        @if (!$programCode)<p class="rounded border border-amber-300 bg-amber-50 p-2 font-sans text-xs">The group program is not configured; neutral computing wording is shown.</p>@endif
        <h3 class="font-bold text-sm">I - Research Paper Evaluation</h3>
        <table class="official-form-table text-[11px] w-full border-collapse leading-tight">
            <thead><tr><th>CRITERIA</th><th class="w-24">SCORE</th></tr></thead>
            <tbody>
                @foreach ($paperRows as $key => [$label, $maximum, $description])
                    <tr><td><strong>{{ $label }} ({{ $maximum }}%)</strong><br><em class="pl-8">{{ $description }}</em></td><td><input type="number" min="0" max="{{ $maximum }}" step="0.01" class="res036-paper-score w-full text-center" name="payload[res_036_paper_scores][{{ $key }}]" value="{{ $paperScores[$key] ?? '' }}"></td></tr>
                @endforeach
                <tr class="font-bold"><td>TOTAL (100%)</td><td class="text-center"><output id="res036-paper-total">0.00</output></td></tr>
            </tbody>
        </table>
        <h3 class="font-bold text-sm pt-2">II - Student's Presentation Evaluation</h3>
        <div class="overflow-x-auto"><table class="official-form-table text-[10px] w-full border-collapse leading-tight">
            <thead>
                <tr><th rowspan="2" class="w-[48%]">CRITERIA</th><th colspan="{{ max(1, count($presentersData)) }}">NAME OF STUDENT PRESENTERS</th></tr>
                <tr>
                    @forelse ($presentersData as $presenter)
                        <th>{{ $presenter['name'] ?? '' }}</th>
                    @empty
                        <th>No frozen presenter roster</th>
                    @endforelse
                </tr>
            </thead>
            <tbody>
                @foreach ($presentationRows as $rowIndex => [$section, $key, $label, $maximum])
                    @if ($section)
                        <tr class="font-bold"><td colspan="{{ max(2, count($presentersData) + 1) }}">{{ $section }} ({{ $section === 'Communication Skills' ? 20 : ($section === 'Work Organization' ? 30 : 50) }}%)</td></tr>
                    @endif
                    <tr>
                        <td>{{ ($rowIndex % 2) + 1 }}. {{ $label }} - ({{ $maximum }}%)</td>
                        @forelse ($presentersData as $index => $presenter)
                            @php
                                $studentKey = $presenter['student_id'] ?? $index;
                            @endphp
                            <td><input type="number" min="0" max="{{ $maximum }}" step="0.01" class="res036-student-score w-full text-center" data-student="{{ $studentKey }}" name="payload[res_036_presenters][{{ $index }}][scores][{{ $key }}]" value="{{ $presenter['scores'][$key] ?? '' }}"></td>
                        @empty
                            <td></td>
                        @endforelse
                    </tr>
                @endforeach
                <tr class="font-bold"><td>TOTAL (100%)</td>
                    @foreach ($presentersData as $index => $presenter)
                        <td class="text-center"><output class="res036-student-total" data-student="{{ $presenter['student_id'] ?? $index }}">0.00</output></td>
                    @endforeach
                </tr>
            </tbody>
        </table></div>
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
    const detailedPaper = [...document.querySelectorAll('.res036-paper-score')];
    const detailedPaperTotal = detailedPaper.reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
    const detailedPaperOutput = document.getElementById('res036-paper-total');
    if (detailedPaperOutput) detailedPaperOutput.value = detailedPaperTotal.toFixed(2);

    document.querySelectorAll('.res036-student-total').forEach(output => {
        const student = output.dataset.student;
        const scores = [...document.querySelectorAll('.res036-student-score')].filter(input => input.dataset.student === student);
        output.value = scores.reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0).toFixed(2);
    });

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
