@props([
    'label',
    'nameField' => null,
    'namePlaceholder' => 'Type printed name',
    'actorType' => null,
    'academicAction' => null,
    'signature' => null,
    'instance' => null,
])

<div {{ $attributes->class(['space-y-2 text-center']) }}>
    @php
        $formInstance = $instance
            ?? ($officialFormInstance ?? null)
            ?? ($__data['officialFormInstance'] ?? null)
            ?? ($__data['instance'] ?? null)
            ?? (request()->route('instance') instanceof \App\Models\OfficialFormInstance ? request()->route('instance') : null)
            ?? (is_numeric(request()->route('instance')) ? \App\Models\OfficialFormInstance::with('currentVersion.signatures')->find((int) request()->route('instance')) : null);
        
        // If signature prop wasn't passed explicitly, attempt to resolve from instance currentVersion
        $appliedSignature = $signature;
        if (! $appliedSignature && $formInstance instanceof \App\Models\OfficialFormInstance && $formInstance->currentVersion) {
            $appliedSignature = $formInstance->currentVersion->signatures
                ->first(function ($sig) use ($actorType, $academicAction, $label) {
                    if ($actorType && $sig->actor_type !== $actorType) return false;
                    if ($academicAction && $sig->academic_action !== $academicAction) return false;
                    if (! $actorType && ! $academicAction) {
                        $normActor = strtolower(str_replace('_', ' ', $sig->actor_type));
                        $normLabel = strtolower(trim($label));
                        return str_contains($normActor, $normLabel) || str_contains($normLabel, $normActor)
                            || str_contains(strtolower($sig->actor_type), strtolower(str_replace(' ', '_', $label)));
                    }
                    return true;
                });
        }

        $authoritativeName = null;
        if ($appliedSignature) {
            $authoritativeName = $appliedSignature->signer_name_snapshot;
        } elseif ($formInstance instanceof \App\Models\OfficialFormInstance) {
            $effectiveActorType = $actorType ?? \Illuminate\Support\Str::snake(\Illuminate\Support\Str::lower($label));
            $classAssignments = $formInstance->researchClass?->officialFormActorAssignments
                ?? $formInstance->group?->researchClass?->officialFormActorAssignments;
            $titlePanelAssignments = $formInstance->titlePresentation?->defense?->activePanelAssignments?->keyBy('panel_position');
            $institutionalDean = app(\App\Modules\OfficialForms\Services\InstitutionalActorResolver::class)->dean();

            $authoritativeName = match ($effectiveActorType) {
                'research_adviser', 'adviser' => $formInstance->group?->adviser?->name,
                'facilitator' => $formInstance->researchClass?->facilitator?->name
                    ?? $formInstance->group?->researchClass?->facilitator?->name,
                'program_coordinator' => $formInstance->actorAssignments->firstWhere('actor_type', 'program_coordinator')?->user?->name
                    ?? $classAssignments?->firstWhere('actor_type', 'program_coordinator')?->user?->name
                    ,
                'dean', 'college_dean' => $institutionalDean?->name
                    ?? $formInstance->actorAssignments->firstWhere('actor_type', 'dean')?->user?->name
                    ?? $classAssignments?->firstWhere('actor_type', 'dean')?->user?->name,
                'title_panel_chairperson' => $titlePanelAssignments?->get('chairperson')?->user?->name,
                'title_panel_member_1' => $titlePanelAssignments?->get('member_1')?->user?->name,
                'title_panel_member_2' => $titlePanelAssignments?->get('member_2')?->user?->name,
                default => $formInstance->actorAssignments->firstWhere('actor_type', $effectiveActorType)?->user?->name
                    ?? $classAssignments?->firstWhere('actor_type', $effectiveActorType)?->user?->name,
            };
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
        @if ($nameField && $formInstance instanceof \App\Models\OfficialFormInstance)
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
