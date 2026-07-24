@props(['label', 'value' => null])

<div>
    <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider block">{{ $label }}</span>
    <span class="text-xs font-bold text-gray-800 block mt-1">
        {{ filled($value) ? \Illuminate\Support\Str::headline((string) $value) : 'Not available' }}
    </span>
</div>
