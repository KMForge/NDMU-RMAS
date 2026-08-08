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

class CancelResearchClassGroupAdviserRequest
{
    public function handle(
        User $facilitator,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        ResearchClassGroupAdviserRequest $adviserRequest,
    ): void {
        try {
            DB::transaction(function () use ($facilitator, $researchClass, $group, $adviserRequest): void {
                $lockedClass = ResearchClass::query()->lockForUpdate()->findOrFail($researchClass->getKey());

                if ($lockedClass->facilitator_id !== $facilitator->getKey()) {
                    throw new AuthorizationException('You cannot manage adviser requests for this class.');
                }

                $lockedGroup = ResearchClassGroup::query()
                    ->whereKey($group->getKey())
                    ->where('research_class_id', $lockedClass->getKey())
                    ->lockForUpdate()
                    ->first();

                $lockedRequest = ResearchClassGroupAdviserRequest::query()
                    ->whereKey($adviserRequest->getKey())
                    ->where('research_class_group_id', $group->getKey())
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if ($lockedGroup === null || $lockedRequest === null) {
                    throw new ClassOperationException('The pending adviser request was not found.');
                }

                $lockedRequest->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The adviser request could not be cancelled. Please try again.');
        }
    }
}
