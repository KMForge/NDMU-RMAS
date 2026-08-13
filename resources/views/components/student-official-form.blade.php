@props(['code', 'title', 'guidebookPage'])
@php($workspaceInstance = request()->routeIs('official-forms.workspace.show', 'official-forms.print') ? request()->route('instance') : null)

@once
    <style>
        .official-form-viewport { overflow-x: auto; padding-bottom: 1rem; }
        .official-form-paper {
            box-sizing: border-box; color: #071912; font-family: Arial, Helvetica, sans-serif;
            display: flex; flex-direction: column;
            width: 8.5in; min-width: 8.5in; min-height: 11in;
        }
        .official-form-body { flex: 1 1 auto; min-height: 0; overflow: visible; }
        .official-form-actions {
            position: sticky; bottom: 0; z-index: 5; flex: 0 0 auto; margin-top: 1rem;
            background: #ccebdd;
        }
        .official-form-paper input, .official-form-paper textarea, .official-form-paper select {
            background: transparent; border: 0; border-bottom: 1px solid #173c30; border-radius: 0;
            color: #071912; min-width: 0; padding: .2rem .25rem; outline: none;
            pointer-events: auto; user-select: text;
        }
        .official-form-paper input:not([type="checkbox"]):not([type="radio"]) {
            position: relative; z-index: 1; cursor: text;
        }
        .official-form-paper input[type="checkbox"], .official-form-paper input[type="radio"] {
            appearance: auto; border: initial; width: auto; accent-color: #006b46;
        }
        .official-form-paper input[readonly], .official-form-paper textarea[readonly] { cursor: not-allowed; opacity: .72; }
        .official-form-paper textarea { border: 1px solid #173c30; resize: vertical; }
        .official-form-paper input:focus, .official-form-paper textarea:focus, .official-form-paper select:focus {
            background: rgba(255,255,255,.28); box-shadow: 0 1px 0 #009b67;
        }
        .official-form-table { width: 100%; border-collapse: collapse; }
        .official-form-table th, .official-form-table td { border: 1px solid #173c30; padding: .45rem; vertical-align: top; }
        @media print {
            @page { size: Letter portrait; margin: 0; }
            body * { visibility: hidden !important; }
            .official-form-print-area, .official-form-print-area * { visibility: visible !important; }
            .official-form-viewport { overflow: visible !important; padding: 0 !important; }
            .official-form-print-area {
                position: absolute; inset: 0; width: 8.5in; min-width: 8.5in;
                height: auto; min-height: 11in; box-shadow: none !important;
            }
            .official-form-body { overflow: visible !important; }
            .official-form-actions { display: none !important; }
        }
    </style>
@endonce

<div class="official-form-viewport w-full">
<div class="official-form-print-area official-form-paper mx-auto bg-[#ccebdd] px-12 py-9 shadow-xl">
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

    <div class="official-form-body mt-7 space-y-5 text-sm">
        {{ $slot }}
    </div>

    <div class="official-form-actions flex flex-wrap gap-3 border-t border-[#173c30]/20 pt-3">
        @if ($workspaceInstance instanceof \App\Models\OfficialFormInstance)
            <button type="submit" form="official-form-editor" class="rounded-lg bg-[#009b67] px-5 py-2.5 text-xs font-bold text-white">Save Form</button>
        @else
            <a href="{{ route('official-forms.workspace.index') }}" class="rounded-lg bg-[#009b67] px-5 py-2.5 text-xs font-bold text-white">Open Saved Form</a>
        @endif
        @if ($workspaceInstance instanceof \App\Models\OfficialFormInstance)
            <a href="{{ route('official-forms.print', $workspaceInstance) }}" target="_blank" rel="noopener" class="rounded-lg bg-blue-600 px-5 py-2.5 text-xs font-bold text-white">Print Saved Form</a>
            <a href="{{ route('official-forms.print', $workspaceInstance) }}" target="_blank" rel="noopener" class="rounded-lg bg-purple-600 px-5 py-2.5 text-xs font-bold text-white">Export Saved PDF</a>
        @else
            <button type="button" disabled class="rounded-lg bg-blue-600 px-5 py-2.5 text-xs font-bold text-white opacity-50">Print Saved Form</button>
            <button type="button" disabled class="rounded-lg bg-purple-600 px-5 py-2.5 text-xs font-bold text-white opacity-50">Export Saved PDF</button>
        @endif
        <button type="button" @click="activeTab = 'dashboard'" class="rounded-lg bg-gray-100 px-5 py-2.5 text-xs font-bold text-gray-700">Cancel</button>
    </div>
</div>
</div>
