@props([
    'user',
    'active' => null,
    'badges' => [],
])

@php
    $navigation = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ph-squares-four', 'url' => route('facilitator.dashboard')],
        ['key' => 'approvals', 'label' => 'Pending Form Approvals', 'icon' => 'ph-check-square-offset', 'url' => route('official-forms.workspace.index'), 'badge' => 'forms', 'badge_label' => 'forms awaiting approval'],
        ['key' => 'classes', 'label' => 'Capstone Classes', 'icon' => 'ph-chalkboard-teacher', 'url' => route('facilitator.dashboard', ['tab' => 'classes']), 'badge' => 'classes', 'badge_label' => 'active classes'],
        ['key' => 'join-requests', 'label' => 'Join Requests', 'icon' => 'ph-user-plus', 'url' => route('facilitator.dashboard', ['tab' => 'join-requests']), 'badge' => 'join_requests', 'badge_label' => 'pending join requests'],
        ['key' => 'monitoring', 'label' => 'Research Monitoring', 'icon' => 'ph-chart-line-up', 'url' => route('facilitator.dashboard', ['tab' => 'monitoring'])],
        ['key' => 'screening', 'label' => 'Research Screening', 'icon' => 'ph-file-search', 'url' => route('facilitator.dashboard', ['tab' => 'screening']), 'badge' => 'screening', 'badge_label' => 'title proposals awaiting screening'],
        ['key' => 'defenses', 'label' => 'Defense Management', 'icon' => 'ph-calendar', 'url' => route('facilitator.dashboard', ['tab' => 'defenses']), 'badge' => 'defenses', 'badge_label' => 'upcoming defenses'],
        ['key' => 'statistics', 'label' => 'Research Statistics', 'icon' => 'ph-chart-bar', 'url' => route('facilitator.dashboard', ['tab' => 'statistics'])],
        ['key' => 'reports', 'label' => 'Research Reports', 'icon' => 'ph-file-text', 'url' => route('facilitator.reports.index')],
        ['key' => 'repository', 'label' => 'Research Repository', 'icon' => 'ph-folder-open', 'url' => route('facilitator.dashboard', ['tab' => 'repository'])],
    ];

    $baseClass = 'flex w-full items-center justify-between rounded-xl px-3.5 py-2.5 text-left text-[13px] transition-all duration-200';
    $activeClass = 'bg-[#eebc3f] font-bold text-[#09472d] shadow-md shadow-amber-950/20';
    $inactiveClass = 'font-semibold text-white/85 hover:translate-x-1 hover:bg-white/15 hover:text-white';
@endphp

