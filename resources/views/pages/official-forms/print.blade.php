<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $instance->definition->code }} - {{ $instance->definition->title }} | NDMU-RMAS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page { size: Letter portrait; margin: 0; }
        body { margin: 0; background: #eef3f1; }
        .print-toolbar { position: sticky; top: 0; z-index: 50; padding: .75rem; text-align: center; background: white; border-bottom: 1px solid #ddd; }
        .official-form-paper input, .official-form-paper textarea, .official-form-paper select { pointer-events: none !important; }
        @media print {
            .print-toolbar, .official-form-actions { display: none !important; }
            body { background: white; }
        }
    </style>
</head>
<body x-data="{ activeOfficialForm: @js($instance->definition->code), activeTab: 'forms' }">
    <div class="print-toolbar">
        <p class="mb-2 text-xs font-bold text-[#0e5c3a]">
            Saved authoritative version {{ $version?->version_number ?? 0 }} · {{ strtoupper($instance->status) }}
        </p>
        <button type="button" onclick="window.print()" class="rounded-lg bg-[#0e5c3a] px-5 py-2 text-xs font-bold text-white">Print / Save as PDF</button>
    </div>

    @include($instance->definition->template_view, [
        'officialFormInstance' => $instance,
        'payload' => $payload,
    ])
</body>
</html>
