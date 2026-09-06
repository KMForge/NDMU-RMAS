@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $class = $officialFormInstance?->researchClass ?? $officialFormInstance?->group?->researchClass;
    $groups = $class?->groups ?? collect();
    $facilitatorName = $class?->facilitator?->name ?? '';

    // Auto-generate entries from class groups if not already customized in payload
    $entries = $payload['entries'] ?? [];
    if (empty($entries) && $groups->isNotEmpty()) {
        foreach ($groups as $idx => $grp) {
            $entryNum = $idx + 1;
            $gTitle = $grp->researchGroup?->currentProject?->title 
                ?? $grp->titlePresentation?->approved_title 
                ?? '';
            
            // If the research group title is just a group name placeholder, leave blank
            if (empty($gTitle) && !empty($grp->researchGroup?->title) && !str_starts_with(strtolower($grp->researchGroup->title), 'group') && !str_starts_with(strtolower($grp->researchGroup->title), 'capstone group')) {
                $gTitle = $grp->researchGroup->title;
            }

            $gResearchers = $grp->members->map(fn($m) => $m->student?->name)->filter()->implode("\n");
            $entries[$entryNum] = [
                'title' => $gTitle,
                'researchers' => $gResearchers,
            ];
        }
    }
@endphp
<div x-show="activeOfficialForm === 'RES-041'" x-cloak>
    <x-student-official-form code="RES-Form-041" title="Endorsement of the Revised Research Proposals to the Program Coordinator" guidebook-page="126">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? now()->format('Y-m-d') }}"></label>
        <p>Dear Sir/Madam:</p>
        <p class="leading-7">
            This is to officially endorse the hard copies of the Revised Research Proposal papers of the students in
            <input name="payload[subject_number]" value="{{ $payload['subject_number'] ?? ($class?->name ?? 'ITCAP 102 - IT4A') }}" class="w-48 font-bold" placeholder="Subject No.">
            <input name="payload[descriptive_title]" value="{{ $payload['descriptive_title'] ?? 'Capstone Project and Research 1' }}" class="w-72" placeholder="Descriptive Title">.
        </p>
        <p>Below is the list of Research Titles and the corresponding student-researchers:</p>
        <table class="official-form-table text-xs">
            <thead>
                <tr>
                    <th class="w-1/2">Research Titles</th>
                    <th class="w-1/2">Student-Researchers</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 1; $i <= max(8, count($entries)); $i++)
                    <tr>
                        <td>
                            <input name="payload[entries][{{ $i }}][title]" value="{{ $entries[$i]['title'] ?? '' }}" class="w-full font-semibold" placeholder="Research Title {{ $i }}">
                        </td>
                        <td>
                            <textarea name="payload[entries][{{ $i }}][researchers]" class="min-h-12 w-full border-0 text-xs" placeholder="Student Researchers...">{{ $entries[$i]['researchers'] ?? '' }}</textarea>
                        </td>
                    </tr>
                @endfor
            </tbody>
        </table>
        <p>Sincerely yours,</p>
        <div class="ml-auto max-w-sm pt-8 text-center">
            <x-official-signature-field name-field="res_041_instructor_printed_name" label="Research Instructor" />
            <p class="mt-1 text-xs font-bold text-slate-800">{{ $facilitatorName }}</p>
        </div>
    </x-student-official-form>
</div>
