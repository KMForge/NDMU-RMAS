<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        return $this->mayAccess($user, $document);
    }

    public function download(User $user, Document $document): bool
    {
        return $this->mayAccess($user, $document);
    }

    private function mayAccess(User $user, Document $document): bool
    {
        return $user->can('documents.download')
            && (
                $user->getKey() === $document->user_id
                || $user->can('documents.download-any')
            );
    }
}
