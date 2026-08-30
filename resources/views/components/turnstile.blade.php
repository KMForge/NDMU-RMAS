@props([
    'theme' => 'light',
    'size' => 'flexible',
])

@if (config('services.turnstile.site_key'))
    <div class="my-3 flex flex-col items-center sm:items-start space-y-1">
        <div 
            class="cf-turnstile" 
            data-sitekey="{{ config('services.turnstile.site_key') }}" 
            data-theme="{{ $theme }}"
            data-size="{{ $size }}"
        ></div>
        @error('cf-turnstile-response')
            <p class="text-xs font-bold text-rose-600 flex items-center gap-1.5 mt-1">
                <i class="ph ph-warning-circle text-sm"></i>
                <span>{{ $message }}</span>
            </p>
        @enderror
    </div>

    @pushOnce('scripts')
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endPushOnce
@endif
