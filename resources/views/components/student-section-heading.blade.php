@props(['title', 'description' => null])

<div class="flex items-center gap-3">
    <span class="w-2.5 h-8 rounded-full bg-[#0e5c3a]"></span>
    <div>
        <h1 class="text-xl sm:text-2xl font-black font-heading tracking-tight text-slate-900">{{ $title }}</h1>
        @if ($description)
            <p class="text-xs text-slate-500 font-medium mt-0.5">{{ $description }}</p>
        @endif
    </div>
</div>

