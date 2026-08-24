<?php

namespace App\Modules\Documents\Support;

use App\Models\Document;
use App\Models\ResearchClassGroupMember;
use App\Models\ResearchClassGroupMemberHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DocumentGroupAccess
{
    public function isActiveMember(User $user, Document $document): bool
    {
        if ($document->research_class_group_id === null) {
            return false;
        }

        return $this->activeMembershipQuery($user)
            ->where('research_class_group_id', $document->research_class_group_id)
            ->exists();
    }

    public function activeMembershipFor(User $user): ?ResearchClassGroupMember
    {
        return $this->activeMembershipQuery($user)
            ->with([
                'researchClassGroup.leader',
                'researchClassGroup.adviser',
                'researchClassGroup.researchClass',
            ])
            ->first();
    }

    public function isHistoricalMember(User $user, Document $document): bool
    {
        if ($document->research_class_group_id === null) {
            return false;
        }

        return ResearchClassGroupMemberHistory::query()
            ->where('student_id', $user->getKey())
            ->where('research_class_group_id', $document->research_class_group_id)
            ->whereHas('group', fn ($query) => $query
                ->where('status', 'disbanded')
                ->whereNotNull('disbanded_at'))
            ->exists();
    }

    /**
     * @return Builder<ResearchClassGroupMember>
     */
    private function activeMembershipQuery(User $user): Builder
    {
        return ResearchClassGroupMember::query()
            ->where('student_id', $user->getKey())
            ->whereHas('researchClassGroup', fn ($query) => $query
                ->where('status', 'active')
                ->whereNull('disbanded_at'))
            ->whereHas('researchClassEnrollment', fn ($query) => $query
                ->where('status', 'active'));
    }
}
