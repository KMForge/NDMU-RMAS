@props([
    'theme' => 'light',
    'size' => 'flexible',
])

@if (app(\App\Modules\SystemSettings\Services\TurnstileSettings::class)->shouldRender())
    @php($widgetId = 'turnstile-'.str()->uuid())

    <div class="my-3 flex w-full flex-col items-center space-y-1 sm:items-start" data-ndmu-turnstile-wrapper>
        <div
            id="{{ $widgetId }}"
            class="min-h-[65px] w-full"
            data-ndmu-turnstile
            data-sitekey="{{ config('services.turnstile.site_key') }}"
            data-theme="{{ $theme }}"
            data-size="{{ $size }}"
        ></div>

        <div class="hidden items-start gap-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900" data-turnstile-notice role="status">
            <i class="ph ph-warning-circle mt-0.5 shrink-0 text-sm" aria-hidden="true"></i>
            <span data-turnstile-message>Security verification is loading.</span>
        </div>

        <button type="button" class="hidden text-xs font-bold text-[#0e5c3a] underline underline-offset-2" data-turnstile-retry>
            Retry security verification
        </button>

        @error('cf-turnstile-response')
            <p class="text-xs font-bold text-rose-600 flex items-center gap-1.5 mt-1">
                <i class="ph ph-warning-circle text-sm"></i>
                <span>{{ $message }}</span>
            </p>
        @enderror
    </div>

    @pushOnce('scripts')
        <script>
            (() => {
                const wrappers = () => document.querySelectorAll('[data-ndmu-turnstile-wrapper]');

                const setNotice = (wrapper, message, canRetry = false) => {
                    const notice = wrapper.querySelector('[data-turnstile-notice]');
                    const messageElement = wrapper.querySelector('[data-turnstile-message]');
                    const retryButton = wrapper.querySelector('[data-turnstile-retry]');

                    if (messageElement) messageElement.textContent = message;
                    notice?.classList.remove('hidden');
                    notice?.classList.add('flex');
                    retryButton?.classList.toggle('hidden', !canRetry);
                };

                const clearNotice = (wrapper) => {
                    const notice = wrapper.querySelector('[data-turnstile-notice]');
                    notice?.classList.add('hidden');
                    notice?.classList.remove('flex');
                    wrapper.querySelector('[data-turnstile-retry]')?.classList.add('hidden');
                };

                const resetWidget = (wrapper, message = null) => {
                    const widgetId = wrapper.dataset.turnstileWidgetId;

                    if (widgetId !== undefined && window.turnstile) {
                        window.turnstile.reset(widgetId);
                    }

                    if (message) setNotice(wrapper, message, true);
                };

                const unlockForm = (wrapper) => {
                    const form = wrapper.closest('form');
                    if (! form) return;

                    delete form.dataset.turnstileSubmitting;
                    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
                        button.disabled = false;
                    });
                };

                window.ndmuTurnstileOnload = () => {
                    wrappers().forEach((wrapper) => {
                        const container = wrapper.querySelector('[data-ndmu-turnstile]');
                        if (! container || container.dataset.turnstileRendered === 'true') return;

                        let renderedWidgetId;
                        renderedWidgetId = window.turnstile.render(container, {
                            sitekey: container.dataset.sitekey,
                            theme: container.dataset.theme || 'light',
                            size: container.dataset.size || 'flexible',
                            retry: 'auto',
                            'retry-interval': 3000,
                            'refresh-expired': 'auto',
                            'refresh-timeout': 'auto',
                            callback: () => {
                                wrapper.dataset.turnstileState = 'ready';
                                clearNotice(wrapper);
                                unlockForm(wrapper);
                            },
                            'error-callback': (errorCode) => {
                                wrapper.dataset.turnstileState = 'error';
                                unlockForm(wrapper);
                                setNotice(wrapper, `Security verification could not finish (code ${errorCode}). It will retry automatically.`, true);
                            },
                            'expired-callback': () => {
                                wrapper.dataset.turnstileState = 'expired';
                                unlockForm(wrapper);
                                resetWidget(wrapper, 'The security check expired and has been refreshed. Please wait for it to finish.');
                            },
                            'timeout-callback': () => {
                                wrapper.dataset.turnstileState = 'timeout';
                                unlockForm(wrapper);
                                resetWidget(wrapper, 'The security check timed out and has been refreshed. Please try again.');
                            },
                            'unsupported-callback': () => {
                                wrapper.dataset.turnstileState = 'unsupported';
                                unlockForm(wrapper);
                                setNotice(wrapper, 'This browser cannot run the security check. Update the browser or try another one.', false);
                            },
                        });

                        wrapper.dataset.turnstileWidgetId = String(renderedWidgetId);
                        container.dataset.turnstileRendered = 'true';
                    });
                };

                window.ndmuTurnstileScriptFailed = () => {
                    wrappers().forEach((wrapper) => {
                        unlockForm(wrapper);
                        setNotice(wrapper, 'The security service could not load. Check the connection or content blocker, then retry.', true);
                    });
                };

                document.addEventListener('click', (event) => {
                    const retryButton = event.target.closest('[data-turnstile-retry]');
                    if (! retryButton) return;

                    const wrapper = retryButton.closest('[data-ndmu-turnstile-wrapper]');
                    if (! wrapper) return;

                    if (! window.turnstile) {
                        window.location.reload();

                        return;
                    }

                    clearNotice(wrapper);
                    resetWidget(wrapper);
                });

                document.addEventListener('submit', (event) => {
                    const form = event.target;
                    if (! (form instanceof HTMLFormElement)) return;

                    const wrapper = form.querySelector('[data-ndmu-turnstile-wrapper]');
                    if (! wrapper) return;

                    const response = form.querySelector('input[name="cf-turnstile-response"]');
                    if (! response?.value) {
                        event.preventDefault();
                        setNotice(wrapper, 'Please wait for the security verification to complete before continuing.', true);
                        resetWidget(wrapper);

                        return;
                    }

                    if (form.dataset.turnstileSubmitting === 'true') {
                        event.preventDefault();

                        return;
                    }

                    form.dataset.turnstileSubmitting = 'true';
                    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
                        button.disabled = true;
                    });
                });

                window.addEventListener('pageshow', () => {
                    wrappers().forEach(unlockForm);
                });
            })();
        </script>
        <script
            src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=ndmuTurnstileOnload&render=explicit"
            async
            defer
            onerror="window.ndmuTurnstileScriptFailed?.()"
        ></script>
    @endPushOnce
@endif
