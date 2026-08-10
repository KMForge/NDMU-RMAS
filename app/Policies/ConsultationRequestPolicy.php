<?php

namespace App\Policies;

use App\Models\ConsultationRequest;
use App\Models\ResearchClassGroupMember;
use App\Models\ResearchClassGroupMemberHistory;
use App\Models\User;

class ConsultationRequestPolicy
{
    public function view(User $user, ConsultationRequest $request): bool
    {
        if ($request->research_class_group_id === null) {
            return $user->getKey() === $request->requested_by
                || $user->getKey() === $request->assigned_adviser_id;
        }

        $groupId = $request->research_class_group_id;

        // Group members (current or historical)
        $isCurrentMember = ResearchClassGroupMember::query()
            ->where('research_class_group_id', $groupId)
            ->where('student_id', $user->getKey())
            ->exists();

        if ($isCurrentMember) {
            return true;
        }

        $isHistoricalMember = ResearchClassGroupMemberHistory::query()
            ->where('research_class_group_id', $groupId)
            ->where('student_id', $user->getKey())
            ->exists();

        if ($isHistoricalMember) {
            return true;
        }

        // Current assigned adviser
        if ($request->researchClassGroup && (int) $request->researchClassGroup->adviser_id === (int) $user->getKey()) {
            return true;
        }

        // Class facilitator (read-only monitoring)
        if ($request->researchClassGroup && $request->researchClassGroup->researchClass && (int) $request->researchClassGroup->researchClass->facilitator_id === (int) $user->getKey()) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('consultations.request');
    }

    public function requesterManage(User $user, ConsultationRequest $request): bool
    {
        return (int) $request->requested_by === (int) $user->getKey();
    }

    public function adviserManage(User $user, ConsultationRequest $request): bool
    {
        if (! $user->can('consultations.manage-assigned')) {
            return false;
        }

        if (! $request->researchClassGroup) {
            return false;
        }

        return (int) $request->researchClassGroup->adviser_id === (int) $user->getKey();
    }

    public function facilitatorView(User $user, ConsultationRequest $request): bool
    {
        if (! $request->researchClassGroup || ! $request->researchClassGroup->researchClass) {
            return false;
        }

        return (int) $request->researchClassGroup->researchClass->facilitator_id === (int) $user->getKey();
    }
}
