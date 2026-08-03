<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CreateResearchClassGroup
{
    public function handle(
        User $facilitator,
        ResearchClass $researchClass,
        string $creationToken,
        string $name,
    ): ResearchClassGroup {
        $lock = Cache::lock("class-group:{$researchClass->getKey()}:{$creationToken}", 30);

        if (! $lock->get()) {
            throw new DuplicateClassOperation('This group creation request is already being processed.');
        }

        try {
            return DB::transaction(function () use ($facilitator, $researchClass, $creationToken, $name): ResearchClassGroup {
                $lockedClass = ResearchClass::query()->lockForUpdate()->findOrFail($researchClass->getKey());

                if ($lockedClass->facilitator_id !== $facilitator->getKey()) {
                    throw new ClassOperationException('You cannot manage groups for this class.');
                }

                $duplicate = ResearchClassGroup::query()
                    ->where('research_class_id', $lockedClass->getKey())
                    ->where(function ($query) use ($creationToken, $name): void {
                        $query->where('creation_token', $creationToken)->orWhere('name', $name);
                    })
                    ->lockForUpdate()
                    ->exists();

                if ($duplicate) {
                    throw new DuplicateClassOperation('This class group already exists.');
                }

                return ResearchClassGroup::query()->create([
                    'research_class_id' => $lockedClass->getKey(),
                    'creation_token' => $creationToken,
                    'name' => $name,
                    'created_by' => $facilitator->getKey(),
                ]);
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The class group could not be created. Please try again.');
        } finally {
            $lock->release();
        }
    }
}
