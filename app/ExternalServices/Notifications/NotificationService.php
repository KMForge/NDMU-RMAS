<?php

namespace App\ExternalServices\Notifications;

interface NotificationService
{
    /** @param array<string, mixed> $context */
    public function notify(object $recipient, string $event, array $context = []): void;
}
