@props([
    'count' => 0,
    'label' => 'pending items',
])

@php($sidebarBadgeCount = max(0, (int) $count))

@if ($sidebarBadgeCount > 0)
    <span
        {{ $attributes->class('inline-flex min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-black leading-none text-white shadow-sm') }}
        aria-label="{{ $sidebarBadgeCount }} {{ $label }}"
        title="{{ $sidebarBadgeCount }} {{ $label }}"
    >{{ $sidebarBadgeCount }}</span>
@endif
