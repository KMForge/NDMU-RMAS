@props(['instance'])

@php
    $signatures = $instance->currentVersion?->signatures ?? collect();
    $required = [
        'chairperson' => ['title_panel_chairperson', 'sign_chairperson'],
        'member_1' => ['title_panel_member_1', 'sign_member_1'],
        'member_2' => ['title_panel_member_2', 'sign_member_2'],
        'coordinator' => ['program_coordinator', 'endorse'],
        'dean' => ['dean', 'approve'],
    ];
    $signed = collect($required)->map(fn ($requirement): bool => $signatures->contains(
        fn ($signature): bool => $signature->actor_type === $requirement[0]
            && $signature->academic_action === $requirement[1],
    ));
    $panelCount = $signed->only(['chairperson', 'member_1', 'member_2'])->filter()->count();
    $fullyApproved = $signed->every(fn ($value): bool => $value)
        && $instance->status === 'approved'
        && $instance->titlePresentation?->status === 'finalized';
    $approvalLabel = match (true) {
        $fullyApproved => 'Approved - All required signatures recorded',
        $panelCount === 3 && $signed['coordinator'] => 'Awaiting Dean approval',
        $panelCount === 3 => 'Awaiting Program Coordinator signature',
        $instance->titlePresentation?->status === 'awaiting_panel_signatures' || $panelCount > 0 => 'Awaiting panel signatures',
        default => 'Submitted - Signatures and Dean Approval Pending',
    };
@endphp

<div class="w-full rounded-xl border p-4 {{ $fullyApproved ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-900' }}" role="status" data-res026-approval-status="{{ $fullyApproved ? 'approved' : 'pending' }}">
    <p class="text-xs font-black">{{ $approvalLabel }}</p>
    <div class="mt-3 grid gap-2 text-xs sm:grid-cols-3">
        <p><span class="font-bold">Panel signatures:</span> {{ $panelCount }}/3 recorded</p>
        <p><span class="font-bold">Program Coordinator:</span> {{ $signed['coordinator'] ? 'Signed' : 'Pending signature' }}</p>
        <p><span class="font-bold">College Dean:</span> {{ $signed['dean'] ? 'Signed' : 'Pending approval and signature' }}</p>
    </div>
    @unless ($fullyApproved)
        <p class="mt-2 text-[11px]">Recording the selected title or submitting this form is not final approval. The panel, Program Coordinator, and College Dean must sign this saved version.</p>
    @endunless
</div>
