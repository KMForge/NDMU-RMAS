@props(['title', 'status' => null, 'date' => null, 'description' => null])

<article class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <h2 class="font-bold text-sm text-gray-800">{{ $title }}</h2>
            @if ($date)
                <p class="text-[10px] text-gray-400 mt-1">{{ \Illuminate\Support\Carbon::parse($date)->format('M j, Y g:i A') }}</p>
            @endif
        </div>
        @if ($status)
            <span class="shrink-0 px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-[9px] font-bold uppercase">
                {{ \Illuminate\Support\Str::headline($status) }}
            </span>
        @endif
    </div>
    @if ($description)
        <p class="text-xs text-gray-500 leading-6 mt-3">{{ $description }}</p>
    @endif
</article>
