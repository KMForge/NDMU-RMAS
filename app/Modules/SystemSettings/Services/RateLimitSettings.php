<?php

namespace App\Modules\SystemSettings\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class RateLimitSettings
{
    public const CACHE_KEY = 'system-settings.defense-high-traffic-mode';

    public function defenseHighTrafficModeEnabled(): bool
    {
        return (bool) Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(5),
            fn (): bool => (bool) SystemSetting::query()->value('defense_high_traffic_mode_enabled'),
        );
    }
}
