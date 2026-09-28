<?php

namespace App\Modules\SystemSettings\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class RateLimitSettings
{
    public const CACHE_KEY = 'system-settings.defense-high-traffic-mode';

    public function highTrafficModeEnabled(): bool
    {
        return (bool) Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(5),
            function (): bool {
                if (! Schema::hasTable('system_settings')
                    || ! Schema::hasColumn('system_settings', 'defense_high_traffic_mode_enabled')) {
                    return false;
                }

                return (bool) SystemSetting::query()->value('defense_high_traffic_mode_enabled');
            },
        );
    }

    public function defenseHighTrafficModeEnabled(): bool
    {
        return $this->highTrafficModeEnabled();
    }
}
