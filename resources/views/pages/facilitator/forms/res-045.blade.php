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
    $dean = $resolver->dean();
    $deanName = $dean?->name ?? 'Dr. Lourdes Castillo';
@endphp
<div x-show="activeOfficialForm === 'RES-045'" x-cloak>
    <x-student-official-form code="RES-Form-045" title="Certificate of Language Editing" guidebook-page="131">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? now()->format('Y-m-d') }}"></label>
        <div>
            <input value="{{ $deanName }}" class="w-72 font-bold" readonly>
            <br>
            <span>Dean, College of <input value="Engineering, Architecture, and Computing" class="w-80" readonly></span>
        </div>
        <p class="mt-2">Dear Sir/Madam,</p>
        <p>This is to certify that the research paper entitled:</p>
        <input value="{{ $currentResearchTitle }}" class="w-full text-center font-bold" aria-label="Research title" readonly>
        <p>to be submitted by the student researcher/s whose name/s appear below:</p>
        <div class="grid gap-3 md:grid-cols-2">
            @for ($i = 1; $i <= 4; $i++)
                <label class="flex gap-2">
                    <span>{{ $i }}.</span>
                    <input value="{{ $members->get($i - 1)?->student?->name }}" class="flex-1 font-medium" readonly placeholder="Researcher {{ $i }}">
                </label>
            @endfor
        </div>
        <p class="leading-7">has undergone language editing. The content and the authors' intention were not altered in any way during the editing process.</p>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2">
            <x-official-signature-field name-field="res_045_editor_printed_name" label="Language Editor" />
            <label>
                <span>Date Edited:</span>
                <input type="date" name="payload[edited_date]" value="{{ $payload['edited_date'] ?? now()->format('Y-m-d') }}" class="w-full text-center mt-1">
            </label>
        </div>
    </x-student-official-form>
</div>
