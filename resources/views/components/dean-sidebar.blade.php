@props([
    'active' => 'dashboard',
    'badges' => [],
    'stats' => [],
])

@php
    $dean = auth()->user();
    $items = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ph-squares-four'],
        ['key' => 'pending', 'label' => 'Pending Approvals', 'icon' => 'ph-clipboard-text', 'count' => $badges['pending'] ?? 0],
        ['key' => 'manuscript', 'label' => 'Manuscripts', 'icon' => 'ph-file-search', 'count' => $stats['pending_documents'] ?? 0],
        ['key' => 'schedule', 'label' => 'Defense Schedules', 'icon' => 'ph-calendar-check', 'count' => $stats['upcoming_defenses'] ?? 0],
        ['key' => 'repository', 'label' => 'Research Repository', 'icon' => 'ph-folder-open'],
    ];
    $base = 'group flex w-full items-center justify-between rounded-xl px-3.5 py-2.5 text-left text-[13px] transition-all duration-200';
    $selected = 'translate-x-1 bg-[#eebc3f] font-bold text-[#09472d] shadow-md shadow-amber-950/20';
    $normal = 'font-semibold text-white/85 hover:translate-x-1 hover:bg-white/15 hover:text-white';
@endphp

<aside data-portal-sidebar {{ $attributes->class('fixed inset-y-0 left-0 z-30 flex w-72 flex-col overflow-y-auto border-r border-emerald-800/40 bg-gradient-to-b from-[#09472d] via-[#0e5c3a] to-[#073622] text-white shadow-2xl') }}>
    <div class="shrink-0">
        <div class="flex items-center gap-3.5 p-6 pb-4">
            <div class="rounded-2xl border border-white/20 bg-gradient-to-br from-white/15 to-white/5 p-2 shadow-lg backdrop-blur-md">
                <x-app-logo variant="sidebar" />
            </div>
            <div class="flex min-w-0 flex-col leading-none">
                <span class="font-heading text-xl font-black tracking-tight text-white">NDMU</span>
                <span class="mt-1 truncate text-[9px] font-black uppercase tracking-[0.16em] text-[#eebc3f]">Research Management</span>
            </div>
        </div>

        <div class="my-2 flex items-center justify-center gap-2 px-6">
            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-[#eebc3f]/60 to-[#eebc3f]"></div>
            <div class="h-1 w-8 rounded-full bg-gradient-to-r from-[#eebc3f] to-[#ffd76f] shadow-[0_0_8px_rgba(238,188,63,0.7)]"></div>
            <div class="h-px flex-1 bg-gradient-to-l from-transparent via-[#eebc3f]/60 to-[#eebc3f]"></div>
        </div>

        <div class="px-5 py-3">
            <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.06] p-3 shadow-inner backdrop-blur-xs transition hover:bg-white/[0.09]">
                <div class="relative h-10 w-10 shrink-0 rounded-xl bg-gradient-to-tr from-[#eebc3f] to-[#ffd76f] text-[#09472d] shadow-md"><x-current-user-avatar :user="$dean" rounded="rounded-xl" /><span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-[#09472d] bg-emerald-400"></span></div>
                <div class="flex min-w-0 flex-col overflow-hidden leading-tight">
                    <span class="truncate text-xs font-bold text-white">{{ $dean->name }}</span>
                    <span class="mt-0.5 truncate text-[10px] font-medium text-white/70">College Dean</span>
                </div>
            </div>
        </div>
    </div>

    <nav class="flex-grow space-y-6 px-5 py-3" aria-label="College dean navigation">
        <div class="space-y-1">
            <div class="mb-2.5 flex items-center gap-2 px-3"><span class="h-3 w-1 rounded-full bg-[#eebc3f]"></span><span class="text-[10px] font-black uppercase tracking-[0.18em] text-emerald-300/80">Navigation</span></div>
            @foreach ($items as $item)
                <a href="{{ route('dean.dashboard', ['tab' => $item['key']]) }}" wire:navigate @class([$base, $active === $item['key'] ? $selected : $normal]) @if ($active === $item['key']) aria-current="page" @endif>
                    <span class="flex min-w-0 items-center gap-3"><i class="ph {{ $item['icon'] }} shrink-0 text-lg transition-transform group-hover:scale-110"></i><span>{{ $item['label'] }}</span></span>
                    <span class="flex items-center gap-2">@if (($item['count'] ?? 0) > 0)<x-sidebar-count-badge :count="$item['count']" :label="strtolower($item['label'])" />@endif @if ($active === $item['key'])<span class="h-1.5 w-1.5 rounded-full bg-[#09472d]"></span>@endif</span>
                </a>
            @endforeach
            @can('reports.view')
                <a href="{{ route('dean.reports.index') }}" @class([$base, $active === 'reports' ? $selected : $normal]) @if ($active === 'reports') aria-current="page" @endif>
                    <span class="flex items-center gap-3"><i class="ph ph-chart-line-up text-lg transition-transform group-hover:scale-110"></i>Research Reports</span>
                    @if ($active === 'reports')<span class="h-1.5 w-1.5 rounded-full bg-[#09472d]"></span>@endif
                </a>
            @endcan
        </div>

        <div class="space-y-1.5 pt-2">
            <div class="mb-2.5 flex items-center gap-2 px-3"><span class="h-3 w-1 rounded-full bg-[#eebc3f]"></span><span class="text-[10px] font-black uppercase tracking-[0.18em] text-emerald-300/80">Research Forms</span></div>
            <a href="{{ route('official-forms.workspace.index') }}" class="{{ $base }} {{ $normal }}">
                <span class="flex items-center gap-3"><i class="ph ph-file-pdf text-lg transition-transform group-hover:scale-110"></i>Official Forms</span>
                <span class="flex items-center gap-2"><x-sidebar-count-badge :count="$badges['forms'] ?? 0" label="forms awaiting action" /><i class="ph ph-caret-right text-xs"></i></span>
            </a>
        </div>
    </nav>

    <div class="mt-auto shrink-0 px-5 pb-5">
        <div class="my-3 h-px w-full bg-gradient-to-r from-transparent via-white/15 to-transparent"></div>
        <div class="space-y-1">
            @foreach ([['notifications', 'Notifications', 'ph-bell'], ['settings', 'Settings', 'ph-gear']] as [$key, $label, $icon])
                <a href="{{ route('dean.dashboard', ['tab' => $key]) }}" wire:navigate @class([$base, $active === $key ? $selected : $normal]) @if ($active === $key) aria-current="page" @endif>
                    <span class="flex items-center gap-3"><i class="ph {{ $icon }} text-lg transition-transform group-hover:scale-110"></i>{{ $label }}</span>
                    <span class="flex items-center gap-2">@if ($key === 'notifications' && ($badges['notifications'] ?? 0) > 0)<x-sidebar-count-badge :count="$badges['notifications']" label="unread notifications" />@endif @if ($active === $key)<span class="h-1.5 w-1.5 rounded-full bg-[#09472d]"></span>@endif</span>
                </a>
            @endforeach
            <form method="POST" action="{{ route('logout') }}" data-confirm-logout>
                @csrf
                <button type="submit" class="{{ $base }} font-semibold text-white/70 hover:bg-rose-500/20 hover:text-rose-200"><span class="flex items-center gap-3"><i class="ph ph-sign-out text-lg"></i>Logout</span></button>
            </form>
        </div>
        <div class="mt-4 flex items-center justify-center gap-2 text-center text-[9px] font-medium text-white/40"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400/60"></span><span>NDMU-RMAS &copy; {{ now()->year }} &middot; v1.0</span></div>
    </div>
</aside>
