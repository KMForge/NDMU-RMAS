<?php

namespace App\Integrations\Supabase;

use App\APIs\Contracts\RealtimeProvider;
use App\APIs\Contracts\StorageProvider;

class SupabaseManager
{
    public function __construct(
        private readonly StorageProvider $storage,
        private readonly RealtimeProvider $realtime,
    ) {}

    public function storage(): StorageProvider
    {
        return $this->storage;
    }

    public function realtime(): RealtimeProvider
    {
        return $this->realtime;
    }
}
