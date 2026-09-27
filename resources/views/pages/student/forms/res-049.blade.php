@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $group = $officialFormInstance?->group;
    $class = $officialFormInstance?->researchClass ?? $group?->researchClass;
    $resolver = app(\App\Modules\OfficialForms\Services\InstitutionalActorResolver::class);

    $members = $group?->members?->values() ?? collect();
    $adviserName = $group?->adviser?->name ?? 'Research Adviser';
    $programCoordinator = $resolver->programCoordinatorForGroup($group) ?? $resolver->programCoordinatorForClass($class);
    $programCoordinatorName = $programCoordinator?->name ?? 'Program Coordinator';
@endphp
<div x-show="activeOfficialForm === 'RES-049'" x-cloak>
    <x-student-official-form code="RES-Form-049" title="Certificate of Authentic Authorship" guidebook-page="135">
        <p class="text-justify leading-6">
            I/We declare that this submission is my/our own work and to the best of my/our knowledge it contains no materials previously published or written by another person, nor material which to a substantial extent has been accepted for the award of any other degree or diploma at NDMU or elsewhere, except where due acknowledgment is made in the research paper.
        </p>
        <p class="text-justify leading-6">
            I/We also declare that the intellectual content of this research is the product of my/our work, except to the extent that assistance from others in the project's design and conception or in style, presentation, and linguistic expression is acknowledged.
        </p>
        <input type="hidden" name="payload[authorship_confirmed]" value="1">
        <div class="space-y-6 pt-6">
            @for ($i = 1; $i <= 4; $i++)
                @php
                    $member = $members->get($i - 1);
                @endphp
                <div class="official-signature-row mx-auto w-full max-w-md text-center">
                    <x-official-signature-field :name-field="'res_049_researcher_'.$i.'_signature'" label="Printed Name & Signature" />
                    <input type="text" value="{{ $member?->student?->name }}" class="mt-1 w-full text-center text-xs font-bold" readonly placeholder="Researcher {{ $i }}">
                </div>
            @endfor
        </div>
    </x-student-official-form>
</div>
