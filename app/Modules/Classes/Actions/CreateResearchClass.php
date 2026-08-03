<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateResearchClass
{
    public function handle(
        User $facilitator,
        string $creationToken,
        string $name,
        ?string $description,
        int $maxStudents,
    ): ResearchClass {
        $lock = Cache::lock("class-creation:{$facilitator->getKey()}:{$creationToken}", 30);

        if (! $lock->get()) {
            throw new DuplicateClassOperation('This class creation request is already being processed.');
        }

        try {
            if (ResearchClass::query()
                ->where('facilitator_id', $facilitator->getKey())
                ->where('creation_token', $creationToken)
                ->exists()) {
                throw new DuplicateClassOperation('This class has already been created.');
            }

            $joinCode = $this->generateJoinCode();

            return DB::transaction(function () use (
                $facilitator,
                $creationToken,
                $name,
                $description,
                $joinCode,
                $maxStudents,
            ): ResearchClass {
                $researchClass = new ResearchClass([
                    'facilitator_id' => $facilitator->getKey(),
                    'creation_token' => $creationToken,
                    'name' => $name,
                    'description' => $description,
                    'max_students' => $maxStudents,
                    'is_active' => true,
                ]);
                $researchClass->setJoinCode($joinCode);
                $researchClass->save();

                return $researchClass;
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The class could not be created. Please try again.');
        } finally {
            $lock->release();
        }
    }

    private function generateJoinCode(): string
    {
        do {
            $joinCode = Str::upper(Str::random(8));
            $exists = ResearchClass::query()
                ->where('join_code_hash', ResearchClass::joinCodeFingerprint($joinCode))
                ->exists();
        } while ($exists);

        return $joinCode;
    }
}
