@props(['code', 'title', 'guidebookPage'])

@once
    <style>
        .official-form-paper { color: #071912; font-family: Arial, Helvetica, sans-serif; }
        .official-form-paper input, .official-form-paper textarea, .official-form-paper select {
            background: transparent; border: 0; border-bottom: 1px solid #173c30; border-radius: 0;
            min-width: 0; padding: .2rem .25rem; outline: none;
        }
        .official-form-paper textarea { border: 1px solid #173c30; resize: vertical; }
        .official-form-paper input:focus, .official-form-paper textarea:focus, .official-form-paper select:focus {
            background: rgba(255,255,255,.28); box-shadow: 0 1px 0 #009b67;
        }
        .official-form-table { width: 100%; border-collapse: collapse; }
        .official-form-table th, .official-form-table td { border: 1px solid #173c30; padding: .45rem; vertical-align: top; }
        @media print {
            body * { visibility: hidden !important; }
            .official-form-print-area, .official-form-print-area * { visibility: visible !important; }
            .official-form-print-area { position: absolute; inset: 0; width: 100%; box-shadow: none !important; }
            .official-form-actions { display: none !important; }
        }
    </style>
@endonce

<div class="official-form-print-area official-form-paper mx-auto w-full max-w-4xl bg-[#ccebdd] px-10 py-9 shadow-xl sm:px-12">
    <div class="flex items-start justify-between gap-6 text-xs">
        <strong>{{ $code }}</strong>
        <div class="text-right italic">
            <div>NDMU Undergraduate Research Guidebook</div>
            <strong class="not-italic">{{ $guidebookPage }}</strong>
        </div>
    </div>

    <div class="mt-3 text-center text-sm leading-5">
        <div>JMJ Marist Brothers</div>
        <div class="font-bold">NOTRE DAME OF MARBEL UNIVERSITY</div>
        <div>City of Koronadal, South Cotabato</div>
        <h2 class="mt-5 text-base font-bold uppercase">{{ $title }}</h2>
    </div>

    <div class="mt-7 space-y-5 text-sm">
        {{ $slot }}
    </div>

    <div class="official-form-actions mt-8 flex flex-wrap gap-3">
        <button type="button" disabled title="Database saving will be connected in the backend stage" class="rounded-lg bg-[#009b67] px-5 py-2.5 text-xs font-bold text-white disabled:cursor-not-allowed disabled:opacity-55">Save Form</button>
        <button type="button" onclick="window.print()" class="rounded-lg bg-blue-600 px-5 py-2.5 text-xs font-bold text-white">Print Form</button>
        <button type="button" onclick="window.print()" class="rounded-lg bg-purple-600 px-5 py-2.5 text-xs font-bold text-white">Export to PDF</button>
        <button type="button" @click="activeTab = 'dashboard'" class="rounded-lg bg-gray-100 px-5 py-2.5 text-xs font-bold text-gray-700">Cancel</button>
    </div>
</div>
