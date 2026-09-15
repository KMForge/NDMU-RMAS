<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class RemoveResearchClassGroupAdviser
{
    public function handle(
        User $facilitator,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
    ): void {
        DB::transaction(function () use ($facilitator, $researchClass, $group): void {
            $lockedClass = ResearchClass::query()->lockForUpdate()->findOrFail($researchClass->getKey());

            if ($lockedClass->facilitator_id !== $facilitator->getKey()) {
                throw new AuthorizationException('You cannot manage advisers for this class.');
            }

            $lockedGroup = ResearchClassGroup::query()
                ->whereKey($group->getKey())
                ->where('research_class_id', $lockedClass->getKey())
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if ($lockedGroup === null || $lockedGroup->adviser_id === null) {
                throw new ClassOperationException('The group does not currently have an active adviser.');
            }

            throw new ClassOperationException('An active adviser cannot be removed directly. Submit and approve a RES-030 Adviser Change Request Form instead.');
        }, 3);
    }
}
