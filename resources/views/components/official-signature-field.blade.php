@props([
    'label',
    'nameField' => null,
    'namePlaceholder' => 'Type printed name',
    'actorType' => null,
    'academicAction' => null,
    'signature' => null,
])

<div {{ $attributes->class(['space-y-2 text-center']) }}>
    @php
        $officialFormInstance = request()->routeIs('official-forms.workspace.show', 'official-forms.print') ? request()->route('instance') : null;
        
        // If signature prop wasn't passed explicitly, attempt to resolve from instance currentVersion
        $appliedSignature = $signature;
        if (! $appliedSignature && $officialFormInstance instanceof \App\Models\OfficialFormInstance && $officialFormInstance->currentVersion) {
            $appliedSignature = $officialFormInstance->currentVersion->signatures
                ->first(function ($sig) use ($actorType, $academicAction, $label) {
                    if ($actorType && $sig->actor_type !== $actorType) return false;
                    if ($academicAction && $sig->academic_action !== $academicAction) return false;
                    if (! $actorType && ! $academicAction) {
                        return str_contains(strtolower($sig->actor_type), strtolower(str_replace(' ', '_', $label)));
                    }
                    return true;
                });
        }

        $authoritativeName = null;
        if ($appliedSignature) {
            $authoritativeName = $appliedSignature->signer_name_snapshot;
        } elseif ($officialFormInstance instanceof \App\Models\OfficialFormInstance && $actorType) {
            $classAssignments = $officialFormInstance->researchClass?->officialFormActorAssignments
                ?? $officialFormInstance->group?->researchClass?->officialFormActorAssignments;
            $authoritativeName = $actorType === 'research_adviser'
                ? $officialFormInstance->group?->adviser?->name
                : ($officialFormInstance->actorAssignments->firstWhere('actor_type', $actorType)?->user?->name
                    ?? $classAssignments?->firstWhere('actor_type', $actorType)?->user?->name);
        }
    @endphp

    @if ($appliedSignature)
        <div class="flex flex-col items-center justify-center space-y-1">
            <div class="h-16 w-48 max-w-full overflow-hidden rounded border border-emerald-300 bg-white p-1 shadow-sm">
                <img
                    src="{{ route('official-forms.workspace.signature-image', $appliedSignature) }}"
                    alt="Digital signature of {{ $appliedSignature->signer_name_snapshot }}"
                    class="h-full w-full object-contain"
                >
            </div>
            <div class="border-b border-[#173c30] px-2 py-0.5 font-bold text-emerald-950">
                {{ $appliedSignature->signer_name_snapshot }}
            </div>
        </div>
        <div
            class="rounded border border-emerald-400 bg-emerald-50/80 px-3 py-1.5 text-[10px] font-semibold text-emerald-900"
            role="status"
            aria-label="{{ $label }} digital signature verified"
            data-digital-signature-status="verified"
        >
            <span class="block font-bold">Signed & Digital Attestation Verified</span>
            <span class="block text-[9px] font-normal text-emerald-800">
                {{ $appliedSignature->signed_at->format('M d, Y h:i A') }}
            </span>
        </div>
    @else
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
        @else
            <div class="border-b border-[#173c30] px-2 py-1 font-bold">{{ $authoritativeName ?: 'Authorized signer pending' }}</div>
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
    @endif

    <span class="block font-bold">{{ $label }}</span>
</div>
