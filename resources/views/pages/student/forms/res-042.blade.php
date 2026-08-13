@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
@endphp
<div x-show="activeOfficialForm === 'RES-042'" x-cloak><x-student-official-form code="RES-Form-042" title="Request for Research Instrument Validation" guidebook-page="127">
    <div class="flex justify-end"><label>Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? '' }}"></label></div>
    <p>Dear Sir/Madame:</p>
    <p class="leading-7">I/We, the undersigned, is/are <input value="{{ $officialFormInstance?->group?->members?->pluck('student.name')->filter()->join(', ') }}" class="min-w-48" readonly> student/s currently enrolled in <input value="{{ $officialFormInstance?->group?->researchClass?->name }}" class="min-w-48" readonly> program, major in <input placeholder="Major" class="min-w-40" readonly> of the College of <input value="Engineering, Architecture, and Computing" readonly class="min-w-72">, Notre Dame of Marbel University, City of Koronadal.</p>
    <p class="leading-7">I/We am/are presently conducting a research entitled <input value="{{ $officialFormInstance?->group?->title }}" class="min-w-96" readonly> with descriptive title <input name="payload[descriptive_title]" value="{{ $payload['descriptive_title'] ?? '' }}" class="min-w-80"> as partial fulfillment of the course <input name="payload[course]" value="{{ $payload['course'] ?? '' }}" class="min-w-52">.</p>
    <p>The research instrument of the study is a researcher-made one which needs to be validated by experts.</p><p>In view of the above, may I/we humbly ask a special favor from you by validating the content of my/our research instrument? I/We believe that your field of specialization and expertise in research can surely help me/us improve my/our survey instrument.</p><p>Thank you very much.</p>
    <div class="grid gap-8 pt-10 md:grid-cols-2"><x-official-signature-field label="Student Researcher" /><x-official-signature-field label="Noted by: Research Adviser" /></div>
</x-student-official-form></div>
