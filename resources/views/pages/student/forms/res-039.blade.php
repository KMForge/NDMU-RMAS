@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $group = $officialFormInstance?->group;
    $class = $officialFormInstance?->researchClass ?? $group?->researchClass;

    // 1. Research Title
    $currentResearchTitle = $payload['research_title']
        ?? $payload['title']
        ?? $group?->researchGroup?->currentProject?->title
        ?? $group?->titlePresentation?->approved_title
        ?? '';
    $currentResearchTitleLine2 = $payload['research_title_line2'] ?? '';

    // 2. Researchers (up to 4)
    $members = $group?->members?->values() ?? collect();
    $memberStudentNames = $members->map(fn ($m) => $m->student?->name)->filter()->values();
    $savedResearchers = $payload['researchers'] ?? [];
    if (is_string($savedResearchers)) {
        $savedResearchers = array_map('trim', explode(',', $savedResearchers));
    }
    $researcher1 = $savedResearchers[0] ?? $memberStudentNames->get(0) ?? '';
    $researcher2 = $savedResearchers[1] ?? $memberStudentNames->get(1) ?? '';
    $researcher3 = $savedResearchers[2] ?? $memberStudentNames->get(2) ?? '';
    $researcher4 = $savedResearchers[3] ?? $memberStudentNames->get(3) ?? '';

    // 3. Course
    $program = $members->first()?->student?->studentProfile?->program;
    $courseName = $payload['course']
        ?? $program?->code
        ?? $program?->name
        ?? $members->first()?->student?->program
        ?? ($class?->name ?? '');

    // 4. Panel Chairman and Panel Members (up to 4)
    $panelCommittee = $group?->panelCommittees?->first() ?? $class?->panelCommittees?->first();
    if (! $panelCommittee && $group) {
        $panelCommittee = \App\Models\ResearchGroupPanelCommittee::with(['chairperson', 'members.user'])
            ->where('research_class_group_id', $group->id)
            ->latest('id')
            ->first()
            ?? \App\Models\ResearchClassPanelCommittee::with(['chairperson', 'members.user'])
            ->where('research_class_id', $group->research_class_id)
            ->latest('id')
            ->first();
    }
    $defenseSchedule = $group?->defenses()->with(['currentSchedule.room', 'activePanelAssignments.user'])->latest('id')->first();
    $panelAssignments = $defenseSchedule?->activePanelAssignments ?? collect();

    $panelChairName = $payload['panel_chair']
        ?? $panelCommittee?->chairperson?->name
        ?? $panelAssignments->firstWhere('role', 'chairperson')?->user?->name
        ?? '';

    $savedPanelMembers = $payload['panel_members'] ?? [];
    if (is_string($savedPanelMembers)) {
        $savedPanelMembers = array_map('trim', explode(',', $savedPanelMembers));
    }
    $committeeMemberNames = $panelCommittee?->members?->map(fn ($m) => $m->user?->name)->filter()->values()
        ?? $panelAssignments->where('role', '!=', 'chairperson')->map(fn ($a) => $a->user?->name)->filter()->values();

    $panelMember1 = $savedPanelMembers[0] ?? $committeeMemberNames->get(0) ?? '';
    $panelMember2 = $savedPanelMembers[1] ?? $committeeMemberNames->get(1) ?? '';
    $panelMember3 = $savedPanelMembers[2] ?? $committeeMemberNames->get(2) ?? '';
    $panelMember4 = $savedPanelMembers[3] ?? $committeeMemberNames->get(3) ?? '';

    // 5. Research Adviser
    $adviserName = $payload['adviser'] ?? $group?->adviser?->name ?? '';

    // 6. Source linking context
    $source = $officialFormInstance?->source;
    $isDocReview = $source instanceof \App\Models\DocumentReview;
    $isRevRequest = $source instanceof \App\Models\RevisionRequest;

    // 7. Defense Stage Selection
    $isProposalDefense = ! empty($payload['defense_stage_proposal'])
        || (isset($payload['defense_stage']) && in_array($payload['defense_stage'], ['proposal', 'proposal_defense'], true))
        || (isset($payload['recommendation']) && $payload['recommendation'] === 'proposal');

    $isPreFinalDefense = ! empty($payload['defense_stage_pre_final'])
        || (isset($payload['defense_stage']) && in_array($payload['defense_stage'], ['pre_final', 'pre_final_defense'], true))
        || (isset($payload['recommendation']) && $payload['recommendation'] === 'pre_final');

    $isFinalDefense = ! empty($payload['defense_stage_final'])
        || (isset($payload['defense_stage']) && in_array($payload['defense_stage'], ['final', 'final_oral_defense', 'final_defense'], true))
        || (isset($payload['recommendation']) && $payload['recommendation'] === 'final');

    $dateReviewed = $payload['date_reviewed'] ?? $payload['date'] ?? now()->format('Y-m-d');

    // 8. Sections and Matrix Rows Normalization
    $sections = [
        'title' => 'Title',
        'introduction' => 'Introduction',
        'method' => 'Method',
        'results' => 'Results',
        'discussion' => 'Discussion',
        'references' => 'References',
        'others' => 'Others',
    ];

    $aliases = [
        'methodology' => 'method',
        'methods' => 'method',
        'results and discussion' => 'results',
        'background' => 'introduction',
        'intro' => 'introduction',
    ];

    $rawRevisions = $payload['revisions'] ?? [];
    $sectionRows = [];

    foreach ($sections as $secKey => $secLabel) {
        $rows = [];

        // Check if keyed by section key or alias
        $direct = $rawRevisions[$secKey] ?? null;
        if (! $direct) {
            foreach ($aliases as $aliasKey => $targetKey) {
                if ($targetKey === $secKey && isset($rawRevisions[$aliasKey])) {
                    $direct = $rawRevisions[$aliasKey];
                    break;
                }
            }
        }

        if (is_array($direct)) {
            if (array_is_list($direct)) {
                $rows = $direct;
            } else {
                $rows = [$direct];
            }
        }

        // Check if raw revisions has numeric entries with matching area name
        if (is_array($rawRevisions)) {
            foreach ($rawRevisions as $idx => $entry) {
                if (is_int($idx) && is_array($entry)) {
                    $entryArea = strtolower(trim((string) ($entry['area'] ?? '')));
                    $entryTarget = $aliases[$entryArea] ?? $entryArea;
                    if ($entryTarget === $secKey || $entryArea === strtolower($secLabel)) {
                        $rows[] = $entry;
                    }
                }
            }
        }

        // Provide initial default rows if empty (2 for main sections, 1 for references/others)
        if (empty($rows)) {
            $initialCount = in_array($secKey, ['title', 'introduction', 'method', 'results', 'discussion'], true) ? 2 : 1;
            for ($i = 0; $i < $initialCount; $i++) {
                $rows[] = [
                    'suggestions' => '',
                    'recommended_by' => '',
                    'revision_made' => '',
                    'pages' => '',
                    'approval' => '',
                ];
            }
        }

        $sectionRows[$secKey] = $rows;
    }
