<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Official Form Verification — NDMU-RMAS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="mx-auto max-w-3xl px-4 py-12">
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md">
            <!-- Header Banner -->
            <div class="border-b border-emerald-900/20 bg-emerald-950 px-6 py-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-300">Notre Dame of Marbel University</span>
                        <h1 class="text-xl font-extrabold tracking-tight">Official Document Digital Verification</h1>
                    </div>
                    @if ($qrSvgDataUri)
                        <div class="h-14 w-14 rounded bg-white p-1">
                            <img src="{{ $qrSvgDataUri }}" alt="Verification QR Code" class="h-full w-full">
                        </div>
                    @endif
                </div>
            </div>

            <!-- Verification Status Box -->
            <div class="p-6">
                @php
                    $status = $evaluation['status'] ?? 'UNKNOWN';
                    $isValid = $evaluation['is_valid'] ?? false;
                @endphp

                <div class="mb-6 rounded-lg border p-4 @if($isValid) border-emerald-300 bg-emerald-50 text-emerald-950 @else border-rose-300 bg-rose-50 text-rose-950 @endif">
                    <div class="flex items-center space-x-3">
                        @if ($isValid)
                            <svg class="h-8 w-8 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <h2 class="text-base font-bold">
                                    {{ $status === 'VALID_CURRENT' ? 'VERIFIED AUTHORITATIVE CURRENT VERSION' : 'VERIFIED HISTORICAL VERSION' }}
                                </h2>
                                <p class="text-xs text-emerald-800">
                                    This document version and all applied digital signature attestations have been cryptographically verified against the institutional registry.
                                </p>
                            </div>
                        @else
                            <svg class="h-8 w-8 text-rose-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <h2 class="text-base font-bold">VERIFICATION UNCONFIRMED ({{ $status }})</h2>
                                <p class="text-xs text-rose-800">
                                    The document payload or signature attestations could not be fully verified against the institutional registry.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Document Summary -->
                <div class="space-y-4">
                    <h3 class="border-b border-slate-200 pb-2 text-sm font-bold text-slate-900 uppercase tracking-wider">Document Details</h3>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2 text-xs">
                        <div>
                            <dt class="font-semibold text-slate-500">Form Code</dt>
                            <dd class="mt-0.5 font-bold text-slate-900">{{ strtoupper($definition->code) }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">Form Title</dt>
                            <dd class="mt-0.5 font-bold text-slate-900">{{ $definition->title }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">Version Number</dt>
                            <dd class="mt-0.5 font-medium text-slate-900">v{{ $version->version_number }} @if($version->is_current) (Current) @else (Historical) @endif</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">Public Reference UUID</dt>
                            <dd class="mt-0.5 font-mono text-[11px] text-slate-700">{{ $verification->public_reference }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Signers Table -->
                <div class="mt-8 space-y-4">
                    <h3 class="border-b border-slate-200 pb-2 text-sm font-bold text-slate-900 uppercase tracking-wider">Applied Digital Signature Attestations</h3>
                    @if (empty($evaluation['signatures']))
                        <p class="text-xs text-slate-500">No digital signature attestations recorded on this form version.</p>
                    @else
                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 font-semibold text-slate-700">
                                    <tr>
                                        <th class="px-4 py-2.5">Signer Name</th>
                                        <th class="px-4 py-2.5">Academic Function</th>
                                        <th class="px-4 py-2.5">Action</th>
                                        <th class="px-4 py-2.5">Signed Date & Time</th>
                                        <th class="px-4 py-2.5 text-right">Integrity</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach ($evaluation['signatures'] as $sig)
                                        <tr>
                                            <td class="px-4 py-2.5 font-bold text-slate-900">{{ $sig['signer_name'] }}</td>
                                            <td class="px-4 py-2.5 text-slate-600">{{ Str::headline($sig['actor_type']) }}</td>
                                            <td class="px-4 py-2.5 text-slate-600">{{ Str::headline($sig['academic_action']) }}</td>
                                            <td class="px-4 py-2.5 text-slate-600">{{ \Illuminate\Support\Carbon::parse($sig['signed_at'])->format('M d, Y h:i A') }}</td>
                                            <td class="px-4 py-2.5 text-right">
                                                @if ($sig['is_valid'])
                                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">
                                                        VERIFIED
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-800">
                                                        {{ $sig['status'] }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="mt-8 border-t border-slate-200 pt-4 text-center text-[10px] text-slate-500">
                    Notre Dame of Marbel University — Research Management & Assistance System (NDMU-RMAS)<br>
                    Official Document Verification Registry &bull; Confidentiality & Integrity Guaranteed
                </div>
            </div>
        </div>
    </div>
</body>
</html>
