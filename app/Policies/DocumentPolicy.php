<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Support\DocumentReviewerAccess;

class DocumentPolicy
{
    public function __construct(
        private readonly DocumentReviewerAccess $reviewerAccess,
    ) {}

    public function view(User $user, Document $document): bool
    {
        return $this->mayAccess($user, $document);
    }

    public function download(User $user, Document $document): bool
    {
        return $this->mayAccess($user, $document);
    }

    public function review(User $user, Document $document): bool
    {
        return $this->reviewerAccess->canReview($user, $document);
    }

    private function mayAccess(User $user, Document $document): bool
    {
        return $user->can('documents.download')
            && (
                $user->getKey() === $document->user_id
                || $user->can('documents.download-any')
                || $this->reviewerAccess->canReview($user, $document)
            );
    }
}
