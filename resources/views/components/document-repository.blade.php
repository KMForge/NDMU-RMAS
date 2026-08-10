@props(['documents', 'filters', 'stats', 'stageOptions', 'statusOptions'])

<div class="space-y-6">
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([['Total Current', $stats['total'], 'ph-files'], ['Accepted', $stats['accepted'], 'ph-check-circle'], ['Pending', $stats['pending'], 'ph-clock'], ['Under Review', $stats['under_review'], 'ph-magnifying-glass']] as [$label, $value, $icon])
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><div><p class="text-2xl font-black text-gray-900">{{ $value }}</p><p class="mt-1 text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $label }}</p></div><i class="ph {{ $icon }} text-xl text-[#0e5c3a]"></i></div></div>
        @endforeach
    </div>

    <form method="GET" class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <input type="hidden" name="tab" value="repository">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <input name="repository_q" value="{{ $filters['search'] }}" maxlength="100" placeholder="Search filename..." class="rounded-xl border border-gray-200 px-4 py-3 text-xs xl:col-span-2">
            <select name="repository_stage" class="rounded-xl border border-gray-200 px-3 py-3 text-xs"><option value="all">All stages</option>@foreach ($stageOptions as $value => $label)<option value="{{ $value }}" @selected($filters['stage'] === $value)>{{ $label }}</option>@endforeach</select>
            <select name="repository_status" class="rounded-xl border border-gray-200 px-3 py-3 text-xs"><option value="all">All statuses</option>@foreach ($statusOptions as $value => $label)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>@endforeach</select>
            <select name="repository_type" class="rounded-xl border border-gray-200 px-3 py-3 text-xs"><option value="all" @selected($filters['file_type'] === 'all')>All file types</option><option value="pdf" @selected($filters['file_type'] === 'pdf')>PDF</option><option value="docx" @selected($filters['file_type'] === 'docx')>DOCX</option></select>
            <select name="repository_version" class="rounded-xl border border-gray-200 px-3 py-3 text-xs"><option value="current" @selected($filters['version'] === 'current')>Current</option><option value="all" @selected($filters['version'] === 'all')>All versions</option></select>
            <select name="repository_sort" class="rounded-xl border border-gray-200 px-3 py-3 text-xs"><option value="newest" @selected($filters['sort'] === 'newest')>Newest</option><option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest</option></select>
            <div class="flex gap-2 xl:col-span-5"><button class="rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-xs font-bold text-white">Apply Filters</button><a href="{{ url()->current() }}?tab=repository" class="rounded-xl border border-gray-200 px-5 py-2.5 text-xs font-bold text-gray-600">Reset</a></div>
        </div>
    </form>

    @if ($documents->isEmpty())
        <x-student-empty-state message="No documents match your current repository view." />
    @else
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($documents as $document)
                <article class="rounded-2xl border bg-white p-5 shadow-sm {{ $document->is_current ? 'border-emerald-100' : 'border-gray-200 opacity-80' }}">
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="text-[10px] font-black uppercase tracking-wider text-[#b88718]">{{ $document->stageLabel() }}</p><h3 class="mt-1 truncate text-sm font-extrabold text-gray-900">{{ $document->original_filename }}</h3></div><span class="rounded-full px-2.5 py-1 text-[9px] font-black {{ $document->is_current ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ $document->is_current ? 'CURRENT' : 'VOID' }}</span></div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-[10px]">
                        <div><dt class="font-bold uppercase text-gray-400">Group</dt><dd class="mt-1 font-semibold text-gray-700">{{ $document->researchClassGroup?->name ?? 'Legacy record' }}</dd></div><div><dt class="font-bold uppercase text-gray-400">Research Title</dt><dd class="mt-1 font-semibold text-gray-500">Research title not yet finalized</dd></div>
                        <div><dt class="font-bold uppercase text-gray-400">Version</dt><dd class="mt-1 font-semibold text-gray-700">V{{ $document->version_number }}</dd></div><div><dt class="font-bold uppercase text-gray-400">Review Status</dt><dd class="mt-1 font-semibold text-gray-700">{{ \Illuminate\Support\Str::headline($document->status->value) }}</dd></div>
                        <div><dt class="font-bold uppercase text-gray-400">File</dt><dd class="mt-1 font-semibold text-gray-700">{{ strtoupper($document->file_type) }} · {{ $document->formattedFileSize() }}</dd></div><div><dt class="font-bold uppercase text-gray-400">Submitted</dt><dd class="mt-1 font-semibold text-gray-700">{{ $document->submitted_at?->format('M j, Y g:i A') }}</dd></div>
                        <div class="col-span-2"><dt class="font-bold uppercase text-gray-400">Uploader</dt><dd class="mt-1 font-semibold text-gray-700">{{ $document->user?->name ?? 'Unavailable' }}</dd></div>
                    </dl>
                    <div class="mt-5 grid grid-cols-3 gap-2 border-t border-gray-100 pt-4"><a href="{{ route('documents.view', $document) }}" class="rounded-lg border border-emerald-200 px-2 py-2 text-center text-[10px] font-bold text-emerald-800">View</a><a href="{{ route('documents.download', $document) }}" class="rounded-lg bg-blue-50 px-2 py-2 text-center text-[10px] font-bold text-blue-700">Download</a><a href="{{ route('documents.history', $document) }}" class="rounded-lg bg-gray-100 px-2 py-2 text-center text-[10px] font-bold text-gray-700">History</a></div>
                </article>
            @endforeach
        </div>
        <div>{{ $documents->links() }}</div>
    @endif
</div>
