@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $titlePresentation = $officialFormInstance?->titlePresentation;
    $panelByPosition = $titlePresentation?->defense?->activePanelAssignments?->keyBy('panel_position') ?? collect();
@endphp
<div x-show="activeOfficialForm === 'RES-026'" x-cloak><x-student-official-form code="RES-Form-026" title="Research Title Approval" guidebook-page="101">
        <div class="res-026-content space-y-2 text-[11px] leading-tight">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div class="max-w-[70%] border border-[#173c30] px-2 py-1.5 text-[9px] leading-tight">
                <strong>Requirements for Research Title Proposal:</strong><br>
                • Three (3) proposed research titles<br>
                • Each title must contain the following: Background/Rationale (at least 150 words); Objectives/Statement of the Problem; and Brief Methodology Plan (Research Design, Locale, Respondents, and Sample Size).
            </div>
            <label class="flex items-center gap-2 whitespace-nowrap">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? '' }}"></label>
        </div>
        <fieldset><legend class="mb-1 font-bold">Name of Student/s:</legend><div class="grid grid-cols-1 gap-x-6 gap-y-1 md:grid-cols-2">@for ($i = 1; $i <= 4; $i++)<label class="flex gap-2"><span>{{ $i }}.</span><input type="text" value="{{ $officialFormInstance?->group?->members?->values()?->get($i - 1)?->student?->name }}" class="min-w-0 flex-1" readonly></label>@endfor</div></fieldset>
        <fieldset><legend class="mb-1 font-bold">Proposed Research Topics:</legend><div class="space-y-1">@for ($i = 1; $i <= 3; $i++)<label class="flex items-start gap-2"><span>{{ $i }}.</span><textarea class="min-h-10 w-full" name="payload[topics][]">{{ $payload['topics'][$i - 1] ?? '' }}</textarea></label>@endfor</div></fieldset>
        <label class="flex items-center gap-2 font-bold">Approved Research Title No. <input class="w-24" value="{{ $titlePresentation?->approved_title_number }}" readonly></label>
        <label class="block font-bold">Remarks:<textarea class="mt-1 min-h-16 w-full" readonly>{{ $titlePresentation?->remarks }}</textarea></label>
        <div class="res-026-panel-signatures grid grid-cols-[minmax(0,1fr)_minmax(0,.75fr)] items-center gap-x-5 gap-y-1.5 pt-1">
            <div class="border-b border-[#173c30] pb-1 font-bold">Name</div><div class="border-b border-[#173c30] pb-1 text-center font-bold">Signature</div>
            <div class="col-span-2 font-bold">Panel of Examiners:</div>
            @foreach ([
                ['label' => 'Chairman', 'position' => 'chairperson', 'actor' => 'title_panel_chairperson', 'action' => 'sign_chairperson'],
                ['label' => 'Member 1', 'position' => 'member_1', 'actor' => 'title_panel_member_1', 'action' => 'sign_member_1'],
                ['label' => 'Member 2', 'position' => 'member_2', 'actor' => 'title_panel_member_2', 'action' => 'sign_member_2'],
            ] as $panelRole)
                <div class="min-w-0">
                    <div class="min-h-7 border-b border-[#173c30] px-1 pt-1 font-medium">{{ $panelByPosition->get($panelRole['position'])?->user?->name }}</div>
                    <span class="text-[10px] italic">{{ $panelRole['label'] }}</span>
                </div>
                <div class="res-026-panel-signature"><x-official-signature-field :instance="$officialFormInstance" :label="$panelRole['label'].' signature'" :actor-type="$panelRole['actor']" :academic-action="$panelRole['action']" /></div>
            @endforeach
        </div>
        <div class="official-signature-row grid grid-cols-2 gap-6 pt-2 text-center"><x-official-signature-field :instance="$officialFormInstance" actor-type="program_coordinator" academic-action="endorse" name-field="program_coordinator_name" label="Noted: Program Coordinator (Name & Signature)" /><x-official-signature-field :instance="$officialFormInstance" actor-type="dean" academic-action="approve" name-field="college_dean_name" label="College Dean (Name & Signature)" /></div>
        </div>
    </x-student-official-form></div>
