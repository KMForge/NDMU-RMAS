<div
    x-data="{
        open: false,
        submitting: false,
        logoutForm: null,
        requestLogout(form) {
            this.logoutForm = form;
            this.submitting = false;
            this.open = true;
            this.$nextTick(() => this.$refs.cancelButton.focus());
        },
        cancelLogout() {
            if (this.submitting) return;
            this.open = false;
            this.logoutForm = null;
        },
        confirmLogout() {
            if (! this.logoutForm || this.submitting) return;
            this.submitting = true;
            this.logoutForm.submit();
        },
    }"
    x-init="document.addEventListener('submit', event => {
        const form = event.target.closest('form[data-confirm-logout]');
        if (! form) return;
        event.preventDefault();
        requestLogout(form);
    })"
    @keydown.escape.window="cancelLogout()"
>
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="logout-confirmation-title"
    >
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" @click="cancelLogout()"></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-md overflow-hidden rounded-[2rem] border border-white/60 bg-white shadow-2xl"
        >
            <div class="bg-[#0e5c3a] px-7 py-6 text-white">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-2xl text-[#f4c542]">
                        <i class="ph ph-sign-out"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#f4c542]">Confirm action</p>
                        <h2 id="logout-confirmation-title" class="mt-1 text-xl font-black">Log out of NDMU-RMAS?</h2>
                    </div>
                </div>
            </div>

            <div class="px-7 py-6">
                <p class="text-sm leading-6 text-gray-600">You will need to sign in again to access your research portal.</p>

                <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        x-ref="cancelButton"
                        @click="cancelLogout()"
                        :disabled="submitting"
                        class="rounded-2xl border border-gray-200 px-5 py-3 text-sm font-bold text-gray-700 transition hover:bg-gray-50 disabled:opacity-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="confirmLogout()"
                        :disabled="submitting"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#0e5c3a] px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-[#0a4a2e] disabled:cursor-wait disabled:opacity-60"
                    >
                        <i class="ph" :class="submitting ? 'ph-spinner animate-spin' : 'ph-sign-out'"></i>
                        <span x-text="submitting ? 'Logging out…' : 'Yes, Log Out'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
