@props(['notifications', 'unreadCount' => 0, 'filter' => 'all', 'dashboardRoute' => null])

<div class="space-y-6 animate-fade-in font-sans">
    <!-- Header Hero Card -->
    <div class="flex flex-col gap-4 bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-7 shadow-sm md:flex-row md:items-center md:justify-between relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#eebc3f]"></div>
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#073823] to-[#0e5c3a] text-[#eebc3f] shadow-xs">
                <i class="ph ph-bell text-2xl"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-black font-heading text-slate-900">Notifications Center</h1>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Workflow updates and actions addressed specifically to you.</p>
            </div>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <span class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-2 text-xs font-black text-[#0e5c3a]">
                {{ $unreadCount }} Unread
            </span>

            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="rounded-2xl border border-emerald-200 bg-white hover:bg-emerald-50 px-4 py-2 text-xs font-black text-[#0e5c3a] transition-colors shadow-2xs cursor-pointer inline-flex items-center gap-1.5">
                        <i class="ph ph-checks text-sm"></i>
                        <span>Mark All Read</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Filter Bar & Notification List -->
    <div class="rounded-3xl border border-slate-200/80 bg-white shadow-sm overflow-hidden relative">
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5 bg-slate-50/60 mt-1">
            <div class="flex gap-2">
                @foreach (['all' => 'All Notifications', 'unread' => 'Unread', 'read' => 'Read'] as $value => $label)
                    <a
                        href="{{ $dashboardRoute ? url($dashboardRoute).'?tab=notifications&notification_filter='.$value : request()->fullUrlWithQuery(['tab' => 'notifications', 'notification_filter' => $value]) }}"
                        wire:navigate
                        class="rounded-xl px-4 py-2 text-xs font-bold transition-all {{ ($filter ?? 'all') === $value ? 'bg-gradient-to-r from-[#073823] to-[#0e5c3a] text-white shadow-xs' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-100' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($notifications as $notification)
                @php($data = $notification->data)
                <article class="flex gap-4 p-5 sm:p-6 transition-colors {{ $notification->read_at === null ? 'bg-emerald-50/40 hover:bg-emerald-50/60' : 'bg-white hover:bg-slate-50/60' }}">
                    <div class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl {{ $notification->read_at === null ? 'bg-gradient-to-br from-[#073823] to-[#0e5c3a] text-[#eebc3f] shadow-xs' : 'bg-slate-100 text-slate-500' }}">
                        <i class="ph {{ $notification->read_at === null ? 'ph-bell-ringing' : 'ph-bell' }} text-lg"></i>
                    </div>
                    <div class="min-w-0 flex-1 space-y-1.5">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <h2 class="font-black text-slate-900 text-sm sm:text-base">{{ $data['title'] ?? 'Notification' }}</h2>
                                @if (! empty($data['acting_as']) || ! empty($data['context_label']))
                                    <p class="mt-0.5 text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">
                                        {{ $data['acting_as'] ?? '' }}{{ ! empty($data['acting_as']) && ! empty($data['context_label']) ? ' · ' : '' }}{{ $data['context_label'] ?? '' }}
                                    </p>
                                @endif
                            </div>
                            <span class="text-[11px] font-medium text-slate-400">
                                {{ $notification->created_at?->diffForHumans() }}
                            </span>
                        </div>
                        <p class="text-xs leading-relaxed text-slate-600 font-medium">{{ $data['message'] ?? '' }}</p>
                        <div class="pt-2 flex flex-wrap gap-2">
                            <a
                                href="{{ route('notifications.open', $notification->id) }}"
                                class="rounded-xl bg-[#0e5c3a] hover:bg-[#073823] px-3.5 py-1.5 text-xs font-bold text-white shadow-2xs transition-colors cursor-pointer inline-flex items-center gap-1"
                            >
                                <span>Open</span>
                                <i class="ph ph-arrow-up-right text-xs"></i>
                            </a>
                            @if ($notification->read_at === null)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3.5 py-1.5 text-xs font-bold text-slate-700 transition-colors cursor-pointer">
                                        Mark as Read
                                    </button>
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
                    <p class="text-xs text-slate-500 max-w-sm mx-auto font-medium">
                        Workflow updates and action requests addressed to you will appear directly here.
                    </p>
                </div>
            @endforelse
        </div>

        @if ($notifications instanceof \Illuminate\Contracts\Pagination\Paginator && $notifications->hasPages())
            <div class="border-t border-slate-100 p-5 bg-slate-50/50">{{ $notifications->links() }}</div>
        @endif
    </div>
</div>

