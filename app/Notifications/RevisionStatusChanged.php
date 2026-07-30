<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\RevisionRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class RevisionStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        private readonly RevisionRequest $revisionRequest,
        private readonly User $actor,
        private readonly string $action,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $status = $this->revisionRequest->status->value;

        return [
            'type' => NotificationType::Document->value,
            'title' => 'Revision '.Str::headline($status),
            'message' => "{$this->revisionRequest->title} was updated by {$this->actor->name}.",
            'revision_request_id' => $this->revisionRequest->getKey(),
            'status' => $status,
            'action' => $this->action,
            'actor_name' => $this->actor->name,
        ];
    }
}
