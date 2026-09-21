<?php

namespace App\Modules\SystemSettings\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class TurnstileSettings
{
    public const CACHE_KEY = 'system-settings.turnstile-enabled';

    public function enabled(): bool
    {
        return (bool) Cache::rememberForever(self::CACHE_KEY, function (): bool {
            try {
                $enabled = SystemSetting::query()->value('turnstile_enabled');

                return $enabled === null
                    ? (bool) config('services.turnstile.enabled', true)
                    : (bool) $enabled;
            } catch (\Throwable) {
                // Keep protection enabled by default while installing or if settings cannot be read.
                return (bool) config('services.turnstile.enabled', true);
            }
        });
    }

    public function shouldRender(): bool
    {
        return $this->enabled() && filled(config('services.turnstile.site_key'));
    }

    public function shouldValidate(): bool
    {
        return $this->shouldRender() && ! app()->environment('testing');
    }
}
