<div
    class="relative z-50"
    x-data="{
        notificationMenuOpen: false,
        get currentUnreadCount() {
            if ($store?.liveState?.initialized && typeof $store.liveState.unreadCount === 'number') {
                return $store.liveState.unreadCount;
            }
            return {{ (int) $unreadCount }};
        },
        get liveList() {
            if ($store?.liveState?.initialized && Array.isArray($store.liveState.recentNotifications)) {
                return $store.liveState.recentNotifications;
            }
            return null;
        }
    }"
    @click.outside="notificationMenuOpen = false"
    @keydown.escape.window="notificationMenuOpen = false"
>
    <button
        type="button"
        @click="notificationMenuOpen = !notificationMenuOpen"
        :aria-expanded="notificationMenuOpen"
        aria-haspopup="menu"
        aria-label="Open notification menu"
        class="relative flex h-9 w-9 items-center justify-center rounded-full bg-slate-50 text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#0e5c3a]/30 cursor-pointer shadow-2xs"
    >
        <i class="ph ph-bell text-lg"></i>
        <span
            x-show="currentUnreadCount > 0"
            x-cloak
            x-text="currentUnreadCount > 99 ? '99+' : currentUnreadCount"
            class="absolute -right-1 -top-1 flex min-h-5 min-w-5 items-center justify-center rounded-full border-2 border-white bg-rose-500 px-1 text-[9px] font-black leading-none text-white animate-pulse"
            @if ($unreadCount <= 0) style="display: none;" @endif
        >
            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
        </span>
    </button>

    <section
        x-show="notificationMenuOpen"
        x-cloak
        x-transition.origin.top.right
        role="menu"
        class="absolute right-0 z-50 mt-3 w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-3xl border border-slate-200/90 bg-white text-left shadow-2xl ring-1 ring-black/5"
    >
        <div class="h-1 bg-gradient-to-r from-[#073823] to-[#eebc3f]"></div>
        <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5 bg-white">
            <div>
                <h2 class="text-sm font-black text-slate-900">Notifications</h2>
                <p class="mt-0.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider" x-text="currentUnreadCount + ' unread'">{{ $unreadCount }} unread</p>
            </div>
            <div x-show="currentUnreadCount > 0" x-cloak>
                <form method="POST" action="{{ route('notifications.read-all') }}" @submit.prevent="
                    fetch('{{ route('notifications.read-all') }}', {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']')?.content,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }).then(() => {
                        if ($store?.liveState) {
                            $store.liveState.unreadCount = 0;
                            if (Array.isArray($store.liveState.recentNotifications)) {
                                $store.liveState.recentNotifications.forEach(n => n.read_at = new Date().toISOString());
                            }
                        }
                    }).catch(() => $el.submit());
                ">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a] hover:text-[#073823] cursor-pointer">
                        Mark all read
                    </button>
                </form>
            </div>
        </header>

        @php($notificationWorkspaceResolver = app(\App\Modules\Notifications\Services\NotificationWorkspaceResolver::class))
        <div class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
            {{-- Dynamic Alpine view when store is initialized --}}
            <template x-if="liveList !== null">
                <div>
                    <template x-for="item in liveList" :key="item.id">
                        <a
                            :href="item.open_url"
                            role="menuitem"
                            :class="item.read_at ? 'bg-white' : 'bg-emerald-50/50'"
                            class="flex gap-3 px-4 py-3 transition-colors hover:bg-slate-50 border-b border-slate-100 last:border-b-0"
                        >
                            <span
                                :class="item.read_at ? 'bg-slate-100 text-slate-500' : 'bg-emerald-100 text-emerald-700'"
                                class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                            >
                                <i class="ph ph-bell-ringing text-base"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-start justify-between gap-2">
                                    <span class="truncate text-xs font-black text-slate-900" x-text="item.title"></span>
                                    <template x-if="!item.read_at">
                                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-red-500" aria-label="Unread"></span>
                                    </template>
                                </span>
                                <span class="mt-1 line-clamp-2 block text-[11px] leading-4 text-slate-600" x-text="item.message"></span>
                                <template x-if="item.workspace_label">
                                    <span class="mt-2 inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-amber-800">
                                        <i class="ph ph-identification-card"></i>
                                        For: <span x-text="item.workspace_label"></span>
                                    </span>
                                </template>
                                <span class="mt-1.5 flex items-center justify-between gap-2 text-[9px] text-slate-400">
                                    <span class="truncate font-bold text-emerald-700" x-text="item.context_label || 'NDMU-RMAS'"></span>
                                    <time class="shrink-0" x-text="item.created_at_human"></time>
                                </span>
                            </span>
                        </a>
                    </template>
                    <template x-if="liveList.length === 0">
                        <div class="px-5 py-10 text-center">
                            <i class="ph ph-bell-slash text-3xl text-slate-300"></i>
                            <p class="mt-2 text-xs font-bold text-slate-700">No notifications yet</p>
                            <p class="mt-1 text-[10px] text-slate-500">Your workflow updates will appear here.</p>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Initial server-rendered fallback before client hydration --}}
            <template x-if="liveList === null">
                <div>
                    @forelse ($recentNotifications as $notification)
                        @php($data = $notification->data)
                        @php($workspaceLabel = $notificationWorkspaceResolver->labelForData($data))
                        @php($targetWorkspace = $notificationWorkspaceResolver->fromData($data))
                        <a
                            href="{{ route('notifications.open', $notification->id) }}"
                            role="menuitem"
                            class="flex gap-3 px-4 py-3 transition-colors hover:bg-slate-50 {{ $notification->read_at === null ? 'bg-emerald-50/50' : 'bg-white' }}"
                        >
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $notification->read_at === null ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                <i class="ph ph-bell-ringing text-base"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-start justify-between gap-2">
                                    <span class="truncate text-xs font-black text-slate-900">{{ $data['title'] ?? 'Notification' }}</span>
                                    @if ($notification->read_at === null)
                                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-red-500" aria-label="Unread"></span>
                                    @endif
                                </span>
                                <span class="mt-1 line-clamp-2 block text-[11px] leading-4 text-slate-600">{{ $data['message'] ?? '' }}</span>
                                @if ($workspaceLabel)
                                    <span class="mt-2 inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-amber-800">
                                        <i class="ph ph-identification-card"></i>
                                        For: {{ $workspaceLabel }}
                                        @if ($targetWorkspace && session('active_workspace') !== $targetWorkspace)
                                            <span class="normal-case tracking-normal text-amber-600">&middot; opens workspace</span>
                                        @endif
                                    </span>
                                @endif
                                <span class="mt-1.5 flex items-center justify-between gap-2 text-[9px] text-slate-400">
                                    <span class="truncate font-bold text-emerald-700">{{ $data['context_label'] ?? 'NDMU-RMAS' }}</span>
                                    <time class="shrink-0">{{ $notification->created_at->diffForHumans() }}</time>
                                </span>
                            </span>
                        </a>
                    @empty
                        <div class="px-5 py-10 text-center">
                            <i class="ph ph-bell-slash text-3xl text-slate-300"></i>
                            <p class="mt-2 text-xs font-bold text-slate-700">No notifications yet</p>
                            <p class="mt-1 text-[10px] text-slate-500">Your workflow updates will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </template>
        </div>

        <footer class="border-t border-slate-100 bg-slate-50/70 p-2">
            <a
                href="{{ request()->routeIs('*.dashboard') ? url()->current().'?tab=notifications' : route('notifications.index') }}"
                wire:navigate
                class="flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-xs font-black text-[#0e5c3a] transition-colors hover:bg-emerald-50"
            >
                View all notifications
                <i class="ph ph-arrow-right"></i>
            </a>
        </footer>
    </section>
</div>
