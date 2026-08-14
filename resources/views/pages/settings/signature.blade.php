@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl space-y-8">
        <header class="border-b border-emerald-900/10 pb-5">
            <h1 class="text-2xl font-bold tracking-tight text-emerald-950">Digital Signature Enrollment</h1>
            <p class="mt-1 text-sm text-slate-600">
                Enroll your digital signature specimen to sign authoritative research forms and endorsements.
            </p>
        </header>

        @if (session('signature_success'))
            <div class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm font-semibold text-emerald-900">
                {{ session('signature_success') }}
            </div>
        @endif

        @if ($errors->has('signature'))
            <div class="rounded-lg border border-rose-300 bg-rose-50 p-4 text-sm font-semibold text-rose-900">
                {{ $errors->first('signature') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
            <!-- Current Signature Status -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Current Signature Specimen</h2>

                @if ($signature)
                    <div class="mt-4 space-y-4">
                        <div class="flex h-36 items-center justify-center rounded-lg border border-emerald-200 bg-slate-50 p-3">
                            <img
                                src="{{ route('signature.preview') }}"
                                alt="Your enrolled digital signature"
                                class="max-h-full max-w-full object-contain"
                            >
                        </div>
                        <div class="text-xs text-slate-500">
                            <p><strong class="text-slate-700">Enrolled on:</strong> {{ $signature->registered_at->format('F d, Y h:i A') }}</p>
                            <p><strong class="text-slate-700">Original File:</strong> {{ $signature->original_filename }}</p>
                        </div>
                        <form action="{{ route('signature.destroy') }}" method="POST" onsubmit="return confirm('Are you sure you want to remove your digital signature specimen? You will be unable to sign forms until a new specimen is uploaded.');">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="w-full rounded-md border border-rose-300 bg-rose-50 px-4 py-2 text-xs font-bold text-rose-700 hover:bg-rose-100"
                            >
                                Remove Signature Specimen
                            </button>
                        </form>
                    </div>
                @else
                    <div class="mt-4 flex h-36 flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-center text-xs text-slate-500">
                        <svg class="mb-2 h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        No digital signature specimen currently enrolled.
                    </div>
                @endif
            </div>

            <!-- Upload / Replace Form -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">
                    {{ $signature ? 'Replace Signature Specimen' : 'Enroll Signature Specimen' }}
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Upload a clear image of your handwritten signature on a plain white or transparent background. Supported formats: PNG, JPEG (max 2 MB).
                </p>

                <form action="{{ route('signature.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Signature File</label>
                        <input
                            type="file"
                            name="signature"
                            accept="image/png,image/jpeg"
                            required
                            class="mt-1 block w-full text-xs text-slate-500 file:mr-4 file:rounded-md file:border-0 file:bg-emerald-800 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-emerald-900"
                        >
                    </div>

                    <div class="rounded-lg bg-amber-50 p-3 text-[11px] text-amber-900">
                        <strong>Security Note:</strong> Your signature specimen is normalized and stored securely in private institutional storage. Applied signatures on historical form versions remain immutable even if you replace your specimen.
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-md bg-emerald-900 px-4 py-2.5 text-xs font-bold text-white shadow hover:bg-emerald-950"
                    >
                        {{ $signature ? 'Save & Replace Specimen' : 'Save & Enroll Specimen' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
