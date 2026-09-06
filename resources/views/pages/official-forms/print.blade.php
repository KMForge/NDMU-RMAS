<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $instance->definition->code }} - {{ $instance->definition->title }} | NDMU-RMAS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page { size: Letter portrait; margin: 0; }
        body { margin: 0; background: #eef2f0; }
        .print-toolbar { position: sticky; top: 0; z-index: 50; padding: .75rem; text-align: center; background: white; color: #1e293b; border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(15, 23, 42, .08); }
        .official-form-paper input, .official-form-paper textarea, .official-form-paper select { pointer-events: none !important; }
        @media print {
            .print-toolbar, .official-form-actions { display: none !important; }
            body { background: white; }
        }
    </style>
</head>
<body x-data="{ activeOfficialForm: @js($instance->definition->code), activeTab: 'forms' }">
    @php
        $verificationRef = $version?->verification?->public_reference;
        $verifyUrl = $verificationRef ? route('official-forms.verify', ['reference' => $verificationRef]) : null;
        $qrDataUri = null;
        if ($verifyUrl) {
            try {
                $qrDataUri = app(\App\Services\OfficialFormQrCodeGenerator::class)->generateSvgDataUri($verifyUrl);
            } catch (\Throwable $e) {}
        }
    @endphp

    <div class="print-toolbar flex items-center justify-between px-8">
        <div>
            <p class="text-xs font-bold text-[#164b38]">
                Saved authoritative version {{ $version?->version_number ?? 0 }} · {{ strtoupper($instance->status) }}
            </p>
            @if ($verifyUrl)
                <p class="text-[10px] text-slate-500 font-mono">Verification: {{ $verificationRef }}</p>
            @endif
        </div>
        <div class="flex items-center space-x-4">
            @if ($qrDataUri)
                <div class="h-10 w-10 bg-white p-0.5 border border-gray-300 rounded shadow-sm" title="Scan to verify document integrity">
                    <img src="{{ $qrDataUri }}" alt="QR Code" class="h-full w-full">
                </div>
            @endif
            <button type="button" onclick="window.print()" class="rounded-lg bg-[#237655] px-5 py-2 text-xs font-bold text-white hover:bg-[#2b8a65]">Print / Save as PDF</button>
        </div>
    </div>

    @if (view()->exists($instance->definition->template_view))
        @include($instance->definition->template_view, [
            'officialFormInstance' => $instance,
            'payload' => $payload,
        ])
    @else
        <div class="mx-auto max-w-4xl p-10 text-center">
            <h2 class="text-xl font-bold text-gray-800">{{ $instance->definition->code }} — {{ $instance->definition->title }}</h2>
            <p class="mt-2 text-sm text-gray-600">Institutional print view template under verification [{{ $instance->definition->template_view }}].</p>
        </div>
    @endif
</body>
</html>
