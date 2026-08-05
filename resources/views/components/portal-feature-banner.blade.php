@props([
    'sections' => [],
])

<section
    x-data="{ portalFeatureSections: @js($sections) }"
    x-show="portalFeatureSections[activeTab]"
    x-cloak
    {{ $attributes->class(['relative overflow-hidden rounded-3xl bg-[#0e5c3a] px-6 py-6 text-white shadow-sm md:px-8']) }}
    aria-live="polite"
>
    <div aria-hidden="true" class="pointer-events-none absolute -right-10 -top-20 h-48 w-48 rounded-full bg-white/5"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -right-2 -top-12 h-32 w-32 rounded-full bg-white/5"></div>

    <div class="relative flex items-center gap-5">
        <div class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-[#f4c542] sm:flex">
            <i class="ph text-2xl" :class="portalFeatureSections[activeTab]?.icon ?? 'ph-squares-four'"></i>
        </div>

        <div class="min-w-0">
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#f4c542]"
               x-text="portalFeatureSections[activeTab]?.eyebrow ?? 'NDMU-RMAS'"></p>
            <h1 class="mt-2 text-2xl font-black tracking-tight md:text-3xl"
                x-text="portalFeatureSections[activeTab]?.title"></h1>
            <p class="mt-1 max-w-3xl text-xs leading-5 text-white/80 md:text-sm"
               x-text="portalFeatureSections[activeTab]?.description"></p>
        </div>
    </div>
</section>
