@props([
    'sections' => [],
])

<section
    x-data="{ portalFeatureSections: @js($sections) }"
    x-show="portalFeatureSections[activeTab]"
    x-cloak
    {{ $attributes->class(['relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#09472d] via-[#0e5c3a] to-[#0a4a2e] px-7 py-7 text-white shadow-lg shadow-emerald-950/20 border border-emerald-700/30 md:px-9']) }}
    aria-live="polite"
>
    <!-- Background Ambient Glow & Patterns -->
    <div aria-hidden="true" class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-[#eebc3f]/10 blur-3xl"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -left-12 -bottom-20 h-48 w-48 rounded-full bg-emerald-400/10 blur-2xl"></div>
    <div aria-hidden="true" class="pointer-events-none absolute right-8 top-1/2 -translate-y-1/2 opacity-[0.08]">
        <img src="{{ asset('images/ndmu_logo.png') }}" alt="" class="h-32 md:h-36 w-auto object-contain">
    </div>

    <div class="relative flex items-center gap-5 md:gap-6">
        <div class="hidden h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-[#f4c542] backdrop-blur-md border border-white/15 shadow-inner sm:flex transition-transform duration-300 hover:scale-105">
            <i class="ph text-3xl" :class="portalFeatureSections[activeTab]?.icon ?? 'ph-squares-four'"></i>
        </div>

        <div class="min-w-0 flex-1">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-black/20 backdrop-blur-xs border border-white/10 text-[10px] font-black uppercase tracking-[0.2em] text-[#eebc3f]">
                <span class="w-1.5 h-1.5 rounded-full bg-[#eebc3f] animate-pulse"></span>
                <span x-text="portalFeatureSections[activeTab]?.eyebrow ?? 'NDMU-RMAS'"></span>
            </div>
            <h1 class="mt-2 text-2xl font-black font-heading tracking-tight text-white md:text-3xl"
                x-text="portalFeatureSections[activeTab]?.title"></h1>
            <p class="mt-1 max-w-3xl text-xs font-normal leading-relaxed text-white/80 md:text-sm"
               x-text="portalFeatureSections[activeTab]?.description"></p>
        </div>
    </div>
</section>
