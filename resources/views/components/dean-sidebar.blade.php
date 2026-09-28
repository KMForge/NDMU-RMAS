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
    $base = 'flex w-full items-center justify-between rounded-xl px-3.5 py-2.5 text-left text-[13px] transition-all duration-200';
    $selected = 'bg-[#eebc3f] font-bold text-[#09472d] shadow-md shadow-black/10';
    $normal = 'font-semibold text-white/85 hover:translate-x-0.5 hover:bg-white/15 hover:text-white';
@endphp

<aside data-portal-sidebar {{ $attributes->class('fixed inset-y-0 left-0 z-30 flex w-72 flex-col overflow-y-auto border-r border-emerald-900/40 bg-gradient-to-b from-[#09472d] via-[#0e5c3a] to-[#073622] text-white shadow-2xl') }}>
    <div class="p-5">
        <div class="flex items-center gap-3 border-b border-white/10 pb-5">
            <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="NDMU" class="h-12 w-12 rounded-xl object-contain">
            <div class="min-w-0">
                <p class="text-xl font-black">NDMU</p>
                <p class="truncate text-[9px] font-black uppercase tracking-[.16em] text-[#eebc3f]">Research Management</p>
            </div>
        </div>
        <div class="mt-5 flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[.07] p-3">
            <div class="h-10 w-10 shrink-0 rounded-xl bg-[#eebc3f] text-[#09472d]"><x-current-user-avatar :user="$dean" rounded="rounded-xl" /></div>
            <div class="min-w-0">
                <p class="truncate text-xs font-bold">{{ $dean->name }}</p>
                <p class="mt-1 text-[10px] text-white/65">College Dean</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 space-y-6 px-5 pb-5" aria-label="College dean navigation">
        <div class="space-y-1">
            <p class="mb-2 px-3 text-[10px] font-black uppercase tracking-[.18em] text-emerald-300/80">Navigation</p>
            @foreach ($items as $item)
                <a href="{{ route('dean.dashboard', ['tab' => $item['key']]) }}" wire:navigate @class([$base, $active === $item['key'] ? $selected : $normal]) @if ($active === $item['key']) aria-current="page" @endif>
                    <span class="flex min-w-0 items-center gap-3"><i class="ph {{ $item['icon'] }} shrink-0 text-lg"></i><span>{{ $item['label'] }}</span></span>
                    @if (($item['count'] ?? 0) > 0)<x-sidebar-count-badge :count="$item['count']" :label="strtolower($item['label'])" />@endif
                </a>
            @endforeach
            @can('reports.view')
                <a href="{{ route('dean.reports.index') }}" @class([$base, $active === 'reports' ? $selected : $normal]) @if ($active === 'reports') aria-current="page" @endif>
                    <span class="flex items-center gap-3"><i class="ph ph-chart-line-up text-lg"></i>Research Reports</span>
                </a>
            @endcan
        </div>

        <div class="space-y-1">
            <p class="mb-2 px-3 text-[10px] font-black uppercase tracking-[.18em] text-emerald-300/80">Official Forms</p>
            <a href="{{ route('official-forms.workspace.index') }}" class="{{ $base }} {{ $normal }}">
                <span class="flex items-center gap-3"><i class="ph ph-file-pdf text-lg"></i>Forms Workspace</span>
                <x-sidebar-count-badge :count="$badges['forms'] ?? 0" label="forms awaiting action" />
            </a>
        </div>
    </nav>

    <div class="space-y-1 border-t border-white/10 p-5">
        @foreach ([['notifications', 'Notifications', 'ph-bell'], ['settings', 'Settings', 'ph-gear']] as [$key, $label, $icon])
            <a href="{{ route('dean.dashboard', ['tab' => $key]) }}" wire:navigate @class([$base, $active === $key ? $selected : $normal])>
                <span class="flex items-center gap-3"><i class="ph {{ $icon }} text-lg"></i>{{ $label }}</span>
                @if ($key === 'notifications' && ($badges['notifications'] ?? 0) > 0)<x-sidebar-count-badge :count="$badges['notifications']" label="unread notifications" />@endif
            </a>
        @endforeach
        <form method="POST" action="{{ route('logout') }}" data-confirm-logout>
            @csrf
            <button type="submit" class="{{ $base }} font-semibold text-white/70 hover:bg-rose-500/20 hover:text-rose-200"><span class="flex items-center gap-3"><i class="ph ph-sign-out text-lg"></i>Logout</span></button>
        </form>
    </div>
</aside>