@endphp

<div x-show="activeOfficialForm === 'RES-039'" x-cloak>
    <x-student-official-form code="RES Form 039" title="RESEARCH REVISION CHART" guidebook-page="123">
        {{-- Research Title Block --}}
        <div class="space-y-1.5 text-xs">
            <div class="flex items-end gap-2">
                <span class="font-bold text-slate-900 shrink-0">Research Title:</span>
                <input
                    type="text"
                    name="payload[research_title]"
                    value="{{ $currentResearchTitle }}"
                    class="w-full border-0 border-b border-black text-xs font-semibold text-slate-900 focus:border-b-2 focus:border-[#0e5c3a]"
                    placeholder="Enter full research title..."
                >
            </div>
            <div class="flex items-end pl-[90px]">
                <input
                    type="text"
                    name="payload[research_title_line2]"
                    value="{{ $currentResearchTitleLine2 }}"
                    class="w-full border-0 border-b border-black text-xs font-semibold text-slate-900 focus:border-b-2 focus:border-[#0e5c3a]"
                    placeholder="Title continuation (if applicable)..."
                >
            </div>
        </div>

        {{-- Researchers (Numbered 1-4) --}}
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2 text-xs">
            <div>
                <span class="font-bold text-slate-900 block mb-1">Researchers:</span>
                <div class="space-y-2 pl-2">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-800 shrink-0">1)</span>
                        <input
                            type="text"
                            name="payload[researchers][0]"
                            value="{{ $researcher1 }}"
                            class="w-full border-0 border-b border-black text-xs text-slate-900"
                            placeholder="Researcher Name"
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-800 shrink-0">2)</span>
                        <input
                            type="text"
                            name="payload[researchers][1]"
                            value="{{ $researcher2 }}"
                            class="w-full border-0 border-b border-black text-xs text-slate-900"
                            placeholder="Researcher Name"
                        >
                    </div>
                </div>
            </div>

            <div>
                <span class="font-bold text-slate-900 block mb-1 hidden sm:block">&nbsp;</span>
                <div class="space-y-2 pl-2">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-800 shrink-0">3)</span>
                        <input
                            type="text"
                            name="payload[researchers][2]"
                            value="{{ $researcher3 }}"
                            class="w-full border-0 border-b border-black text-xs text-slate-900"
                            placeholder="Researcher Name"
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-800 shrink-0">4)</span>
                        <input
                            type="text"
                            name="payload[researchers][3]"
                            value="{{ $researcher4 }}"
                            class="w-full border-0 border-b border-black text-xs text-slate-900"
                            placeholder="Researcher Name"
                        >
                    </div>
                </div>
            </div>
        </div>

        {{-- Course --}}
        <div class="mt-3 flex items-center gap-2 text-xs">
            <span class="font-bold text-slate-900 shrink-0">Course:</span>
            <input
                type="text"
                name="payload[course]"
                value="{{ $courseName }}"
                class="w-full max-w-lg border-0 border-b border-black text-xs text-slate-900 font-medium"
                placeholder="e.g. BS Information Technology / BS Computer Science"
            >
        </div>

        {{-- Chairman of the Panel --}}
        <div class="mt-3 flex items-center gap-2 text-xs">
            <span class="font-bold text-slate-900 shrink-0">Chairman of the Panel:</span>
            <input
                type="text"
                name="payload[panel_chair]"
                value="{{ $panelChairName }}"
                class="w-full max-w-lg border-0 border-b border-black text-xs text-slate-900 font-medium"
                placeholder="Panel Chairman Name"
            >
        </div>

        {{-- Panel Members (Numbered 1-4) --}}
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2 text-xs">
            <div>
                <span class="font-bold text-slate-900 block mb-1">Panel Members:</span>
                <div class="space-y-2 pl-2">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-800 shrink-0">1)</span>
                        <input
                            type="text"
                            name="payload[panel_members][0]"
                            value="{{ $panelMember1 }}"
                            class="w-full border-0 border-b border-black text-xs text-slate-900"
                            placeholder="Panel Member Name"
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-800 shrink-0">2)</span>
                        <input
                            type="text"
                            name="payload[panel_members][1]"
                            value="{{ $panelMember2 }}"
                            class="w-full border-0 border-b border-black text-xs text-slate-900"
                            placeholder="Panel Member Name"
                        >
                    </div>
                </div>
            </div>

            <div>
                <span class="font-bold text-slate-900 block mb-1 hidden sm:block">&nbsp;</span>
                <div class="space-y-2 pl-2">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-800 shrink-0">3)</span>
                        <input
                            type="text"
                            name="payload[panel_members][2]"
                            value="{{ $panelMember3 }}"
                            class="w-full border-0 border-b border-black text-xs text-slate-900"
                            placeholder="Panel Member Name"
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-800 shrink-0">4)</span>
                        <input
                            type="text"
                            name="payload[panel_members][3]"
                            value="{{ $panelMember4 }}"
                            class="w-full border-0 border-b border-black text-xs text-slate-900"
                            placeholder="Panel Member Name"
                        >
                    </div>
                </div>
            </div>
        </div>

        {{-- Research Adviser --}}
        <div class="mt-3 flex items-center gap-2 text-xs">
            <span class="font-bold text-slate-900 shrink-0">Research Adviser:</span>
            <input
                type="text"
                name="payload[adviser]"
                value="{{ $adviserName }}"
                class="w-full max-w-lg border-0 border-b border-black text-xs text-slate-900 font-medium"
                placeholder="Research Adviser Name"
            >
        </div>

        {{-- Contextual Source Alert (DocumentReview or RevisionRequest Linkage) --}}
        @if ($isDocReview && $source->review_notes)
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs mt-3 no-print">
                <p class="font-bold text-[#0e5c3a]">Source Review Notes ({{ str($source->decision)->headline() }}):</p>
                <p class="mt-1 text-gray-700">{{ $source->review_notes }}</p>
            </div>
        @elseif ($isRevRequest && $source->instructions)
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs mt-3 no-print">
                <p class="font-bold text-[#0e5c3a]">Source Revision Instructions ({{ $source->title }}):</p>
                <p class="mt-1 text-gray-700">{{ $source->instructions }}</p>
            </div>
        @endif

        {{-- Revision Matrix Table (5 Columns) with Alpine dynamic rows --}}
        <div class="mt-5" x-data="{
            sections: @js($sectionRows),
            addRow(key) {
                this.sections[key].push({
                    suggestions: '',
                    recommended_by: '',
                    revision_made: '',
                    pages: '',
                    approval: ''
                });
            },
            removeRow(key, index) {
                if (this.sections[key].length > 1) {
                    this.sections[key].splice(index, 1);
                } else {
                    this.sections[key][0] = {
                        suggestions: '',
                        recommended_by: '',
                        revision_made: '',
                        pages: '',
                        approval: ''
                    };
                }
            }
        }">
            <table class="official-form-table w-full border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-100 text-center font-bold text-slate-900">
                        <th class="border border-black p-2 w-[34%]">Suggestion and Recommendations</th>
                        <th class="border border-black p-2 w-[18%]">Recommended by:</th>
                        <th class="border border-black p-2 w-[26%]">Revision Made</th>
                        <th class="border border-black p-2 w-[10%]">New Page/s</th>
                        <th class="border border-black p-2 w-[12%]">Approval (Signature)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sections as $areaKey => $areaLabel)
                        {{-- Full-width Section Category Row --}}
                        <tr class="bg-slate-50">
                            <td colspan="5" class="border border-black px-2 py-1 font-bold text-slate-900">
                                <div class="flex items-center justify-between">
                                    <span>{{ $areaLabel }}</span>
                                    <button
                                        type="button"
                                        @click="addRow('{{ $areaKey }}')"
                                        class="no-print text-[10px] font-bold text-emerald-800 hover:text-emerald-950 px-2 py-0.5 rounded bg-emerald-100/70 hover:bg-emerald-200 transition cursor-pointer"
                                        title="Add bullet item under {{ $areaLabel }}"
                                    >
                                        + Add Row
                                    </button>
                                </div>
                            </td>
                        </tr>

                        {{-- Section Item Rows --}}
                        <template x-for="(row, index) in sections['{{ $areaKey }}']" :key="'{{ $areaKey }}-' + index">
                            <tr>
                                <td class="border border-black p-1.5 align-top">
                                    <div class="flex items-start gap-1">
                                        <span class="font-bold text-slate-700 select-none mt-0.5">•</span>
                                        <textarea
                                            :name="`payload[revisions][{{ $areaKey }}][${index}][suggestions]`"
                                            x-model="row.suggestions"
                                            rows="2"
                                            class="w-full border-0 text-xs p-1 text-slate-900 focus:ring-0 resize-y bg-transparent"
                                            placeholder="Suggestion / Recommendation..."
                                        ></textarea>
                                    </div>
                                </td>
                                <td class="border border-black p-1.5 align-top">
                                    <input
                                        type="text"
                                        :name="`payload[revisions][{{ $areaKey }}][${index}][recommended_by]`"
                                        x-model="row.recommended_by"
                                        class="w-full border-0 text-xs p-1 text-slate-900 focus:ring-0 bg-transparent"
                                        placeholder="Panelist name"
                                    >
                                </td>
                                <td class="border border-black p-1.5 align-top">
                                    <textarea
                                        :name="`payload[revisions][{{ $areaKey }}][${index}][revision_made]`"
                                        x-model="row.revision_made"
                                        rows="2"
                                        class="w-full border-0 text-xs p-1 text-slate-900 focus:ring-0 resize-y bg-transparent"
                                        placeholder="Revision made..."
                                    ></textarea>
                                </td>
                                <td class="border border-black p-1.5 align-top text-center">
                                    <input
                                        type="text"
                                        :name="`payload[revisions][{{ $areaKey }}][${index}][pages]`"
                                        x-model="row.pages"
                                        class="w-full border-0 text-xs p-1 text-center text-slate-900 focus:ring-0 bg-transparent"
                                        placeholder="e.g. 15-18"
                                    >
                                </td>
                                <td class="border border-black p-1.5 align-top text-center relative">
                                    <input
                                        type="text"
                                        :name="`payload[revisions][{{ $areaKey }}][${index}][approval]`"
                                        x-model="row.approval"
                                        class="w-full border-0 text-xs p-1 text-center text-slate-900 focus:ring-0 bg-transparent"
                                        placeholder="Sign / Initial"
                                    >
                                    <button
                                        type="button"
                                        x-show="sections['{{ $areaKey }}'].length > 1"
                                        @click="removeRow('{{ $areaKey }}', index)"
                                        class="no-print absolute top-1 right-1 text-rose-500 hover:text-rose-700 text-xs font-black p-0.5 rounded cursor-pointer leading-none"
                                        title="Remove row"
                                    >
                                        &times;
                                    </button>
                                </td>
                            </tr>
                        </template>

                        {{-- Fallback for non-JS / test assertion server-side rendering --}}
                        <noscript>
                            @foreach ($sectionRows[$areaKey] as $index => $row)
                                <tr>
                                    <td class="border border-black p-1.5 align-top">
                                        <div class="flex items-start gap-1">
                                            <span class="font-bold text-slate-700">•</span>
                                            <textarea name="payload[revisions][{{ $areaKey }}][{{ $index }}][suggestions]" rows="2" class="w-full border-0 text-xs p-1">{{ $row['suggestions'] ?? '' }}</textarea>
                                        </div>
                                    </td>
                                    <td class="border border-black p-1.5 align-top">
                                        <input type="text" name="payload[revisions][{{ $areaKey }}][{{ $index }}][recommended_by]" value="{{ $row['recommended_by'] ?? '' }}" class="w-full border-0 text-xs p-1">
                                    </td>
                                    <td class="border border-black p-1.5 align-top">
                                        <textarea name="payload[revisions][{{ $areaKey }}][{{ $index }}][revision_made]" rows="2" class="w-full border-0 text-xs p-1">{{ $row['revision_made'] ?? '' }}</textarea>
                                    </td>
                                    <td class="border border-black p-1.5 align-top text-center">
                                        <input type="text" name="payload[revisions][{{ $areaKey }}][{{ $index }}][pages]" value="{{ $row['pages'] ?? '' }}" class="w-full border-0 text-xs p-1 text-center">
                                    </td>
                                    <td class="border border-black p-1.5 align-top text-center">
                                        <input type="text" name="payload[revisions][{{ $areaKey }}][{{ $index }}][approval]" value="{{ $row['approval'] ?? '' }}" class="w-full border-0 text-xs p-1 text-center">
                                    </td>
                                </tr>
                            @endforeach
                        </noscript>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Bottom Review Certification & Defense Stage Checkboxes --}}
        <div class="mt-6 space-y-3 text-xs text-slate-900">
            <p class="leading-relaxed text-justify">
                The revisions enumerated above were thoroughly reviewed and found to be in coherence with the suggestions and recommendations of the panel of examiners during:
            </p>

            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-8 py-1 pl-4 font-bold">
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="payload[defense_stage_proposal]"
                        value="1"
                        {{ $isProposalDefense ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-gray-400 text-[#0e5c3a] focus:ring-[#0e5c3a]"
                    >
                    <span>Research Proposal Defense</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="payload[defense_stage_pre_final]"
                        value="1"
                        {{ $isPreFinalDefense ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-gray-400 text-[#0e5c3a] focus:ring-[#0e5c3a]"
                    >
                    <span>Research Pre-Final Defense</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="payload[defense_stage_final]"
                        value="1"
                        {{ $isFinalDefense ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-gray-400 text-[#0e5c3a] focus:ring-[#0e5c3a]"
                    >
                    <span>Research Final Oral Defense</span>
                </label>
            </div>

            {{-- Reviewer & Date Sign-off Block --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-10 pt-8 items-end">
                <div>
                    <p class="font-bold text-xs mb-3 text-slate-800">Reviewed by:</p>
                    <div class="space-y-1">
                        @php
                            $hasAdviserSignature = $officialFormInstance?->currentVersion?->signatures?->contains(
                                fn ($sig) => in_array($sig->actor_type, ['adviser', 'research_adviser'], true)
                            );
                        @endphp
                        @if ($hasAdviserSignature)
                            <x-official-signature-field
                                label="Research Adviser (Name & Signature)"
                                actor-type="adviser"
                                academic-action="sign"
                                :instance="$officialFormInstance"
                            />
                        @else
                            <div class="border-b border-black pb-1 font-bold text-xs text-center text-slate-900 min-h-[1.5rem]">
                                {{ $adviserName }}
                            </div>
                            <p class="text-[11px] text-slate-700 text-center font-medium mt-1">Research Adviser (Name & Signature)</p>
                        @endif
                    </div>
                </div>

                <div>
                    <div class="flex items-end gap-2">
                        <span class="font-bold text-xs text-slate-800 shrink-0">Date Reviewed:</span>
                        <input
                            type="date"
                            name="payload[date_reviewed]"
                            value="{{ $dateReviewed }}"
                            class="w-full border-0 border-b border-black text-xs font-semibold text-center text-slate-900 focus:border-b-2 focus:border-[#0e5c3a]"
                        >
                    </div>
                </div>
            </div>
        </div>
    </x-student-official-form>
</div>
