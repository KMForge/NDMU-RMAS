<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $instance->definition->code }} - {{ $instance->definition->title }} | NDMU-RMAS</title>
    <style>
        body { font-family: 'Times New Roman', serif; font-size: 12pt; line-height: 1.5; color: #111; margin: 0; padding: 2rem; }
        .header { text-align: center; border-bottom: 2px solid #173c30; padding-bottom: 1rem; margin-bottom: 1.5rem; }
        .header h1 { font-size: 16pt; margin: 0; text-transform: uppercase; color: #173c30; }
        .header h2 { font-size: 14pt; margin: 0.25rem 0; font-weight: normal; }
        .header p { font-size: 10pt; margin: 0; color: #555; }
        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; font-size: 10pt; margin-bottom: 1.5rem; border: 1px solid #ccc; padding: 0.75rem; background: #fdfdfd; }
        .payload-box { border: 1px solid #173c30; padding: 1rem; margin-bottom: 1.5rem; font-size: 11pt; }
        .payload-box h3 { font-size: 12pt; margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 0.5rem; color: #173c30; }
        .table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 10pt; }
        .table th, .table td { border: 1px solid #222; padding: 0.5rem; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .actor-box { margin-top: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; text-align: center; }
        .signature-line { border-bottom: 1px solid #000; height: 2.5rem; margin-bottom: 0.25rem; }
        .actor-title { font-size: 9pt; text-transform: uppercase; color: #444; }
        @media print { body { padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 1.5rem; text-align: right;">
        <button onclick="window.print()" style="background: #173c30; color: white; border: none; padding: 0.5rem 1rem; cursor: pointer; font-size: 10pt; font-weight: bold; border-radius: 4px;">Print Document</button>
    </div>

    <div class="header">
        <h1>NOTRE DAME OF MARBEL UNIVERSITY</h1>
        <h2>Research Management and Assistance System</h2>
        <p>{{ $instance->definition->title }} ({{ $instance->definition->code }})</p>
    </div>

    <div class="meta-grid">
        <div><strong>Form Code:</strong> {{ $instance->definition->code }}</div>
        <div><strong>Document Status:</strong> {{ strtoupper($instance->status) }}</div>
        <div><strong>Research Group:</strong> {{ $instance->group?->name ?? 'N/A' }}</div>
        <div><strong>Research Class:</strong> {{ $instance->researchClass?->name ?? 'N/A' }}</div>
        <div><strong>Version:</strong> v{{ $version->version_number ?? 1 }}</div>
        <div><strong>Date Created:</strong> {{ $instance->created_at->format('F d, Y h:i A') }}</div>
    </div>

    <div class="payload-box">
        <h3>Form Payload Data</h3>
        @if (!empty($payload))
            <ul style="list-style: none; padding-left: 0;">
                @foreach ($payload as $key => $value)
                    <li style="margin-bottom: 0.5rem;">
                        <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong>
                        @if (is_array($value))
                            <pre style="font-family: inherit; font-size: 10pt; background: #f5f5f5; padding: 0.5rem; margin-top: 0.25rem;">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                        @else
                            <span>{{ $value ?? 'N/A' }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p>No payload data recorded for this version.</p>
        @endif
    </div>

    <div class="actor-box">
        <div>
            <div class="signature-line"></div>
            <strong>{{ $instance->group?->leader?->name ?? 'Group Leader' }}</strong>
            <div class="actor-title">Student Researcher / Initiator</div>
        </div>
        <div>
            <div class="signature-line"></div>
            <strong>{{ $instance->group?->adviser?->name ?? 'Thesis Adviser' }}</strong>
            <div class="actor-title">Thesis Adviser</div>
        </div>
    </div>
</body>
</html>
