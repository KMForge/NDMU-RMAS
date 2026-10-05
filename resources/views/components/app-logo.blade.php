@props([
    'variant' => 'default', // 'default', 'small', 'sidebar', 'header', 'auth', 'watermark'
    'alt' => 'Notre Dame of Marbel University - Research Management and Archiving System Logo',
])

@php
    $isSmall = in_array($variant, ['small', 'sidebar', 'header'], true);
    $src = $isSmall ? asset('images/ndmu-logo-small.png') : asset('images/ndmu_logo.png');
    $intrinsicWidth = $isSmall ? 96 : 320;
    $intrinsicHeight = $isSmall ? 96 : 320;

    $defaultClass = match($variant) {
        'small', 'sidebar', 'header' => 'h-10 w-auto object-contain drop-shadow-sm',
        'auth' => 'h-11 sm:h-13 w-auto object-contain transition-transform duration-300 group-hover:scale-105 drop-shadow-md',
        'watermark' => 'h-36 md:h-44 w-auto object-contain pointer-events-none select-none opacity-10',
        default => 'h-10 w-auto object-contain',
    };
@endphp

<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    width="{{ $intrinsicWidth }}"
    height="{{ $intrinsicHeight }}"
    loading="lazy"
    decoding="async"
    {{ $attributes->merge(['class' => $defaultClass]) }}
/>
