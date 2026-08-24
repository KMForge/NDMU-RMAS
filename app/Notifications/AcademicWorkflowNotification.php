<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AcademicWorkflowNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, scalar|null>  $routeParameters
     */
    public function __construct(
        public readonly string $eventKey,
        public readonly string $title,
        public readonly string $message,
        public readonly string $category,
        public readonly string $logicalKey,
        public readonly string $routeName,
        public readonly array $routeParameters = [],
        public readonly ?string $contextLabel = null,
        public readonly ?string $actingAs = null,
        public readonly ?string $actorName = null,
        public readonly ?string $sourceType = null,
        public readonly int|string|null $sourceId = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return array_filter([
            'event_key' => $this->eventKey,
            'title' => $this->title,
            'message' => $this->message,
            'category' => $this->category,
            'logical_key' => $this->logicalKey,
            'route_name' => $this->routeName,
            'route_parameters' => $this->routeParameters,
            'context_label' => $this->contextLabel,
            'acting_as' => $this->actingAs,
            'actor_name' => $this->actorName,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'occurred_at' => now()->toIso8601String(),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
