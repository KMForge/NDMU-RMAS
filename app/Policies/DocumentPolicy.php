<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Support\DocumentRepositoryAccess;
use App\Modules\Documents\Support\DocumentReviewerAccess;

class DocumentPolicy
{
    public function __construct(
        private readonly DocumentRepositoryAccess $repositoryAccess,
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
        return $this->repositoryAccess->canAccess($user, $document);
    }
}
