@props(['code', 'title', 'guidebookPage'])
@php($workspaceInstance = request()->routeIs('official-forms.workspace.show', 'official-forms.print') ? request()->route('instance') : null)

@once
    <style>
        .official-form-viewport {
            overflow-x: auto;
            padding: 1.5rem 1rem 2rem;
            border-radius: 1.25rem;
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, .09), transparent 34%),
                linear-gradient(145deg, #31594c, #24483d);
        }
        .official-form-paper {
            box-sizing: border-box; color: #111827; font-family: "Times New Roman", Times, serif;
            display: flex; flex-direction: column;
            width: 8.5in; min-width: 8.5in; min-height: 11in;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: .2rem;
            box-shadow: 0 24px 55px rgba(15, 23, 42, .32);
            padding: .62in .70in;
        }
        .official-form-body { flex: 1 1 auto; min-height: 0; overflow: visible; }
        .official-form-actions {
            position: sticky; bottom: 0; z-index: 5; flex: 0 0 auto; margin-top: 1rem;
            background: #ffffff;
        }
        .official-form-paper input, .official-form-paper textarea, .official-form-paper select {
            background: transparent; border: 0; border-bottom: 1px solid #365e50; border-radius: 0;
            color: #111827; min-width: 0; padding: .2rem .25rem; outline: none;
            pointer-events: auto; user-select: text;
        }
        .official-form-paper input:not([type="checkbox"]):not([type="radio"]) {
            position: relative; z-index: 1; cursor: text;
        }
        .official-form-paper input[type="checkbox"], .official-form-paper input[type="radio"] {
            appearance: auto; border: initial; width: auto; accent-color: #0e5c3a;
        }
        .official-form-paper input[readonly], .official-form-paper textarea[readonly] { cursor: not-allowed; opacity: .72; }
        .official-form-paper textarea { border: 1px solid #567368; resize: vertical; }
        .official-form-paper input:focus, .official-form-paper textarea:focus, .official-form-paper select:focus {
            background: #f0f9f5; box-shadow: 0 1px 0 #0e5c3a;
        }
        .official-form-table { width: 100%; border-collapse: collapse; }
        .official-form-table th, .official-form-table td { border: 1px solid #567368; padding: .45rem; vertical-align: top; }
        @media print {
            @page { size: Letter portrait; margin: 0; }
            body * { visibility: hidden !important; }
            .official-form-print-area, .official-form-print-area * { visibility: visible !important; }
            .official-form-viewport { overflow: visible !important; padding: 0 !important; background: #ffffff !important; }
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
<div class="official-form-print-area official-form-paper mx-auto bg-white">
    <div class="flex items-start justify-between gap-6 text-xs">
        <strong>{{ $code }}</strong>
        @if (filled($guidebookPage))
            <div class="text-right italic">
                <div>NDMU Undergraduate Research Guidebook</div>
                <strong class="not-italic">{{ $guidebookPage }}</strong>
            </div>
        @endif
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

    @unless ($workspaceInstance instanceof \App\Models\OfficialFormInstance)
        <div class="official-form-actions flex flex-wrap gap-3 border-t border-slate-300 pt-3">
            <a href="{{ route('official-forms.workspace.index') }}" class="rounded-lg bg-[#0e5c3a] px-5 py-2.5 text-xs font-bold text-white">Open Workspace to Edit & Save</a>
        </div>
    @endunless
</div>
</div>
