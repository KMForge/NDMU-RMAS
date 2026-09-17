<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BulkAssignStudentsToResearchClassGroup
{
    public function __construct(
        private readonly SynchronizeResearchClassGroupLeadership $synchronizeLeadership,
    ) {}

    /**
     * @param  array<int, int>  $enrollmentIds
     * @return Collection<int, ResearchClassGroupMember>
     */
    public function handle(
        User $facilitator,
        ResearchClass $researchClass,
        int $groupId,
        array $enrollmentIds,
    ): Collection {
        if (empty($enrollmentIds)) {
            throw new ClassOperationException('Please select at least one student to assign.');
        }

        try {
            return DB::transaction(function () use ($facilitator, $researchClass, $groupId, $enrollmentIds): Collection {
                $lockedClass = ResearchClass::query()->lockForUpdate()->findOrFail($researchClass->getKey());

                if ($lockedClass->facilitator_id !== $facilitator->getKey()) {
                    throw new AuthorizationException('You cannot manage groups for this class.');
                }

                $lockedGroup = ResearchClassGroup::query()
                    ->whereKey($groupId)
                    ->where('research_class_id', $lockedClass->getKey())
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if ($lockedGroup === null) {
                    throw new ClassOperationException('The selected active research group was not found.');
                }

                $uniqueEnrollmentIds = array_values(array_unique(array_filter($enrollmentIds)));

                $validEnrollments = ResearchClassEnrollment::query()
                    ->whereIn('id', $uniqueEnrollmentIds)
                    ->where('research_class_id', $lockedClass->getKey())
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->get();

                if ($validEnrollments->count() !== count($uniqueEnrollmentIds)) {
                    throw new ClassOperationException('One or more selected students do not have active class enrollments.');
                }

                $existingGroupMembers = ResearchClassGroupMember::query()
                    ->where('research_class_group_id', $lockedGroup->getKey())
                    ->lockForUpdate()
                    ->get();

                $currentMemberEnrollmentIds = $existingGroupMembers->pluck('research_class_enrollment_id')->toArray();
                $newEnrollments = $validEnrollments->reject(fn ($enr) => in_array($enr->id, $currentMemberEnrollmentIds, true));

                $totalAfterAssignment = $existingGroupMembers->count() + $newEnrollments->count();
                if ($totalAfterAssignment > 4) {
                    $availableSlots = max(0, 4 - $existingGroupMembers->count());
                    throw new ClassOperationException(
                        "Cannot assign {$newEnrollments->count()} student(s) to {$lockedGroup->name}. ".
                        "Group currently has {$existingGroupMembers->count()} member(s) (Maximum 4 allowed, {$availableSlots} slot(s) remaining)."
                    );
                }

                $assignedMembers = collect();
                $sourceGroupsToSynchronize = collect();

                foreach ($validEnrollments as $enrollment) {
                    $existingMember = ResearchClassGroupMember::query()
                        ->where('research_class_id', $lockedClass->getKey())
                        ->where('student_id', $enrollment->student_id)
                        ->lockForUpdate()
                        ->first();

                    $attributes = [
                        'research_class_group_id' => $lockedGroup->getKey(),
                        'research_class_id' => $lockedClass->getKey(),
                        'research_class_enrollment_id' => $enrollment->getKey(),
                        'student_id' => $enrollment->student_id,
                        'assigned_by' => $facilitator->getKey(),
                    ];

                    if ($existingMember !== null) {
                        if ($existingMember->research_class_group_id !== $lockedGroup->getKey()) {
                            $oldGroup = ResearchClassGroup::query()
                                ->lockForUpdate()
                                ->find($existingMember->research_class_group_id);
                            if ($oldGroup !== null) {
                                $sourceGroupsToSynchronize->put($oldGroup->getKey(), $oldGroup);
                            }
                        }

                        $existingMember->update($attributes);
                        $assignedMembers->push($existingMember->refresh());
                    } else {
                        $newMember = ResearchClassGroupMember::query()->create($attributes);
                        $assignedMembers->push($newMember);
                    }
                }

                foreach ($sourceGroupsToSynchronize as $sourceGroup) {
                    $this->synchronizeLeadership->handle($sourceGroup, $facilitator);
                }
                $this->synchronizeLeadership->handle($lockedGroup, $facilitator);

                return $assignedMembers;
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The students could not be assigned to the group. Please try again.');
        }
    }
}
