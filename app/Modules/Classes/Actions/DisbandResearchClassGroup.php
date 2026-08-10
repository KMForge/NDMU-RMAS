<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserHistory;
use App\Models\ResearchClassGroupAdviserRequest;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class DisbandResearchClassGroup
{
    public function handle(
        User $facilitator,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
    ): void {
        try {
            DB::transaction(function () use ($facilitator, $researchClass, $group): void {
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
                    throw new ClassOperationException('The group was not found or is already disbanded.');
                }

                $now = now();

                // 1. Cancel pending adviser requests
                ResearchClassGroupAdviserRequest::query()
                    ->where('research_class_group_id', $lockedGroup->getKey())
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_at' => $now,
                        'updated_at' => $now,
                    ]);

                // 2. End current active adviser assignment if present
                if ($lockedGroup->adviser_id !== null) {
                    ResearchClassGroupAdviserHistory::query()
                        ->where('research_class_group_id', $lockedGroup->getKey())
                        ->whereNull('ended_at')
                        ->update([
                            'ended_at' => $now,
                            'ended_by' => $facilitator->getKey(),
                            'updated_at' => $now,
                        ]);

                    $lockedGroup->adviser_id = null;
                }

                // 3. Detach active members so students return to Unassigned Students
                ResearchClassGroupMember::query()
                    ->where('research_class_group_id', $lockedGroup->getKey())
                    ->delete();

                // 4. Mark group as disbanded and clear active leader
                $lockedGroup->leader_student_id = null;
                $lockedGroup->status = 'disbanded';
                $lockedGroup->disbanded_at = $now;
                $lockedGroup->save();
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The group could not be disbanded. Please try again.');
        }
    }
}
