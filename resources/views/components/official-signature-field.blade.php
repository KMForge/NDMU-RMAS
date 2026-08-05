@props([
    'label',
    'nameField' => null,
    'namePlaceholder' => 'Type printed name',
])

<div {{ $attributes->class(['space-y-2 text-center']) }}>
    @if ($nameField)
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
