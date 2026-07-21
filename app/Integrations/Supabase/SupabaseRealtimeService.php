<?php

namespace App\Integrations\Supabase;

use App\APIs\Contracts\RealtimeProvider;

class SupabaseRealtimeService implements RealtimeProvider
{
    public function isConfigured(): bool
    {
        return (bool) config('supabase.realtime.enabled') && $this->endpoint() !== null;
    }

    public function endpoint(): ?string
    {
        $endpoint = config('supabase.realtime.endpoint');

        return is_string($endpoint) && $endpoint !== '' ? $endpoint : null;
    }
}
