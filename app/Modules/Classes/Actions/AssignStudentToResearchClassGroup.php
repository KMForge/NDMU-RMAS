<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AssignStudentToResearchClassGroup
{
    public function handle(
        User $facilitator,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        ResearchClassEnrollment $enrollment,
    ): ResearchClassGroupMember {
        try {
            return DB::transaction(function () use ($facilitator, $researchClass, $group, $enrollment): ResearchClassGroupMember {
                $lockedClass = ResearchClass::query()->lockForUpdate()->findOrFail($researchClass->getKey());

                if ($lockedClass->facilitator_id !== $facilitator->getKey()) {
                    throw new ClassOperationException('You cannot manage groups for this class.');
                }

                $lockedGroup = ResearchClassGroup::query()
                    ->whereKey($group->getKey())
                    ->where('research_class_id', $lockedClass->getKey())
                    ->lockForUpdate()
                    ->first();
                $lockedEnrollment = ResearchClassEnrollment::query()
                    ->whereKey($enrollment->getKey())
                    ->where('research_class_id', $lockedClass->getKey())
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if ($lockedGroup === null || $lockedEnrollment === null) {
                    throw new ClassOperationException('The group or active class enrollment was not found.');
                }

                $member = ResearchClassGroupMember::query()
                    ->where('research_class_id', $lockedClass->getKey())
                    ->where('student_id', $lockedEnrollment->student_id)
                    ->lockForUpdate()
                    ->first();

                $attributes = [
                    'research_class_group_id' => $lockedGroup->getKey(),
                    'research_class_id' => $lockedClass->getKey(),
                    'research_class_enrollment_id' => $lockedEnrollment->getKey(),
                    'student_id' => $lockedEnrollment->student_id,
                    'assigned_by' => $facilitator->getKey(),
                ];

                if ($member !== null) {
                    $member->update($attributes);

                    return $member->refresh();
                }

                return ResearchClassGroupMember::query()->create($attributes);
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The student could not be assigned to the group. Please try again.');
        }
    }
}
