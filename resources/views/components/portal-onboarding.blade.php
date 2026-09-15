@if ($visible)
    <div
        x-data="{
            open: true,
            index: 0,
            steps: @js($steps),
            saving: false,
            error: '',
            async dismiss(status) {
                if (this.saving) return;
                this.saving = true;
                this.error = '';

                try {
                    const response = await fetch(@js(route('onboarding.complete', $workspace)), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                        },
                        body: JSON.stringify({ status }),
                    });

                    if (!response.ok) throw new Error('Unable to save the introduction. Please try again.');
                    this.open = false;
                } catch (error) {
                    this.error = error.message;
                } finally {
                    this.saving = false;
                }
            },
        }"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-label="Workspace introduction"
    >
        <section class="w-full max-w-lg overflow-hidden rounded-3xl border border-white/20 bg-white shadow-2xl" @click.stop>
            <div class="relative overflow-hidden bg-gradient-to-br from-[#073823] via-[#0e5c3a] to-[#16734c] px-6 py-8 text-center text-white sm:px-10">
                <div class="absolute -right-10 -top-14 h-40 w-40 rounded-full bg-white/5"></div>
                <div class="relative mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-3xl shadow-lg">
                    <i :class="['ph', steps[index].icon]"></i>
                </div>
                <p class="relative mt-5 text-[10px] font-black uppercase tracking-[0.22em] text-amber-300" x-text="index === 0 ? 'Getting started' : `Guide ${index} of ${steps.length - 1}`"></p>
                <h2 class="relative mt-2 text-2xl font-black font-heading" x-text="steps[index].title"></h2>
                <p class="relative mx-auto mt-3 max-w-md text-sm leading-6 text-emerald-50/90" x-text="steps[index].description"></p>
            </div>

            <div class="space-y-5 px-6 py-5 sm:px-8">
                <div class="flex justify-center gap-2" aria-label="Introduction progress">
                    <template x-for="(_, stepIndex) in steps" :key="stepIndex">
                        <span :class="stepIndex === index ? 'w-7 bg-[#0e5c3a]' : stepIndex < index ? 'w-2 bg-emerald-300' : 'w-2 bg-slate-200'" class="h-2 rounded-full transition-all"></span>
                    </template>
                </div>

                <p x-show="error" x-text="error" class="rounded-xl bg-rose-50 px-3 py-2 text-center text-xs font-semibold text-rose-700"></p>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" @click="dismiss('skipped')" :disabled="saving" class="rounded-xl px-4 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-100 disabled:opacity-50">
                        Skip guide
                    </button>
                    <div class="flex gap-2">
                        <button x-show="index > 0" type="button" @click="index--" :disabled="saving" class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 sm:flex-none">
                            Back
                        </button>
                        <button
                            type="button"
                            @click="index === steps.length - 1 ? dismiss('completed') : index++"
                            :disabled="saving"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-xs font-black text-white shadow-md hover:bg-[#073823] disabled:opacity-50 sm:flex-none"
                        >
                            <span x-text="saving ? 'Saving...' : (index === 0 ? 'Start guide' : (index === steps.length - 1 ? 'Finish' : 'Next'))"></span>
                            <i x-show="!saving" class="ph ph-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endif
