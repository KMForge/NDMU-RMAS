<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Support\DocumentGroupAccess;
use App\Modules\Documents\Support\DocumentReviewerAccess;

class DocumentPolicy
{
    public function __construct(
        private readonly DocumentGroupAccess $groupAccess,
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
        if (! $user->can('documents.download')) {
            return false;
        }

        if ($user->can('documents.download-any')) {
            return true;
        }

        if ($document->research_class_group_id !== null) {
            return $this->groupAccess->isActiveMember($user, $document)
                || $this->reviewerAccess->canReview($user, $document);
        }

        return $user->getKey() === $document->user_id
            || $this->reviewerAccess->canReview($user, $document);
    }
}
