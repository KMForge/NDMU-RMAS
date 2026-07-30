<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Document;
use App\Models\DocumentReview;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class DocumentReviewStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Document $document,
        private readonly DocumentReview $review,
        private readonly User $reviewer,
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
        $decision = Str::headline($this->review->decision);

        return [
            'type' => NotificationType::Document->value,
            'title' => "Document {$decision}",
            'message' => "{$this->document->original_filename} was reviewed by {$this->reviewer->name}.",
            'document_id' => $this->document->getKey(),
            'review_id' => $this->review->getKey(),
            'status' => $this->review->decision,
            'reviewer_name' => $this->reviewer->name,
        ];
    }
}
