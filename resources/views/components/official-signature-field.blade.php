@props([
    'label',
    'nameField' => null,
    'namePlaceholder' => 'Type printed name',
])

<div {{ $attributes->class(['space-y-2 text-center']) }}>
    @php
        $officialFormInstance = request()->routeIs('official-forms.workspace.show', 'official-forms.print') ? request()->route('instance') : null;
        $authoritativeName = null;
        if ($officialFormInstance instanceof \App\Models\OfficialFormInstance && $nameField) {
            $field = strtolower($nameField);
            $actorType = match (true) {
                str_contains($field, 'instructor') => 'research_instructor',
                str_contains($field, 'coordinator') => 'program_coordinator',
                str_contains($field, 'dean') => 'dean',
                str_contains($field, 'validator') => 'instrument_validator',
                str_contains($field, '045') => 'language_editor',
                str_contains($field, '046') => 'technical_editor',
                default => null,
            };

            $classAssignments = $officialFormInstance->researchClass?->officialFormActorAssignments
                ?? $officialFormInstance->group?->researchClass?->officialFormActorAssignments;
            $authoritativeName = str_contains($field, 'adviser')
                ? $officialFormInstance->group?->adviser?->name
                : ($officialFormInstance->actorAssignments->firstWhere('actor_type', $actorType)?->user?->name
                    ?? $classAssignments?->firstWhere('actor_type', $actorType)?->user?->name);
        }
    @endphp

    @if ($nameField && $officialFormInstance instanceof \App\Models\OfficialFormInstance)
        <div class="border-b border-[#173c30] px-2 py-1 font-bold">{{ $authoritativeName ?: 'Authorized actor pending assignment' }}</div>
    @elseif ($nameField)
        <label class="block text-left">
            <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#173c30]/70">Printed name</span>
            <input
                type="text"
                name="{{ $nameField }}"
                placeholder="{{ $namePlaceholder }}"
                autocomplete="name"
                class="w-full text-center"
            >
        </label>
    @endif

    <div
        class="rounded border border-dashed border-[#173c30]/45 bg-white/20 px-3 py-2 text-[10px] font-semibold text-[#173c30]/65"
        role="status"
        aria-label="{{ $label }} digital signature status"
        data-digital-signature-status="pending"
    >
        <span class="block">Digital signature pending approval</span>
        <span class="mt-0.5 block font-normal">The authorized signer must approve this form.</span>
    </div>

    <span class="block font-bold">{{ $label }}</span>
</div>
