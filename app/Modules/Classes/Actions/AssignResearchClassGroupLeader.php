<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use App\Modules\Classes\Exceptions\ClassOperationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AssignResearchClassGroupLeader
{
    public function __construct(private readonly AuditLogWriter $auditLogs) {}

    public function handle(
        User $facilitator,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        User $student,
    ): ResearchClassGroup {
        try {
            return DB::transaction(function () use ($facilitator, $researchClass, $group, $student): ResearchClassGroup {
                $lockedClass = ResearchClass::query()->lockForUpdate()->findOrFail($researchClass->getKey());

                if ($lockedClass->facilitator_id !== $facilitator->getKey()) {
                    throw new AuthorizationException('You cannot manage groups for this class.');
                }

                $lockedGroup = ResearchClassGroup::query()
                    ->whereKey($group->getKey())
                    ->where('research_class_id', $lockedClass->getKey())
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if ($lockedGroup === null) {
                    throw new ClassOperationException('The group was not found or is no longer active.');
                }

                $isGroupMember = ResearchClassGroupMember::query()
                    ->where('research_class_group_id', $lockedGroup->getKey())
                    ->where('student_id', $student->getKey())
                    ->lockForUpdate()
                    ->exists();

                if (! $isGroupMember) {
                    throw new ClassOperationException('Only active members of this research group can be assigned as Group Leader.');
                }

                $previousLeaderId = $lockedGroup->leader_student_id;
                $lockedGroup->leader_student_id = $student->getKey();
                $lockedGroup->save();

                $this->auditLogs->write(
                    actor: $facilitator,
                    event: 'research-group.leader-assigned',
                    description: 'A research group leader was assigned.',
                    requestContext: AuditRequestContext::fromRequest(request()),
                    auditable: $lockedGroup,
                    subjectName: $lockedGroup->name,
                    oldValues: ['leader_student_id' => $previousLeaderId],
                    newValues: ['leader_student_id' => $student->getKey()],
                    actorContext: 'research-facilitator',
                );

                return $lockedGroup->refresh();
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The Group Leader could not be assigned. Please try again.');
        }
    }
}
