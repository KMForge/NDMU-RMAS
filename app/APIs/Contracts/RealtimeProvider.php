<?php

namespace App\APIs\Contracts;

interface RealtimeProvider
{
    public function isConfigured(): bool;

    public function endpoint(): ?string;
}
