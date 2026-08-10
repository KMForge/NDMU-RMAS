@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div class="rounded-3xl bg-[#0e5c3a] p-7 text-white shadow-lg">
        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#f4c542]">Secure Document Details</p>
        <h1 class="mt-2 break-words text-2xl font-black">{{ $document->original_filename }}</h1>
        <p class="mt-2 text-sm text-white/75">DOCX files are not rendered in the browser. Review the trusted metadata below or download the original file.</p>
    </div>
    <div class="rounded-3xl border border-gray-100 bg-white p-7 shadow-sm">
        <dl class="grid gap-5 sm:grid-cols-2">
            <div><dt class="text-xs font-bold uppercase text-gray-400">Research Group</dt><dd class="mt-1 font-semibold">{{ $document->researchClassGroup?->name ?? 'Legacy record' }}</dd></div>
            <div><dt class="text-xs font-bold uppercase text-gray-400">Research Title</dt><dd class="mt-1 font-semibold text-gray-500">Research title not yet finalized</dd></div>
            <div><dt class="text-xs font-bold uppercase text-gray-400">Stage</dt><dd class="mt-1 font-semibold">{{ $document->stageLabel() }}</dd></div>
            <div><dt class="text-xs font-bold uppercase text-gray-400">Version State</dt><dd class="mt-1 font-semibold">V{{ $document->version_number }} · {{ $document->is_current ? 'CURRENT' : 'VOID' }}</dd></div>
            <div><dt class="text-xs font-bold uppercase text-gray-400">Review Status</dt><dd class="mt-1 font-semibold">{{ \Illuminate\Support\Str::headline($document->status->value) }}</dd></div>
            <div><dt class="text-xs font-bold uppercase text-gray-400">File</dt><dd class="mt-1 font-semibold">{{ strtoupper($document->file_type) }} · {{ $document->formattedFileSize() }}</dd></div>
            <div><dt class="text-xs font-bold uppercase text-gray-400">Uploader</dt><dd class="mt-1 font-semibold">{{ $document->user?->name ?? 'Unavailable' }}</dd></div>
            <div><dt class="text-xs font-bold uppercase text-gray-400">Submitted</dt><dd class="mt-1 font-semibold">{{ $document->submitted_at?->format('M j, Y g:i A') }}</dd></div>
        </dl>
        <div class="mt-7 flex flex-wrap gap-3"><a href="{{ route('documents.download', $document) }}" class="rounded-xl bg-[#0e5c3a] px-5 py-3 text-sm font-bold text-white">Download DOCX</a><a href="{{ route('documents.history', $document) }}" class="rounded-xl border border-gray-200 px-5 py-3 text-sm font-bold">Version History</a><a href="{{ url()->previous() }}" class="rounded-xl border border-gray-200 px-5 py-3 text-sm font-bold">Back</a></div>
    </div>
</div>
@endsection
