@extends('layouts.app')

@section('content')
@php
    $comments = $document->reviewComments ?? collect();
    $unresolvedCount = $comments->whereNull('resolved_at')->count();
    $viewerComments = $comments->map(fn ($comment) => [
        'id' => $comment->id,
        'name' => $comment->author?->name ?? 'Reviewer',
        'role' => $comment->author?->roles?->first()?->name
            ? \Illuminate\Support\Str::headline($comment->author->roles->first()->name)
            : 'Reviewer',
        'page_number' => $comment->page_number,
        'severity' => $comment->severity,
        'comment' => $comment->comment,
        'resolved' => $comment->resolved_at !== null,
    ])->values();
@endphp
<div class="mx-auto max-w-7xl space-y-5" x-data="{
    zoomLevel: 100,
    showDetails: false,
    showComments: true,
    zoomIn() { if (this.zoomLevel < 150) this.zoomLevel += 10; },
    zoomOut() { if (this.zoomLevel > 70) this.zoomLevel -= 10; },
    resetZoom() { this.zoomLevel = 100; },
    jumpToPage(page) {
        if (!page) return;
        const target = document.getElementById(`pdf-page-${page}`) || document.getElementById(`doc-page-${page}`);
        target?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        target?.classList.add('review-page-highlight');
        window.setTimeout(() => target?.classList.remove('review-page-highlight'), 1800);
    }
}">
    <!-- Document Viewer Header & Navigation -->
    <div class="sticky top-2 z-20 rounded-2xl bg-white/95 backdrop-blur-md p-4 sm:p-5 shadow-md border border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5 min-w-0">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="h-10 w-10 shrink-0 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-700 transition shadow-2xs">
                <i class="ph ph-arrow-left text-lg font-bold"></i>
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-black uppercase text-[#0e5c3a]">
                        {{ strtoupper($document->file_type) }}
                    </span>
                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700">
                        V{{ $document->version_number }} {{ $document->is_current ? '· Current' : '· Void' }}
                    </span>
                    <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-[10px] font-bold text-blue-700 border border-blue-200">
                        {{ \Illuminate\Support\Str::headline($document->status->value) }}
                    </span>
                    @if ($comments->isNotEmpty())
                        <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-bold text-amber-800 border border-amber-200 flex items-center gap-1">
                            <i class="ph ph-chat-circle-dots"></i> {{ $comments->count() }} Attached {{ \Illuminate\Support\Str::plural('Critique', $comments->count()) }}
                        </span>
                    @endif
                </div>
                <h1 class="mt-1 text-base sm:text-lg font-black text-slate-900 truncate" title="{{ $document->original_filename }}">
                    {{ $document->original_filename }}
                </h1>
                <p class="text-xs text-slate-500 truncate">
                    {{ $document->researchClassGroup?->name ?? 'Academic Context' }} · Submitted by <strong class="text-slate-700">{{ $document->user?->name ?? 'Student' }}</strong> {{ $document->submitted_at?->diffForHumans() }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 shrink-0 flex-wrap justify-end">
            <!-- Zoom Controls (for DOCX & PDF) -->
            @if ($document->file_type === 'docx')
                <div class="inline-flex items-center rounded-xl bg-slate-100 p-1 border border-slate-200 shadow-2xs">
                    <button type="button" @click="zoomOut()" class="h-7 w-7 rounded-lg hover:bg-white flex items-center justify-center text-slate-700 transition cursor-pointer" title="Zoom Out">
                        <i class="ph ph-minus text-xs font-bold"></i>
                    </button>
                    <span class="px-2 text-xs font-mono font-bold text-slate-700" x-text="zoomLevel + '%'">100%</span>
                    <button type="button" @click="zoomIn()" class="h-7 w-7 rounded-lg hover:bg-white flex items-center justify-center text-slate-700 transition cursor-pointer" title="Zoom In">
                        <i class="ph ph-plus text-xs font-bold"></i>
                    </button>
                    <button type="button" @click="resetZoom()" class="ml-1 px-2 py-0.5 text-[10px] font-bold text-slate-500 hover:text-slate-900 rounded-md hover:bg-white transition cursor-pointer" title="Reset Zoom">
                        Reset
                    </button>
                </div>
            @endif

            @if ($comments->isNotEmpty())
                <!-- Toggle Comments Side/Drawer -->
                <button
                    type="button"
                    @click="showComments = !showComments"
                    :class="showComments ? 'bg-amber-500 text-white border-amber-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                    class="inline-flex items-center gap-1.5 rounded-xl border px-3.5 py-2 text-xs font-bold transition shadow-2xs cursor-pointer"
                >
                    <i class="ph ph-chats-circle text-sm"></i>
                    <span>Critiques ({{ $comments->count() }})</span>
                </button>
            @endif

            <!-- Toggle Details Info Drawer -->
            <button
                type="button"
                @click="showDetails = !showDetails"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs cursor-pointer"
            >
                <i class="ph ph-info text-sm"></i>
                <span x-text="showDetails ? 'Hide Details' : 'Details'">Details</span>
            </button>

            <a href="{{ route('documents.history', $document) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                <i class="ph ph-clock-counter-clockwise text-sm"></i>
                <span>History</span>
            </a>

            <a href="{{ route('documents.download', $document) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-[#0e5c3a] hover:bg-[#073823] px-4 py-2 text-xs font-bold text-white transition shadow-xs">
                <i class="ph ph-download-simple text-sm font-bold"></i>
                <span>Download</span>
            </a>
        </div>
    </div>

    <!-- Collapsible Metadata Panel -->
    <div x-show="showDetails" x-cloak x-collapse class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
        <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-3">Document Security & Attribution Metadata</h2>
        <dl class="grid gap-4 sm:grid-cols-2 md:grid-cols-4 text-xs">
            <div>
                <dt class="font-bold text-slate-400 uppercase text-[10px]">Research Group</dt>
                <dd class="mt-0.5 font-semibold text-slate-800">{{ $document->researchClassGroup?->name ?? 'None' }}</dd>
            </div>
            <div>
                <dt class="font-bold text-slate-400 uppercase text-[10px]">Research Class</dt>
                <dd class="mt-0.5 font-semibold text-slate-800">{{ $document->researchClassGroup?->researchClass?->name ?? 'General Repository' }}</dd>
            </div>
            <div>
                <dt class="font-bold text-slate-400 uppercase text-[10px]">Research Stage</dt>
                <dd class="mt-0.5 font-semibold text-slate-800">{{ $document->stageLabel() }}</dd>
            </div>
            <div>
                <dt class="font-bold text-slate-400 uppercase text-[10px]">File Size</dt>
                <dd class="mt-0.5 font-semibold text-slate-800">{{ $document->formattedFileSize() }}</dd>
            </div>
            <div>
                <dt class="font-bold text-slate-400 uppercase text-[10px]">Uploaded By</dt>
                <dd class="mt-0.5 font-semibold text-slate-800">{{ $document->user?->name ?? 'Unavailable' }} ({{ $document->user?->email ?? '' }})</dd>
            </div>
            <div>
                <dt class="font-bold text-slate-400 uppercase text-[10px]">Submitted At</dt>
                <dd class="mt-0.5 font-semibold text-slate-800">{{ $document->submitted_at?->format('M j, Y g:i A') ?? 'Draft' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="font-bold text-slate-400 uppercase text-[10px]">SHA-256 Storage Integrity Checksum</dt>
                <dd class="mt-0.5 font-mono text-[11px] text-slate-600 truncate" title="{{ $document->file_hash }}">
                    {{ $document->file_hash ?: 'Verified on Server' }}
                </dd>
            </div>
        </dl>
    </div>

    <!-- Main Workspace Grid: Document Viewport + Attached Critiques Panel -->
    <div class="grid gap-5 {{ $comments->isNotEmpty() ? 'lg:grid-cols-12' : 'grid-cols-1' }} items-start">
        
        <!-- Left / Center: Document Viewport -->
        <div class="{{ $comments->isNotEmpty() ? 'lg:col-span-8' : 'w-full' }} min-h-[750px] rounded-3xl bg-slate-100/90 border border-slate-200/80 p-4 sm:p-6 shadow-inner flex flex-col items-center justify-center overflow-auto">
            @if ($document->file_type === 'pdf')
                <!-- PDF.js viewer with page-attached reviewer notes -->
                <div
                    class="w-full"
                    data-pdf-viewer
                    data-pdf-url="{{ route('documents.view', [$document, 'raw' => 1]) }}"
                    data-pdf-comments="{{ $viewerComments->toJson() }}"
                >
                    <div data-pdf-status class="py-24 text-center">
                        <div class="mb-3 inline-block h-10 w-10 animate-spin rounded-full border-4 border-emerald-600 border-r-transparent"></div>
                        <p class="text-sm font-bold text-slate-700">Rendering PDF and attached reviewer notes...</p>
                        <p class="mt-1 text-xs text-slate-500">Preparing the secured manuscript preview.</p>
                    </div>
                    <div data-pdf-content class="hidden w-full"></div>
                </div>
            @elseif ($document->file_type === 'docx')
                <!-- In-System Client-Side DOCX Rendering Viewport -->
                <div
                    class="w-full flex flex-col items-center"
                    data-docx-viewer
                    data-docx-url="{{ route('documents.view', [$document, 'raw' => 1]) }}"
                    data-docx-comments="{{ $viewerComments->toJson() }}"
                >
                    <!-- Loading State Spinner -->
                    <div data-docx-status class="py-24 text-center">
                        <div class="inline-block h-10 w-10 animate-spin rounded-full border-4 border-emerald-600 border-r-transparent mb-3"></div>
                        <p class="text-sm font-bold text-slate-700">Rendering DOCX Document in System...</p>
                        <p class="text-xs text-slate-500 mt-1">Processing document styles, paragraphs, and tables...</p>
                    </div>

                    <!-- Rendered DOCX Container with Zoom Transformation -->
                    <div
                        data-docx-content
                        class="hidden w-full transition-transform duration-150 origin-top"
                        :style="`transform: scale(${zoomLevel / 100});`"
                    ></div>
                </div>
            @else
                <div class="py-20 text-center text-slate-500">
                    <i class="ph ph-file-dashed text-4xl mb-2 text-slate-400"></i>
                    <p class="text-sm font-bold text-slate-700">Preview is unavailable for {{ strtoupper($document->file_type) }} files.</p>
                    <a href="{{ route('documents.download', $document) }}" class="mt-3 inline-block rounded-xl bg-[#0e5c3a] px-4 py-2 text-xs font-bold text-white">
                        Download Document File
                    </a>
                </div>
            @endif
        </div>

        @if ($comments->isNotEmpty())
            <!-- Right: Attached Manuscript Critiques & Review Comments Sidebar -->
            <aside x-show="showComments" x-cloak class="lg:col-span-4 bg-white rounded-3xl border border-slate-200/80 shadow-xs p-5 space-y-4 lg:sticky lg:top-24">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <i class="ph ph-chat-centered-dots text-lg text-amber-600"></i>
                        <div>
                            <h2 class="text-xs font-black uppercase tracking-wider text-slate-800">Attached Critiques</h2>
                            <p class="text-[10px] text-slate-400 font-semibold">Select a page comment to jump to its note</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-amber-50 border border-amber-200 px-2 py-0.5 text-[10px] font-black text-amber-800">
                        {{ $comments->count() }}
                    </span>
                </div>

                <!-- Comments List -->
                <div class="space-y-3.5 max-h-[650px] overflow-y-auto pr-1">
                    @foreach ($comments as $comment)
                        @php
                            $authorName = $comment->author?->name ?? 'Reviewer';
                            $roles = $comment->author?->roles?->pluck('name')->toArray() ?? [];
                            $roleLabel = match(true) {
                                in_array('panelist', $roles, true) => 'Defense Panelist',
                                in_array('faculty', $roles, true) => 'Faculty Reviewer',
                                in_array('program-coordinator', $roles, true) => 'Program Coordinator',
                                default => 'Reviewer',
                            };
                            $borderClass = match($comment->severity) {
                                'critical' => 'border-l-4 border-l-red-500 border-slate-200 bg-red-50/20',
                                'revision' => 'border-l-4 border-l-amber-500 border-slate-200 bg-amber-50/20',
                                default => 'border-l-4 border-l-blue-500 border-slate-200 bg-blue-50/20',
                            };
                            $badgeClass = match($comment->severity) {
                                'critical' => 'bg-red-100 text-red-800 border-red-200',
                                'revision' => 'bg-amber-100 text-amber-800 border-amber-200',
                                default => 'bg-blue-100 text-blue-800 border-blue-200',
                            };
                        @endphp
                        <button type="button" @click="jumpToPage({{ $comment->page_number ?: 'null' }})" class="block w-full rounded-2xl border p-3.5 space-y-2.5 text-left shadow-2xs transition hover:-translate-y-0.5 hover:shadow-sm {{ $borderClass }}">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-black text-xs text-slate-800">{{ $authorName }}</span>
                                        <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[9px] font-bold text-slate-600">
                                            {{ $roleLabel }}
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-semibold block mt-0.5">
                                        {{ $comment->created_at?->format('M j, Y g:i A') }}
                                    </span>
                                </div>
                                <span class="rounded-md px-2 py-0.5 text-[9px] font-black uppercase border {{ $badgeClass }}">
                                    {{ $comment->severity }}
                                </span>
                            </div>

                            <p class="text-xs text-slate-700 font-medium leading-relaxed whitespace-pre-line bg-white/70 rounded-xl p-2.5 border border-slate-100">
                                {{ $comment->comment }}
                            </p>

                            <div class="flex items-center justify-between pt-1 text-[10px] font-bold text-slate-500">
                                <span class="flex items-center gap-1">
                                    <i class="ph ph-file-text"></i>
                                    {{ $comment->page_number ? 'Page ' . $comment->page_number : 'General Reference' }}
                                </span>
                                @if ($comment->resolved_at)
                                    <span class="text-emerald-700 font-black flex items-center gap-1">
                                        <i class="ph ph-check-circle"></i> Resolved
                                    </span>
                                @else
                                    <span class="text-amber-700 font-black flex items-center gap-1">
                                        <i class="ph ph-clock"></i> Pending Action
                                    </span>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            </aside>
        @endif

    </div>
</div>
@endsection
