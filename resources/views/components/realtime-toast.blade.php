<div
    x-data
    class="fixed top-5 right-5 z-[9999] flex max-w-sm w-full flex-col gap-2.5 pointer-events-none px-4 sm:px-0"
    role="status"
    aria-live="polite"
>
    <template x-for="toast in $store.liveState.toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="pointer-events-auto flex items-start gap-3 rounded-2xl border border-emerald-900/10 bg-white/95 p-4 shadow-xl backdrop-blur-md ring-1 ring-black/5"
        >
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-[#09472d] to-[#0e5c3a] text-white shadow-sm">
                <i class="ph ph-bell-ringing text-lg"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                    <p class="truncate text-xs font-black text-slate-900" x-text="toast.title"></p>
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider" x-text="toast.context || 'Real-time'"></span>
                </div>
                <p class="mt-1 line-clamp-2 text-[11px] leading-relaxed text-slate-600" x-text="toast.message"></p>
            </div>
            <button
                type="button"
                @click="$store.liveState.dismissToast(toast.id)"
                class="shrink-0 rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition cursor-pointer"
                aria-label="Dismiss notification"
            >
                <i class="ph ph-x text-xs"></i>
            </button>
        </div>
    </template>
</div>
