@props([
    'label',
    'nameField' => null,
    'namePlaceholder' => 'Type printed name',
    'actorType' => null,
    'academicAction' => null,
    'signature' => null,
    'instance' => null,
])

<div {{ $attributes->class(['official-signature-field text-center']) }}>
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

        $signatureVerification = $appliedSignature?->verification;
        $signatureVerifyUrl = $signatureVerification
            ? route('official-forms.signature.verify', ['reference' => $signatureVerification->public_reference])
            : null;
        $signatureQrDataUri = null;
        if ($signatureVerifyUrl) {
            try {
                $signatureQrDataUri = app(\App\Services\OfficialFormQrCodeGenerator::class)
                    ->generateSvgDataUri($signatureVerifyUrl);
            } catch (\Throwable) {
                // The visible reference and public URL remain available if QR rendering fails.
            }
        }
    @endphp

    <div class="official-signature-mark" aria-hidden="{{ $appliedSignature ? 'false' : 'true' }}">
        @if ($appliedSignature)
            <div class="official-signature-image">
                <img
                    src="{{ route('official-forms.workspace.signature-image', $appliedSignature) }}"
                    alt="Digital signature of {{ $appliedSignature->signer_name_snapshot }}"
                    class="h-full w-full object-contain"
                >
            </div>
        @else
            <div class="official-signature-placeholder">
                <span>Signature space</span>
            </div>
        @endif
    </div>

    <div class="official-signature-name">
        @if ($appliedSignature)
            <div class="w-full truncate border-b border-[#173c30] px-2 py-0.5 font-bold text-emerald-950">
                {{ $appliedSignature->signer_name_snapshot }}
            </div>
        @elseif ($nameField && $formInstance instanceof \App\Models\OfficialFormInstance)
            <div class="w-full truncate border-b border-[#173c30] px-2 py-1 font-bold">{{ $authoritativeName ?: 'Authorized actor pending assignment' }}</div>
        @elseif ($nameField)
            <label class="block w-full">
                <span class="sr-only">Printed name</span>
                <input
                    type="text"
                    name="{{ $nameField }}"
                    placeholder="{{ $namePlaceholder }}"
                    autocomplete="name"
                    class="w-full text-center"
                >
            </label>
        @else
            <div class="w-full truncate border-b border-[#173c30] px-2 py-1 font-bold">{{ $authoritativeName ?: 'Authorized signer pending' }}</div>
        @endif
    </div>

    @if ($appliedSignature)
        <div
            class="official-signature-status {{ $signatureQrDataUri ? 'has-verification-qr' : '' }} border-emerald-400 bg-emerald-50/80 text-emerald-900"
            role="status"
            aria-label="{{ $label }} digital signature verified"
            data-digital-signature-status="verified"
        >
            <span class="block font-bold">Signed & Digital Attestation Verified</span>
            <span class="block text-[9px] font-normal text-emerald-800">
                {{ $appliedSignature->signed_at->format('M d, Y h:i A') }}
            </span>
            @if ($signatureVerification)
                <span class="block font-mono text-[7px] font-normal text-emerald-700">
                    Verify: {{ strtoupper(substr($signatureVerification->public_reference, 0, 8)) }}
                </span>
            @endif
            @if ($signatureQrDataUri)
                <span class="official-signature-qr" title="Scan to verify this digital signature">
                    <img src="{{ $signatureQrDataUri }}" alt="QR code for {{ $label }} signature verification">
                </span>
            @endif
        </div>
    @else
        <div
            class="official-signature-status border-dashed border-[#173c30]/45 bg-white/20 text-[#173c30]/65"
            role="status"
            aria-label="{{ $label }} digital signature status"
            data-digital-signature-status="pending"
        >
            <span class="block">Digital signature pending approval</span>
            <span class="mt-0.5 block font-normal">The authorized signer must approve this form.</span>
        </div>
    @endif

    <span class="official-signature-label">{{ $label }}</span>
</div>
