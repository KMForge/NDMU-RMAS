<?php

namespace App\APIs\Contracts;

interface NotificationProvider
{
    /** @param array<string, mixed> $payload */
    public function send(string $recipient, array $payload): void;
}
