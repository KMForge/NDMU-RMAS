@extends('layouts.blank')

@section('content')
<div class="min-h-screen bg-[#f4f8f6] px-4 py-8 sm:px-8">
    <main class="mx-auto max-w-6xl space-y-6">
        <section class="overflow-hidden rounded-3xl bg-[#0e5c3a] text-white shadow-sm">
            <div class="flex flex-col gap-4 px-6 py-8 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[.22em] text-amber-300">NDMU-RMAS</p>
                    <h1 class="mt-2 text-3xl font-black">Notifications</h1>
                    <p class="mt-1 text-sm text-emerald-100">Workflow updates and actions addressed specifically to you.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="rounded-full bg-white/10 px-4 py-2 text-sm font-bold">{{ $unreadCount }} unread</span>
                    <a href="{{ route('dashboard') }}" class="rounded-xl border border-white/25 px-4 py-2 text-sm font-bold hover:bg-white/10">Back to workspace</a>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex gap-2">
                    @foreach (['all' => 'All', 'unread' => 'Unread', 'read' => 'Read'] as $value => $label)
                        <a href="{{ route('notifications.index', ['filter' => $value]) }}"
                           class="rounded-xl px-4 py-2 text-xs font-bold {{ $filter === $value ? 'bg-[#0e5c3a] text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        @method('PATCH')
                        <button class="rounded-xl border border-emerald-200 px-4 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-50">Mark all as read</button>
                    </form>
                @endif
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($notifications as $notification)
                    @php($data = $notification->data)
                    <article class="flex gap-4 p-5 {{ $notification->read_at === null ? 'bg-emerald-50/40' : 'bg-white' }}">
                        <div class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $notification->read_at === null ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                            <i class="ph ph-bell-ringing text-lg"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <h2 class="font-black text-slate-900">{{ $data['title'] ?? 'Notification' }}</h2>
                                    @if (! empty($data['acting_as']) || ! empty($data['context_label']))
                                        <p class="mt-1 text-[11px] font-bold uppercase tracking-wide text-emerald-700">
                                            {{ $data['acting_as'] ?? '' }}{{ ! empty($data['acting_as']) && ! empty($data['context_label']) ? ' · ' : '' }}{{ $data['context_label'] ?? '' }}
                                        </p>
                                    @endif
                                </div>
                                <span class="text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $data['message'] ?? '' }}</p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <a href="{{ route('notifications.open', $notification->id) }}" class="rounded-lg bg-[#0e5c3a] px-3 py-2 text-xs font-bold text-white hover:bg-[#0a4a2e]">Open</a>
                                @if ($notification->read_at === null)
                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">Mark read</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-20 text-center text-slate-500">
                        <i class="ph ph-bell-slash text-4xl text-slate-300"></i>
                        <p class="mt-3 font-bold text-slate-700">No notifications found</p>
                        <p class="mt-1 text-sm">Workflow updates addressed to you will appear here.</p>
                    </div>
                @endforelse
            </div>

            @if ($notifications->hasPages())
                <div class="border-t border-slate-100 p-5">{{ $notifications->links() }}</div>
            @endif
        </section>
    </main>
</div>
@endsection
