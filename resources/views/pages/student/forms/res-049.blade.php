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
        <input type="hidden" name="payload[authorship_confirmed]" value="0">
        <label class="flex items-start gap-3 rounded-xl border border-[#173c30] bg-emerald-50/50 p-4">
            <input type="checkbox" name="payload[authorship_confirmed]" value="1" @checked((bool)($payload['authorship_confirmed'] ?? true)) class="mt-1">
            <span class="font-medium text-slate-800">I/We certify this declaration and accept responsibility for the authenticity of the submitted research.</span>
        </label>
        <div class="space-y-6 pt-6">
            @for ($i = 1; $i <= max(3, $members->count()); $i++)
                @php
                    $member = $members->get($i - 1);
                @endphp
                <div class="grid grid-cols-1 gap-4 text-center md:grid-cols-3 items-center border-b border-gray-100 pb-4">
                    <div>
                        <input type="text" value="{{ $member?->student?->name }}" class="w-full text-center font-bold" readonly placeholder="Researcher {{ $i }}">
                        <span class="mt-1 block text-xs text-slate-500">Printed Name of Researcher {{ $i }}</span>
                    </div>
                    <x-official-signature-field :label="'Researcher '.$i.' signature'" />
                    <div>
                        <input type="text" value="{{ $officialFormInstance?->submitted_at?->format('M j, Y') ?? now()->format('M j, Y') }}" class="w-full text-center text-xs" readonly>
                        <span class="mt-1 block text-xs text-slate-500">Date</span>
                    </div>
                </div>
            @endfor
        </div>
        <div class="grid grid-cols-2 gap-8 pt-10 text-center">
            <div>
                <div class="border-t border-[#173c30] pt-1 font-bold">{{ $adviserName }}</div>
                <span class="text-xs text-slate-500">Research Adviser</span>
            </div>
            <div>
                <div class="border-t border-[#173c30] pt-1 font-bold">{{ $programCoordinatorName }}</div>
                <span class="text-xs text-slate-500">Program Coordinator</span>
            </div>
        </div>
    </x-student-official-form>
</div>
