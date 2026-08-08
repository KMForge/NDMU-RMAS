<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RenameResearchClassGroup
{
    public function handle(
        User $facilitator,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        string $name,
    ): ResearchClassGroup {
        try {
            return DB::transaction(function () use ($facilitator, $researchClass, $group, $name): ResearchClassGroup {
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
                    throw new ClassOperationException('The active group was not found.');
                }

                $trimmedName = trim($name);

                $duplicate = ResearchClassGroup::query()
                    ->where('research_class_id', $lockedClass->getKey())
                    ->where('status', 'active')
                    ->where('name', $trimmedName)
                    ->where('id', '<>', $lockedGroup->getKey())
                    ->lockForUpdate()
                    ->exists();

                if ($duplicate) {
                    throw new DuplicateClassOperation('A group with this name already exists in this class.');
                }

                $lockedGroup->update(['name' => $trimmedName]);

                return $lockedGroup->refresh();
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The group could not be renamed. Please try again.');
        }
    }
}
