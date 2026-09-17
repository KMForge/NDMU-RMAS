<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;

class SynchronizeResearchClassGroupLeadership
{
    public function __construct(private readonly AuditLogWriter $auditLogs) {}

    /**
     * Keep a valid existing leader, automatically appoint a sole member, and
     * leave leadership pending when multiple members have no valid leader.
     */
    public function handle(ResearchClassGroup $group, User $actor): ResearchClassGroup
    {
        $memberIds = ResearchClassGroupMember::query()
            ->where('research_class_group_id', $group->getKey())
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('student_id');

        $previousLeaderId = $group->leader_student_id;
        $hasValidLeader = $previousLeaderId !== null
            && $memberIds->contains(fn ($studentId) => (int) $studentId === (int) $previousLeaderId);

        if ($hasValidLeader) {
            return $group;
        }

        $nextLeaderId = $memberIds->count() === 1 ? (int) $memberIds->first() : null;
        if ((int) $previousLeaderId === (int) $nextLeaderId
            && ($previousLeaderId !== null) === ($nextLeaderId !== null)) {
            return $group;
        }

        $group->forceFill(['leader_student_id' => $nextLeaderId])->save();

        $event = $nextLeaderId === null
            ? 'research-group.leader-cleared'
            : 'research-group.leader-assigned';

        $this->auditLogs->write(
            actor: $actor,
            event: $event,
            description: $nextLeaderId === null
                ? 'Group leadership requires manual selection because multiple members remain.'
                : 'The sole group member was automatically assigned as Group Leader.',
            requestContext: AuditRequestContext::fromRequest(request()),
            auditable: $group,
            subjectName: $group->name,
            oldValues: ['leader_student_id' => $previousLeaderId],
            newValues: [
                'leader_student_id' => $nextLeaderId,
                'assignment_mode' => $nextLeaderId === null ? 'manual_required' : 'automatic_sole_member',
                'active_member_count' => $memberIds->count(),
            ],
            actorContext: 'research-facilitator',
        );

        return $group->refresh();
    }
}
