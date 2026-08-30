@extends('layouts.blank')

@section('content')
<div class="min-h-screen bg-[#f4f7f6] px-4 py-8 sm:px-8 font-sans">
    <main class="mx-auto max-w-6xl space-y-6">
        <!-- Hero Banner -->
        <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#073823] via-[#0e5c3a] to-[#042416] p-7 sm:p-8 text-white shadow-xl border border-emerald-800/40">
            <div class="absolute -right-12 -top-12 h-64 w-64 rounded-full bg-[#eebc3f]/10 blur-2xl pointer-events-none"></div>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between relative z-10">
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/25 backdrop-blur-md border border-[#eebc3f]/30 text-[#eebc3f] font-black text-[10px] uppercase tracking-[0.16em]">
                            <i class="ph ph-bell text-xs"></i>
                            NDMU-RMAS Notifications
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black font-heading tracking-tight text-white">Notifications Center</h1>
                    <p class="text-xs sm:text-sm text-emerald-100/85 max-w-2xl leading-relaxed">
                        Stay informed on workflow updates, document review decisions, and defense schedules addressed to you.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <span class="rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 px-4 py-2 text-xs font-black text-[#eebc3f]">
                        {{ $unreadCount }} unread
                    </span>
                    <a
                        href="{{ route('dashboard') }}"
                        class="rounded-2xl bg-gradient-to-r from-[#eebc3f] to-[#f4c542] hover:brightness-105 px-5 py-2.5 text-xs font-black text-[#073823] shadow-md transition-all cursor-pointer inline-flex items-center gap-2"
                    >
                        <i class="ph ph-arrow-left text-sm"></i>
                        <span>Back to Workspace</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- Notifications Container -->
        <section class="rounded-3xl border border-slate-200/80 bg-white shadow-sm overflow-hidden relative">
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>

            <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between bg-slate-50/60 mt-1">
                <div class="flex gap-2">
                    @foreach (['all' => 'All Notifications', 'unread' => 'Unread', 'read' => 'Read'] as $value => $label)
                        <a href="{{ route('notifications.index', ['filter' => $value]) }}"
                           class="rounded-xl px-4 py-2 text-xs font-bold transition-all cursor-pointer {{ $filter === $value ? 'bg-gradient-to-r from-[#073823] to-[#0e5c3a] text-white shadow-xs' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-100' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        @method('PATCH')
                        <button class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-black text-[#0e5c3a] hover:bg-emerald-100 transition-colors cursor-pointer inline-flex items-center gap-1.5">
                            <i class="ph ph-checks text-sm"></i>
                            <span>Mark all as read</span>
                        </button>
                    </form>
                @endif
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($notifications as $notification)
                    @php($data = $notification->data)
                    <article class="flex gap-4 p-5 sm:p-6 transition-colors {{ $notification->read_at === null ? 'bg-emerald-50/40 hover:bg-emerald-50/60' : 'bg-white hover:bg-slate-50/60' }}">
                        <div class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl {{ $notification->read_at === null ? 'bg-gradient-to-br from-[#073823] to-[#0e5c3a] text-[#eebc3f] shadow-xs' : 'bg-slate-100 text-slate-500' }}">
                            <i class="ph {{ $notification->read_at === null ? 'ph-bell-ringing' : 'ph-bell' }} text-lg"></i>
                        </div>
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <h2 class="font-black text-slate-900 text-sm sm:text-base">{{ $data['title'] ?? 'Notification' }}</h2>
                                    @if (! empty($data['acting_as']) || ! empty($data['context_label']))
                                        <p class="mt-0.5 text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">
                                            {{ $data['acting_as'] ?? '' }}{{ ! empty($data['acting_as']) && ! empty($data['context_label']) ? ' · ' : '' }}{{ $data['context_label'] ?? '' }}
                                        </p>
                                    @endif
                                </div>
                                <span class="text-[11px] text-slate-400 font-medium">{{ $notification->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-xs leading-relaxed text-slate-600 pt-1 font-medium">{{ $data['message'] ?? '' }}</p>
                            <div class="pt-3 flex flex-wrap gap-2">
                                <a href="{{ route('notifications.open', $notification->id) }}" class="rounded-xl bg-[#0e5c3a] hover:bg-[#073823] px-3.5 py-1.5 text-xs font-bold text-white shadow-2xs transition-colors cursor-pointer inline-flex items-center gap-1">
                                    <span>Open</span>
                                    <i class="ph ph-arrow-up-right text-xs"></i>
                                </a>
                                @if ($notification->read_at === null)
                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3.5 py-1.5 text-xs font-bold text-slate-700 transition-colors cursor-pointer">Mark as read</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-20 text-center space-y-3">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center text-3xl mx-auto shadow-2xs">
                            <i class="ph ph-bell-slash"></i>
                        </div>
                        <h2 class="text-base font-black text-slate-900">No Notifications Found</h2>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto">
                            Workflow updates addressed to you will appear here automatically.
                        </p>
                    </div>
                @endforelse
            </div>

            @if ($notifications->hasPages())
                <div class="border-t border-slate-100 p-5 bg-slate-50/50">{{ $notifications->links() }}</div>
            @endif
        </section>
    </main>
</div>
@endsection

