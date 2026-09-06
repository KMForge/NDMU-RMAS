@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $group = $officialFormInstance?->group;
    $class = $officialFormInstance?->researchClass ?? $group?->researchClass;
    $resolver = app(\App\Modules\OfficialForms\Services\InstitutionalActorResolver::class);

    $currentResearchTitle = $payload['research_title']
        ?? $payload['title']
        ?? $group?->researchGroup?->currentProject?->title
        ?? $group?->titlePresentation?->approved_title
        ?? '';

    $members = $group?->members?->values() ?? collect();
    $adviserName = $group?->adviser?->name ?? '';
    $dean = $resolver->dean();
    $deanName = $dean?->name ?? 'Dr. Lourdes Castillo';
@endphp
<div x-show="activeOfficialForm === 'RES-047'" x-cloak>
    <x-student-official-form code="RES-Form-047" title="Endorsement for Reproduction of the Research Paper" guidebook-page="133">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? now()->format('Y-m-d') }}"></label>
        <div>
            <input value="{{ $deanName }}" class="w-72 font-bold" readonly>
            <br>
            <span>Dean, College of <input value="Engineering, Architecture, and Computing" class="w-80" readonly></span>
        </div>
        <p class="mt-2">Dear <input name="payload[salutation]" value="{{ $payload['salutation'] ?? 'Dr. Castillo' }}" class="w-56">:</p>
        <p>After a thorough examination of the revised research paper entitled:</p>
        <input value="{{ $currentResearchTitle }}" class="w-full text-center font-bold" aria-label="Research title" readonly>
        <p>of the following students:</p>
        <div class="grid gap-3 md:grid-cols-2">
            @for ($i = 1; $i <= 4; $i++)
                <label class="flex gap-2">
                    <span>{{ $i }}.</span>
                    <input value="{{ $members->get($i - 1)?->student?->name }}" class="flex-1" readonly placeholder="Researcher {{ $i }}">
                </label>
            @endfor
        </div>
        <p class="leading-7">
            I further attest that the research paper has incorporated the corrections and suggestions of the panel of examiners and was duly edited by proficient language and technical editors. Hence, I highly endorse the attached hard copy for reproduction and hard binding.
        </p>
        <p>For your approval.</p>
        <div class="grid gap-8 pt-10 text-center md:grid-cols-2">
            <div>
                <x-official-signature-field name-field="res_047_adviser_printed_name" label="Endorsed: Research Adviser" />
                <p class="mt-1 text-xs font-bold text-slate-800">{{ $adviserName }}</p>
                <label class="mt-2 block text-xs text-slate-500">
                    Date Endorsed:
                    <input type="text" value="{{ $officialFormInstance?->updated_at?->format('M j, Y') ?? now()->format('M j, Y') }}" class="w-full text-center" readonly>
                </label>
            </div>
            <div>
                <x-official-signature-field name-field="res_047_college_dean_name" label="Approved: College Dean" />
                <p class="mt-1 text-xs font-bold text-slate-800">{{ $deanName }}</p>
            </div>
        </div>
    </x-student-official-form>
</div>
