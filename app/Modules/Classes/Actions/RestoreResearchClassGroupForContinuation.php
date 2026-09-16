<?php

namespace App\Modules\Classes\Actions;

use App\Enums\AccountStatus;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
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

final class RestoreResearchClassGroupForContinuation
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
                    ->where('status', 'disbanded')
                    ->lockForUpdate()
                    ->first();

                if ($lockedGroup === null) {
                    throw new ClassOperationException('The archived research group was not found.');
                }

                $history = ResearchClassGroupMemberHistory::query()
                    ->where('research_class_group_id', $lockedGroup->getKey())
                    ->where('student_id', $continuingStudent->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($history === null) {
                    throw new ClassOperationException('Only a former member may continue this archived research project.');
                }

                $lockedStudent = User::query()->lockForUpdate()->findOrFail($continuingStudent->getKey());

                if ($lockedStudent->status !== AccountStatus::Active || $lockedStudent->approved_at === null) {
                    throw new ClassOperationException('The continuing student must have an active, approved account.');
                }

                $enrollment = ResearchClassEnrollment::query()
                    ->where('research_class_id', $lockedClass->getKey())
                    ->where('student_id', $continuingStudent->getKey())
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if ($enrollment === null) {
                    throw new ClassOperationException('The continuing student must have an active enrollment in this class.');
                }

                $hasAnotherActiveGroup = ResearchClassGroupMember::query()
                    ->where('research_class_id', $lockedClass->getKey())
                    ->where('student_id', $continuingStudent->getKey())
                    ->lockForUpdate()
                    ->exists();

                if ($hasAnotherActiveGroup) {
                    throw new ClassOperationException('The continuing student already belongs to an active research group in this class.');
                }

                ResearchClassGroupMember::query()->create([
                    'research_class_group_id' => $lockedGroup->getKey(),
                    'research_class_id' => $lockedClass->getKey(),
                    'research_class_enrollment_id' => $enrollment->getKey(),
                    'student_id' => $continuingStudent->getKey(),
                    'assigned_by' => $facilitator->getKey(),
                ]);

                $previousDisbandedAt = $lockedGroup->disbanded_at;
                $lockedGroup->status = 'active';
                $lockedGroup->disbanded_at = null;
                $lockedGroup->leader_student_id = $continuingStudent->getKey();
                $lockedGroup->save();

                $this->auditLogs->write(
                    actor: $facilitator,
                    event: 'research-group.restored-for-continuation',
                    description: 'An archived research project was restored for a former member without resetting its records.',
                    requestContext: AuditRequestContext::fromRequest(request()),
                    auditable: $lockedGroup,
                    subjectName: $lockedGroup->name,
                    oldValues: [
                        'status' => 'disbanded',
                        'disbanded_at' => $previousDisbandedAt?->toIso8601String(),
                    ],
                    newValues: [
                        'status' => 'active',
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

            throw new ClassOperationException('The archived project could not be restored. Please try again.');
        }
    }
}
