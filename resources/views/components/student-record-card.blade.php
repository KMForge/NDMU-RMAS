@props(['title', 'status' => null, 'date' => null, 'description' => null])

<article class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-2xs transition-all duration-200 hover:shadow-md hover:border-slate-300 relative overflow-hidden group">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="min-w-0 space-y-1">
            <h2 class="font-black text-base text-slate-900 leading-snug">{{ $title }}</h2>
            @if ($date)
                <p class="text-xs font-semibold text-slate-500 flex items-center gap-1.5">
                    <i class="ph ph-calendar-blank text-slate-400 text-sm"></i>
                    <span>{{ \Illuminate\Support\Carbon::parse($date)->format('M j, Y g:i A') }}</span>
                </p>
            @endif
        </div>
        @if ($status)
            <span class="shrink-0 px-3.5 py-1.5 rounded-full bg-emerald-50 text-[#0e5c3a] text-[10px] font-black uppercase tracking-wider border border-emerald-200 shadow-2xs">
                {{ \Illuminate\Support\Str::headline($status) }}
            </span>
        @endif
    </div>
    @if ($description)
        <p class="text-xs text-slate-600 leading-relaxed mt-4 pt-3.5 border-t border-slate-100 font-medium">{{ $description }}</p>
    @endif
</article>

