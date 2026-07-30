<?php

namespace App\Policies;

use App\Models\RevisionRequest;
use App\Models\User;
use App\Modules\Documents\Support\DocumentReviewerAccess;

class RevisionRequestPolicy
{
    public function __construct(
        private readonly DocumentReviewerAccess $reviewerAccess,
    ) {}

    public function view(User $user, RevisionRequest $revisionRequest): bool
    {
        return $this->isAssignedStudent($user, $revisionRequest)
            || $this->mayManage($user, $revisionRequest);
    }

    public function start(User $user, RevisionRequest $revisionRequest): bool
    {
        return $user->can('revisions.resolve')
            && $this->isAssignedStudent($user, $revisionRequest);
    }

    public function submit(User $user, RevisionRequest $revisionRequest): bool
    {
        return $user->can('documents.upload')
            && $user->can('revisions.resolve')
            && $this->isAssignedStudent($user, $revisionRequest);
    }

    public function manage(User $user, RevisionRequest $revisionRequest): bool
    {
        return $user->can('revisions.resolve')
            && $this->mayManage($user, $revisionRequest);
    }

    private function isAssignedStudent(User $user, RevisionRequest $revisionRequest): bool
    {
        return $user->getKey() === $revisionRequest->assigned_to;
    }

    private function mayManage(User $user, RevisionRequest $revisionRequest): bool
    {
        if ($user->can('research.view-all')) {
            return true;
        }

        if ($user->getKey() === $revisionRequest->requested_by) {
            return true;
        }

        return $revisionRequest->document !== null
            && $this->reviewerAccess->canReview($user, $revisionRequest->document);
    }
}
