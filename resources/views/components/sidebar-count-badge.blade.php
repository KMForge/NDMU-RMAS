@props([
    'count' => 0,
    'label' => 'pending items',
    'badgeKey' => null,
])

@php
    $sidebarBadgeCount = max(0, (int) $count);
    $resolvedKey = $badgeKey;

    if (! $resolvedKey && is_string($label)) {
        $lowerLabel = strtolower($label);
        if (str_contains($lowerLabel, 'notification')) {
            $resolvedKey = 'notifications';
        } elseif (str_contains($lowerLabel, 'form')) {
            $resolvedKey = 'forms';
        } elseif (str_contains($lowerLabel, 'join request')) {
            $resolvedKey = 'join_requests';
        } elseif (str_contains($lowerLabel, 'consultation')) {
            $resolvedKey = 'consultation';
        } elseif (str_contains($lowerLabel, 'revision')) {
            $resolvedKey = 'revisions';
        } elseif (str_contains($lowerLabel, 'class') || str_contains($lowerLabel, 'invitation')) {
            $resolvedKey = 'classes';
        } elseif (str_contains($lowerLabel, 'screening')) {
            $resolvedKey = 'screening';
        } elseif (str_contains($lowerLabel, 'defense')) {
            $resolvedKey = 'defenses';
        }
    }
@endphp

<span
    x-data="{
        get currentCount() {
            @if ($resolvedKey)
                const liveBadges = $store?.liveState?.badges;
                if (liveBadges && liveBadges[{{ json_encode($resolvedKey) }}] !== undefined) {
                    return Math.max(0, parseInt(liveBadges[{{ json_encode($resolvedKey) }}], 10) || 0);
                }
            @endif
            return {{ $sidebarBadgeCount }};
        }
    }"
    x-show="currentCount > 0"
    x-cloak
    x-text="currentCount"
    {{ $attributes->class('inline-flex min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-black leading-none text-white shadow-sm transition-transform duration-200') }}
    :aria-label="currentCount + ' ' + @js($label)"
    :title="currentCount + ' ' + @js($label)"
    aria-label="{{ $sidebarBadgeCount }} {{ $label }}"
    title="{{ $sidebarBadgeCount }} {{ $label }}"
    @if ($sidebarBadgeCount <= 0 && ! $resolvedKey) style="display: none;" @endif
>{{ $sidebarBadgeCount > 0 ? $sidebarBadgeCount : '' }}</span>
