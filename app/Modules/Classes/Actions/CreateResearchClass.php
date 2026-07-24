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
        User $adviser,
        string $creationToken,
        string $name,
        ?string $description,
        ?string $requestedJoinCode,
        int $maxStudents,
    ): ResearchClass {
        $lock = Cache::lock("class-creation:{$adviser->getKey()}:{$creationToken}", 30);

        if (! $lock->get()) {
            throw new DuplicateClassOperation('This class creation request is already being processed.');
        }

        try {
            if (ResearchClass::query()
                ->where('adviser_id', $adviser->getKey())
                ->where('creation_token', $creationToken)
                ->exists()) {
                throw new DuplicateClassOperation('This class has already been created.');
            }

            $joinCode = $requestedJoinCode === null || $requestedJoinCode === ''
                ? $this->generateJoinCode()
                : ResearchClass::normalizeJoinCode($requestedJoinCode);

            if (strlen($joinCode) < 5 || strlen($joinCode) > 16) {
                throw new ClassOperationException('The normalized class code must contain 5 to 16 letters or numbers.');
            }

            if (ResearchClass::query()
                ->where('join_code_hash', ResearchClass::joinCodeFingerprint($joinCode))
                ->exists()) {
                throw new ClassOperationException('That class code is already in use.');
            }

            return DB::transaction(function () use (
                $adviser,
                $creationToken,
                $name,
                $description,
                $joinCode,
                $maxStudents,
            ): ResearchClass {
                $researchClass = new ResearchClass([
                    'adviser_id' => $adviser->getKey(),
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
