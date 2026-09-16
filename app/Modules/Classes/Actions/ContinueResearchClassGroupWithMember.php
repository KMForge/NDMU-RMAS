<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\ResearchClassGroupMemberHistory;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use App\Modules\Classes\Exceptions\ClassOperationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class ContinueResearchClassGroupWithMember
{
    public function __construct(private readonly AuditLogWriter $auditLogs) {}

    public function handle(
        User $facilitator,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        User $continuingStudent,
    ): ResearchClassGroup {
        try {
            return DB::transaction(function () use ($facilitator, $researchClass, $group, $continuingStudent): ResearchClassGroup {
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
                    throw new ClassOperationException('The active research group was not found.');
                }

                $members = ResearchClassGroupMember::query()
                    ->where('research_class_group_id', $lockedGroup->getKey())
                    ->lockForUpdate()
                    ->get();
                $continuingMembership = $members->firstWhere('student_id', $continuingStudent->getKey());

                if ($continuingMembership === null) {
                    throw new ClassOperationException('Only a current member may continue this research project.');
                }

                $now = now();
                $departingMembers = $members->where('student_id', '!=', $continuingStudent->getKey());

                foreach ($departingMembers as $member) {
                    ResearchClassGroupMemberHistory::query()->updateOrCreate(
                        [
                            'research_class_group_id' => $lockedGroup->getKey(),
                            'student_id' => $member->student_id,
                        ],
                        [
                            'research_class_id' => $member->research_class_id,
                            'research_class_enrollment_id' => $member->research_class_enrollment_id,
                            'assigned_by' => $member->assigned_by,
                            'joined_at' => $member->created_at,
                            'archived_at' => $now,
                            'archive_reason' => 'group_restructured',
                        ],
                    );
                }

                ResearchClassGroupMember::query()
                    ->where('research_class_group_id', $lockedGroup->getKey())
                    ->where('student_id', '!=', $continuingStudent->getKey())
                    ->delete();

                $previousLeaderId = $lockedGroup->leader_student_id;
                $lockedGroup->leader_student_id = $continuingStudent->getKey();
                $lockedGroup->save();

                $this->auditLogs->write(
                    actor: $facilitator,
                    event: 'research-group.continued-by-member',
                    description: 'A continuing member retained the research project and its existing records.',
                    requestContext: AuditRequestContext::fromRequest(request()),
                    auditable: $lockedGroup,
                    subjectName: $lockedGroup->name,
                    oldValues: [
                        'leader_student_id' => $previousLeaderId,
                        'member_ids' => $members->pluck('student_id')->values()->all(),
                    ],
                    newValues: [
                        'leader_student_id' => $continuingStudent->getKey(),
                        'member_ids' => [$continuingStudent->getKey()],
                        'preserved_research_class_group_id' => $lockedGroup->getKey(),
                    ],
                    actorContext: 'research-facilitator',
                );

                return $lockedGroup->refresh();
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The project continuation could not be saved. Please try again.');
        }
    }
}
