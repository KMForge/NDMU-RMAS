<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <x-favicon />
    <title>Digital Signature Verification - NDMU-RMAS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    @php
        $isValid = (bool) ($evaluation['is_valid'] ?? false);
        $signedAt = $signature->signed_at->timezone(config('app.timezone'));
    @endphp

    <main class="mx-auto max-w-3xl px-4 py-8 sm:py-12">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
            <header class="bg-[#073823] px-5 py-6 text-white sm:px-7">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#eebc3f]">Notre Dame of Marbel University</p>
                        <h1 class="mt-1 text-xl font-black tracking-tight sm:text-2xl">Digital Signature Verification</h1>
                        <p class="mt-1 text-xs text-emerald-100">NDMU-RMAS institutional signature registry</p>
                    </div>
                    @if ($qrSvgDataUri)
                        <div class="h-24 w-24 shrink-0 rounded-xl bg-white p-2 shadow-sm">
                            <img src="{{ $qrSvgDataUri }}" alt="Digital signature verification QR code" class="h-full w-full">
                        </div>
                    @endif
                </div>
            </header>

            <div class="space-y-7 p-5 sm:p-7">
                <div @class([
                    'rounded-xl border p-4',
                    'border-emerald-300 bg-emerald-50 text-emerald-950' => $isValid,
                    'border-rose-300 bg-rose-50 text-rose-950' => ! $isValid,
                ])>
                    <div class="flex items-start gap-3">
                        <i class="ph {{ $isValid ? 'ph-seal-check text-emerald-600' : 'ph-warning-circle text-rose-600' }} mt-0.5 text-3xl"></i>
                        <div>
                            <h2 class="font-black">{{ $isValid ? 'VALID DIGITAL SIGNATURE ATTESTATION' : 'SIGNATURE VERIFICATION FAILED' }}</h2>
                            <p class="mt-1 text-xs leading-relaxed opacity-80">
                                @if ($isValid)
                                    The signer, form version, signature specimen, timestamp, and institutional attestation match the protected NDMU-RMAS record.
                                @else
                                    The stored evidence did not pass all integrity checks. Contact the NDMU research office before relying on this signature.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <section>
                    <h3 class="border-b border-slate-200 pb-2 text-xs font-black uppercase tracking-wider text-slate-700">Signature details</h3>
                    <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold text-slate-500">Signer</dt>
                            <dd class="mt-1 font-black text-slate-900">{{ $signature->signer_name_snapshot }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-500">Academic function</dt>
                            <dd class="mt-1 font-bold text-slate-900">{{ str($signature->actor_type)->headline() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-500">Signed action</dt>
                            <dd class="mt-1 font-bold text-slate-900">{{ str($signature->academic_action)->headline() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-500">Date and time signed</dt>
                            <dd class="mt-1 font-bold text-slate-900">
                                {{ $signedAt->format('F d, Y') }} &middot; {{ $signedAt->format('h:i:s A') }}
                                <span class="block text-[11px] font-medium text-slate-500">{{ config('app.timezone') }} &middot; {{ $signedAt->format('P') }}</span>
                            </dd>
                        </div>
                    </dl>
                </section>

                <section>
                    <h3 class="border-b border-slate-200 pb-2 text-xs font-black uppercase tracking-wider text-slate-700">Signed document</h3>
                    <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold text-slate-500">Official form</dt>
                            <dd class="mt-1 font-black text-slate-900">{{ strtoupper($definition->code) }} &middot; {{ $definition->title }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-500">Form version</dt>
                            <dd class="mt-1 font-bold text-slate-900">
                                Version {{ $version->version_number }} &middot; {{ $evaluation['is_current_version'] ? 'Current authoritative version' : 'Historical version' }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold text-slate-500">Signature verification reference</dt>
                            <dd class="mt-1 break-all font-mono text-xs font-bold text-[#0e5c3a]">{{ $verification->public_reference }}</dd>
                        </div>
                    </dl>
                </section>

                <details class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <summary class="cursor-pointer text-xs font-black uppercase tracking-wider text-slate-700">Cryptographic integrity details</summary>
                    <dl class="mt-4 space-y-3 text-xs">
                        <div>
                            <dt class="font-semibold text-slate-500">Attestation status</dt>
                            <dd class="mt-1 font-mono font-bold {{ $isValid ? 'text-emerald-700' : 'text-rose-700' }}">{{ $evaluation['status'] }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">Attestation key version</dt>
                            <dd class="mt-1 font-mono text-slate-700">{{ $evaluation['attestation_key_version'] }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">Signature specimen SHA-256</dt>
                            <dd class="mt-1 break-all font-mono text-[10px] text-slate-700">{{ $evaluation['signature_sha256'] }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">Signed form payload SHA-256</dt>
                            <dd class="mt-1 break-all font-mono text-[10px] text-slate-700">{{ $evaluation['version_payload_sha256'] }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">Institutional attestation fingerprint</dt>
                            <dd class="mt-1 break-all font-mono text-[10px] text-slate-700">{{ $evaluation['attestation_hash'] }}</dd>
                        </div>
                    </dl>
                </details>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-[11px] leading-relaxed text-amber-950">
                    This is an NDMU-RMAS institutional digital attestation backed by system integrity checks and audit evidence. It must not be represented as a Philippine National Public Key Infrastructure certificate or an eGovPH-issued signature unless NDMU completes an approved external PKI integration.
                </div>

                <footer class="border-t border-slate-200 pt-4 text-center text-[10px] text-slate-500">
                    Scan the QR code or visit the verification address to re-check this record. No IP address, device data, or private signature file is publicly disclosed.
                </footer>
            </div>
        </section>
    </main>
</body>
</html>
