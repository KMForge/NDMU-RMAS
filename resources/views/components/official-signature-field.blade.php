@props([
    'label',
    'nameField' => null,
    'namePlaceholder' => 'Type printed name',
    'actorType' => null,
    'academicAction' => null,
    'signature' => null,
    'instance' => null,
])

<div
    x-data="{ signatureQrOpen: false }"
    {{ $attributes->class(['official-signature-field text-center']) }}
>
    @php
        $formInstance = $instance
            ?? ($officialFormInstance ?? null)
            ?? ($__data['officialFormInstance'] ?? null)
            ?? ($__data['instance'] ?? null)
            ?? (request()->route('instance') instanceof \App\Models\OfficialFormInstance ? request()->route('instance') : null);
        
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
            $institutionalActors = app(\App\Modules\OfficialForms\Services\InstitutionalActorResolver::class);
            $institutionalDean = $institutionalActors->dean();
            $institutionalProgramCoordinator = $institutionalActors->programCoordinatorForGroup($formInstance->group)
                ?? $institutionalActors->programCoordinatorForClass($formInstance->researchClass);

            $authoritativeName = match ($effectiveActorType) {
                'research_adviser', 'adviser' => $formInstance->group?->adviser?->name,
                'facilitator' => $formInstance->researchClass?->facilitator?->name
                    ?? $formInstance->group?->researchClass?->facilitator?->name,
                'program_coordinator', 'program_head' => $institutionalProgramCoordinator?->name
                    ?? $formInstance->actorAssignments->firstWhere('actor_type', 'program_coordinator')?->user?->name
                    ?? $classAssignments?->firstWhere('actor_type', 'program_coordinator')?->user?->name
                    ?? $classAssignments?->firstWhere('actor_type', 'program_head')?->user?->name,
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
                <button
                    type="button"
                    class="official-signature-qr"
                    title="Enlarge this verification QR code"
                    aria-label="Enlarge QR code for {{ $label }} signature verification"
                    @click="signatureQrOpen = true"
                >
                    <img src="{{ $signatureQrDataUri }}" alt="QR code for {{ $label }} signature verification">
                </button>
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

    @if ($signatureQrDataUri && $signatureVerification)
        <template x-teleport="body">
            <div
                x-show="signatureQrOpen"
                x-cloak
                x-transition.opacity
                class="official-signature-qr-modal fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm print:hidden"
                role="dialog"
                aria-modal="true"
                aria-labelledby="signature-qr-title-{{ $appliedSignature->id }}"
                @click.self="signatureQrOpen = false"
                @keydown.escape.window="signatureQrOpen = false"
            >
                <section class="w-full max-w-sm rounded-3xl border border-emerald-100 bg-white p-5 text-center font-sans shadow-2xl sm:p-6">
                    <div class="flex items-start justify-between gap-4 text-left">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.16em] text-emerald-700">Digital signature verification</p>
                            <h2 id="signature-qr-title-{{ $appliedSignature->id }}" class="mt-1 text-lg font-black text-slate-950">
                                {{ $label }}
                            </h2>
                        </div>
                        <button
                            type="button"
                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-emerald-600"
                            aria-label="Close QR code preview"
                            @click="signatureQrOpen = false"
                        >
                            <i class="ph ph-x text-lg" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="mx-auto mt-5 w-fit rounded-2xl border-2 border-emerald-200 bg-white p-3 shadow-sm">
                        <img
                            src="{{ $signatureQrDataUri }}"
                            alt="Enlarged QR code for {{ $label }} signature verification"
                            class="h-64 w-64 sm:h-72 sm:w-72"
                        >
                    </div>

                    <p class="mt-4 text-sm font-bold text-slate-900">Scan to verify this signature</p>
                    <p class="mt-1 break-all font-mono text-[10px] text-slate-500">
                        Reference: {{ $signatureVerification->public_reference }}
                    </p>

                    <a
                        href="{{ $signatureVerifyUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#0e5c3a] px-4 py-3 text-sm font-black text-white transition hover:bg-[#09472d] focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2"
                    >
                        <i class="ph ph-seal-check text-lg" aria-hidden="true"></i>
                        <span>Open Verification Page</span>
                    </a>
                </section>
            </div>
        </template>
    @endif
</div>
