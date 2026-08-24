<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Document;
use App\Models\DocumentReviewComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DocumentReviewCommentPosted extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Document $document,
        private readonly DocumentReviewComment $comment,
        private readonly User $reviewer,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => NotificationType::Document->value,
            'title' => 'New Research Paper Feedback',
            'message' => "{$this->reviewer->name} posted feedback on {$this->document->original_filename}.",
            'document_id' => $this->document->getKey(),
            'comment_id' => $this->comment->getKey(),
            'reviewer_name' => $this->reviewer->name,
            'url' => route('student.dashboard', ['tab' => 'revisions']),
        ];
    }
}
