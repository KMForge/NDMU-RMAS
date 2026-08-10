@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="rounded-3xl bg-[#0e5c3a] p-7 text-white shadow-lg"><p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#f4c542]">Version History</p><h1 class="mt-2 text-2xl font-black">{{ $document->stageLabel() }}</h1><p class="mt-1 text-sm text-white/75">{{ $document->researchClassGroup?->name ?? 'Legacy record' }}</p></div>
    <div class="space-y-3">
        @forelse ($versions as $version)
            <div class="rounded-2xl border bg-white p-5 shadow-sm {{ $version->is_current ? 'border-emerald-200' : 'border-gray-200' }}">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"><div><div class="flex items-center gap-2"><span class="font-black">V{{ $version->version_number }}</span><span class="rounded-full px-2 py-1 text-[9px] font-black {{ $version->is_current ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ $version->is_current ? 'CURRENT' : 'VOID' }}</span></div><p class="mt-2 font-bold text-gray-900">{{ $version->original_filename }}</p><p class="mt-1 text-xs text-gray-500">{{ strtoupper($version->file_type) }} · {{ $version->formattedFileSize() }} · {{ \Illuminate\Support\Str::headline($version->status->value) }} · {{ $version->user?->name ?? 'Unavailable' }} · {{ $version->submitted_at?->format('M j, Y g:i A') }}</p></div><div class="flex gap-2"><a href="{{ route('documents.view', $version) }}" class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-bold text-emerald-800">View</a><a href="{{ route('documents.download', $version) }}" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700">Download</a></div></div>
            </div>
        @empty
            <x-student-empty-state message="No previous versions are available." />
        @endforelse
    </div>
    <a href="{{ url()->previous() }}" class="inline-flex rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-bold">Back to Repository</a>
</div>
@endsection
