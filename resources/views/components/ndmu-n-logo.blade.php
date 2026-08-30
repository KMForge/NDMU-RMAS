@props([
    'size' => 'md', // sm (36px), md (48px), lg (56px), xl (64px)
    'showGlow' => true,
])

@php
    $sizeClasses = match($size) {
        'sm' => 'w-9 h-9',
        'md' => 'w-12 h-12',
        'lg' => 'w-14 h-14',
        'xl' => 'w-16 h-16',
        default => 'w-12 h-12',
    };

    $svgSizes = match($size) {
        'sm' => 'w-5 h-5',
        'md' => 'w-7 h-7',
        'lg' => 'w-8 h-8',
        'xl' => 'w-10 h-10',
        default => 'w-7 h-7',
    };
@endphp

<div {{ $attributes->merge(['class' => 'relative flex items-center justify-center group shrink-0']) }}>
    @if ($showGlow)
        <div class="absolute -inset-1 rounded-2xl bg-gradient-to-tr from-[#0e5c3a] via-[#eebc3f] to-emerald-400 opacity-35 blur-md group-hover:opacity-65 transition duration-500"></div>
    @endif

    <div class="relative {{ $sizeClasses }} rounded-2xl bg-gradient-to-br from-[#052e1d] via-[#0e5c3a] to-[#073823] border border-[#eebc3f]/50 p-2 flex items-center justify-center shadow-lg shadow-emerald-950/40 transition-transform duration-300 group-hover:scale-105">
        <svg class="{{ $svgSizes }} drop-shadow-sm" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <!-- Gold Diagonal Gradient -->
                <linearGradient id="ndmu-n-gold-grad" x1="12" y1="8" x2="28" y2="32" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#fff1a8" />
                    <stop offset="45%" stop-color="#eebc3f" />
                    <stop offset="100%" stop-color="#c88e1a" />
                </linearGradient>

                <!-- Emerald/White Left Upright -->
                <linearGradient id="ndmu-n-left-grad" x1="8" y1="8" x2="16" y2="32" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#ffffff" />
                    <stop offset="100%" stop-color="#e2e8f0" />
                </linearGradient>

                <!-- Emerald/White Right Upright -->
                <linearGradient id="ndmu-n-right-grad" x1="24" y1="8" x2="32" y2="32" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#ffffff" />
                    <stop offset="100%" stop-color="#cbd5e1" />
                </linearGradient>

                <!-- Subtle Inner Shadow Filter -->
                <filter id="n-emboss" x="-10%" y="-10%" width="120%" height="120%">
                    <feDropShadow dx="0" dy="1" stdDeviation="0.8" flood-color="#041a10" flood-opacity="0.6"/>
                </filter>
            </defs>

            <!-- Left Vertical Column -->
            <path
                d="M9 10C9 8.61929 10.1193 7.5 11.5 7.5H13C14.3807 7.5 15.5 8.61929 15.5 10V30C15.5 31.3807 14.3807 32.5 13 32.5H11.5C10.1193 32.5 9 31.3807 9 30V10Z"
                fill="url(#ndmu-n-left-grad)"
                filter="url(#n-emboss)"
            />

            <!-- Dynamic Diagonal Ribbon in University Gold -->
            <path
                d="M12.5 8.5L27.5 31.5C28.2 32.6 29.7 32.7 30.5 31.8C30.8 31.4 31 30.9 31 30.4V10C31 8.61929 29.8807 7.5 28.5 7.5H27C25.6193 7.5 24.5 8.61929 24.5 10V20.5L16.2 8.2C15.5 7.2 14.1 7.1 13.2 8C12.8 8.4 12.5 8.9 12.5 8.5Z"
                fill="url(#ndmu-n-gold-grad)"
                filter="url(#n-emboss)"
            />

            <!-- Right Vertical Column -->
            <path
                d="M24.5 10C24.5 8.61929 25.6193 7.5 27 7.5H28.5C29.8807 7.5 31 8.61929 31 10V30C31 31.3807 29.8807 32.5 28.5 32.5H27C25.6193 32.5 24.5 31.3807 24.5 30V10Z"
                fill="url(#ndmu-n-right-grad)"
                opacity="0.95"
            />

            <!-- Academic Star / Gold Accent Sparkle -->
            <path
                d="M20 5L20.8 7.2L23 8L20.8 8.8L20 11L19.2 8.8L17 8L19.2 7.2L20 5Z"
                fill="#fef08a"
            />
        </svg>
    </div>
</div>
