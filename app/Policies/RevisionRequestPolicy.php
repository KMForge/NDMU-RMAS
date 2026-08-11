<?php

namespace App\Policies;

use App\Enums\RevisionStatus;
use App\Models\ResearchClassGroupMember;
use App\Models\ResearchClassGroupMemberHistory;
use App\Models\RevisionRequest;
use App\Models\User;

class RevisionRequestPolicy
{
    public function view(User $user, RevisionRequest $revisionRequest): bool
    {
        $group = $revisionRequest->researchClassGroup;

        if ($group === null) {
            return $user->getKey() === $revisionRequest->requested_by
                || $user->getKey() === $revisionRequest->assigned_to;
        }

        // Active Group Member
        if (ResearchClassGroupMember::query()
            ->where('research_class_group_id', $group->getKey())
            ->where('student_id', $user->getKey())
            ->whereHas('researchClassEnrollment', fn ($e) => $e->where('status', 'active'))
            ->exists()) {
            return true;
        }

        // Historical Group Member
        if (ResearchClassGroupMemberHistory::query()
            ->where('research_class_group_id', $group->getKey())
            ->where('student_id', $user->getKey())
            ->exists()) {
            return true;
        }

        // Current Assigned Adviser or Original Requesting Adviser
        if ($group->adviser_id === $user->getKey() || $revisionRequest->requested_by === $user->getKey()) {
            return true;
        }

        // Owning Facilitator
        return $group->researchClass !== null && $group->researchClass->facilitator_id === $user->getKey();
    }

    public function start(User $user, RevisionRequest $revisionRequest): bool
    {
        $group = $revisionRequest->researchClassGroup;

        if ($group === null || ! $group->isActive() || ! $group->isLeader($user)) {
            return false;
        }

        return $revisionRequest->status === RevisionStatus::Open;
    }

    public function submit(User $user, RevisionRequest $revisionRequest): bool
    {
        $group = $revisionRequest->researchClassGroup;

        if ($group === null || ! $group->isActive() || ! $group->isLeader($user)) {
            return false;
        }

        return $revisionRequest->status === RevisionStatus::InProgress
            && $revisionRequest->submitted_document_id === null;
    }

    public function resolve(User $user, RevisionRequest $revisionRequest): bool
    {
        if (! $user->can('revisions.resolve')) {
            return false;
        }

        $group = $revisionRequest->researchClassGroup;

        if ($group === null || ! $group->isActive() || $group->adviser_id !== $user->getKey()) {
            return false;
        }

        return $revisionRequest->status === RevisionStatus::Submitted;
    }

    public function updateDueDate(User $user, RevisionRequest $revisionRequest): bool
    {
        if (! $user->can('revisions.resolve')) {
            return false;
        }

        $group = $revisionRequest->researchClassGroup;

        if ($group === null || ! $group->isActive() || $group->adviser_id !== $user->getKey()) {
            return false;
        }

        return true;
    }

    public function reopen(User $user, RevisionRequest $revisionRequest): bool
    {
        if (! $user->can('revisions.resolve')) {
            return false;
        }

        $group = $revisionRequest->researchClassGroup;

        if ($group === null || ! $group->isActive() || $group->adviser_id !== $user->getKey()) {
            return false;
        }

        return $revisionRequest->status === RevisionStatus::Resolved;
    }

    public function facilitatorView(User $user, RevisionRequest $revisionRequest): bool
    {
        $group = $revisionRequest->researchClassGroup;

        return $group !== null
            && $group->researchClass !== null
            && $group->researchClass->facilitator_id === $user->getKey();
    }
}