<aside {{ $attributes->class('fixed inset-y-0 left-0 z-20 flex w-72 flex-col overflow-y-auto border-r border-emerald-800/40 bg-gradient-to-b from-[#09472d] via-[#0e5c3a] to-[#073622] text-white shadow-2xl') }}>
    <div class="shrink-0">
        <div class="flex items-center gap-3.5 p-6 pb-4">
            <div class="rounded-2xl border border-white/20 bg-gradient-to-br from-white/15 to-white/5 p-2 shadow-lg backdrop-blur-md">
                <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="NDMU Logo" width="96" height="96" class="h-10 w-auto drop-shadow-sm">
            </div>
            <div class="flex flex-col leading-none">
                <span class="font-heading text-xl font-black tracking-tight text-white">NDMU</span>
                <span class="mt-1 text-[9px] font-black uppercase tracking-[0.16em] text-[#eebc3f]">Research Management</span>
            </div>
        </div>

        <div class="flex items-center justify-center gap-2 px-6 py-1">
            <div class="h-px flex-1 bg-gradient-to-r from-transparent to-[#eebc3f]"></div>
            <div class="h-1 w-8 rounded-full bg-[#eebc3f]"></div>
            <div class="h-px flex-1 bg-gradient-to-l from-transparent to-[#eebc3f]"></div>
        </div>

        <div class="px-5 py-3">
            <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.06] p-3 shadow-inner backdrop-blur-xs">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-[#eebc3f] to-[#ffd76f] text-lg font-black text-[#09472d] shadow-md">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                </div>
                <div class="flex min-w-0 flex-col overflow-hidden leading-tight">
                    <span class="truncate text-xs font-bold text-white">{{ $user->name }}</span>
                    <span class="mt-0.5 truncate text-[10px] font-medium text-white/70">Research Facilitator</span>
                </div>
            </div>
        </div>
    </div>

    <nav class="flex-grow space-y-6 px-5 py-3" aria-label="Facilitator navigation">
        <div class="space-y-1">
            <div class="mb-2.5 flex items-center gap-2 px-3">
                <span class="h-3 w-1 rounded-full bg-[#eebc3f]"></span>
                <span class="text-[10px] font-black uppercase tracking-[0.18em] text-emerald-300/80">Navigation</span>
            </div>

            @foreach ($navigation as $item)
                <a
                    href="{{ $item['url'] }}"
                    @class([$baseClass, $active === $item['key'] ? $activeClass : $inactiveClass])
                    @if ($active === $item['key']) aria-current="page" @endif
                >
                    <span class="flex items-center gap-3">
                        <i class="ph {{ $item['icon'] }} text-lg"></i>
                        <span>{{ $item['label'] }}</span>
                    </span>
                    <span class="flex items-center gap-2">
                        @isset($item['badge'])
                            <x-sidebar-count-badge :count="$badges[$item['badge']] ?? 0" :label="$item['badge_label']" />
                        @endisset
                        @if ($active === $item['key'])
                            <span class="h-1.5 w-1.5 rounded-full bg-[#09472d]"></span>
                        @endif
                    </span>
                </a>
            @endforeach
        </div>

        <div class="space-y-1.5 pt-2">
            <div class="mb-2.5 flex items-center gap-2 px-3">
                <span class="h-3 w-1 rounded-full bg-[#eebc3f]"></span>
                <span class="text-[10px] font-black uppercase tracking-[0.18em] text-emerald-300/80">Research Forms</span>
            </div>
            <a href="{{ route('facilitator.dashboard', ['tab' => 'forms']) }}" @class([$baseClass, $active === 'forms' ? $activeClass : $inactiveClass])>
                <span class="flex items-center gap-3">
                    <i class="ph ph-file-pdf text-lg"></i>
                    <span>Official Forms</span>
                </span>
                <i class="ph ph-caret-right text-xs"></i>
            </a>
        </div>
    </nav>

    <div class="mt-auto shrink-0 px-5 pb-5">
        <div class="my-3 h-px w-full bg-gradient-to-r from-transparent via-white/15 to-transparent"></div>
        <div class="space-y-1">
            <a href="{{ route('facilitator.dashboard', ['tab' => 'notifications']) }}" @class([$baseClass, $active === 'notifications' ? $activeClass : $inactiveClass])>
                <span class="flex items-center gap-3"><i class="ph ph-bell text-lg"></i><span>Notifications</span></span>
                <x-sidebar-count-badge :count="$badges['notifications'] ?? 0" label="unread notifications" />
            </a>
            <a href="{{ route('facilitator.dashboard', ['tab' => 'settings']) }}" @class([$baseClass, $active === 'settings' ? $activeClass : $inactiveClass])>
                <span class="flex items-center gap-3"><i class="ph ph-gear text-lg"></i><span>Settings</span></span>
            </a>
            <form method="POST" action="{{ route('logout') }}" data-confirm-logout>
                @csrf
                <button type="submit" class="{{ $baseClass }} font-semibold text-white/70 hover:bg-rose-500/20 hover:text-rose-200">
                    <span class="flex items-center gap-3"><i class="ph ph-sign-out text-lg"></i><span>Logout</span></span>
                </button>
            </form>
        </div>
        <div class="mt-4 flex items-center justify-center gap-2 text-center text-[9px] font-medium text-white/40">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400/60"></span>
            <span>NDMU-RMAS © {{ now()->year }} · v1.0</span>
        </div>
    </div>
</aside>
