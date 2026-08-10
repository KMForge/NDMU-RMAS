@props(['title', 'status' => null, 'date' => null, 'description' => null])

<article class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm transition-all duration-200 hover:shadow-md">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 space-y-1">
            <h2 class="font-bold text-base text-gray-900 leading-snug">{{ $title }}</h2>
            @if ($date)
                <p class="text-xs font-medium text-gray-500 flex items-center gap-1.5">
                    <i class="ph ph-calendar-blank text-gray-400 text-sm"></i>
                    <span>{{ \Illuminate\Support\Carbon::parse($date)->format('M j, Y g:i A') }}</span>
                </p>
            @endif
        </div>
        @if ($status)
            <span class="shrink-0 px-3.5 py-1.5 rounded-full bg-gray-100 text-gray-800 text-xs font-bold uppercase tracking-wider border border-gray-200/80">
                {{ \Illuminate\Support\Str::headline($status) }}
            </span>
        @endif
    </div>
    @if ($description)
        <p class="text-sm text-gray-700 leading-relaxed mt-4 pt-3 border-t border-gray-100 font-normal">{{ $description }}</p>
    @endif
</article>
