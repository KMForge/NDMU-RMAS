<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserRequest;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RequestAdviserForResearchClassGroup
{
    public function handle(
        User $facilitator,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        User $adviser,
    ): ResearchClassGroupAdviserRequest {
        if (! $adviser->can('classes.serve-as-adviser') || ! $adviser->isActiveAndApproved()) {
            throw new ClassOperationException('Select an active and approved research adviser.');
        }

        try {
            return DB::transaction(function () use ($facilitator, $researchClass, $group, $adviser): ResearchClassGroupAdviserRequest {
                $lockedClass = ResearchClass::query()->lockForUpdate()->findOrFail($researchClass->getKey());

                if ($lockedClass->facilitator_id !== $facilitator->getKey()) {
                    throw new AuthorizationException('You cannot assign advisers for this class.');
                }

                $lockedGroup = ResearchClassGroup::query()
                    ->whereKey($group->getKey())
                    ->where('research_class_id', $lockedClass->getKey())
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if ($lockedGroup === null) {
                    throw new ClassOperationException('The class group was not found.');
                }

                if ($lockedGroup->adviser_id !== null) {
                    throw new ClassOperationException('This group already has an active adviser. Remove the current adviser first.');
                }

                $hasPendingRequest = ResearchClassGroupAdviserRequest::query()
                    ->where('research_class_group_id', $lockedGroup->getKey())
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->exists();

                if ($hasPendingRequest) {
                    throw new ClassOperationException('This group already has a pending adviser request.');
                }

                return ResearchClassGroupAdviserRequest::query()->create([
                    'research_class_group_id' => $lockedGroup->getKey(),
                    'adviser_id' => $adviser->getKey(),
                    'requested_by' => $facilitator->getKey(),
                    'status' => 'pending',
                    'requested_at' => now(),
                ]);
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The adviser request could not be sent. Please try again.');
        }
    }
}
