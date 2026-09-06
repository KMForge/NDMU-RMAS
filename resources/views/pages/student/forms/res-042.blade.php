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
    $programName = $payload['degree_program'] ?? $program?->name ?? $members->first()?->student?->program ?? 'Information Technology';
    $adviserName = $group?->adviser?->name ?? '';
@endphp
<div x-show="activeOfficialForm === 'RES-042'" x-cloak>
    <x-student-official-form code="RES-Form-042" title="Request for Research Instrument Validation" guidebook-page="127">
        <div class="flex justify-end">
            <label class="flex items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? now()->format('Y-m-d') }}"></label>
        </div>
        <p>Dear Sir/Madam:</p>
        <p class="leading-7">
            I/We, the undersigned, is/are
            <input value="{{ $joinedResearchers }}" class="min-w-64 font-bold" readonly placeholder="Names of Student Researchers">
            student/s currently enrolled in
            <input value="{{ $programName }}" class="min-w-64 font-bold" readonly placeholder="Degree Program">
            program of the College of
            <input value="Engineering, Architecture, and Computing" readonly class="min-w-72 font-semibold">,
            Notre Dame of Marbel University, City of Koronadal.
        </p>
        <p class="leading-7">
            I/We am/are presently conducting a research entitled
            <input value="{{ $currentResearchTitle }}" class="w-full text-center font-bold" readonly aria-label="Research Title">
            with descriptive title
            <input name="payload[descriptive_title]" value="{{ $payload['descriptive_title'] ?? 'Capstone Project and Research' }}" class="min-w-80 font-medium">
            as partial fulfillment of the course
            <input name="payload[course]" value="{{ $payload['course'] ?? ($class?->name ?? 'ITCAP 102') }}" class="min-w-52 font-medium">.
        </p>
        <p>The research instrument of the study is a researcher-made one which needs to be validated by experts.</p>
        <p>In view of the above, may I/we humbly ask a special favor from you by validating the content of my/our research instrument? I/We believe that your field of specialization and expertise in research can surely help me/us improve my/our survey instrument.</p>
        <p>Thank you very much.</p>
        <div class="grid gap-8 pt-10 md:grid-cols-2 text-center">
            <div>
                <x-official-signature-field label="Student Researcher" />
                <p class="mt-1 text-xs font-bold text-slate-800">{{ $members->first()?->student?->name ?? 'Lead Researcher' }}</p>
            </div>
            <div>
                <x-official-signature-field label="Noted by: Research Adviser" />
                <p class="mt-1 text-xs font-bold text-slate-800">{{ $adviserName }}</p>
            </div>
        </div>
    </x-student-official-form>
</div>
