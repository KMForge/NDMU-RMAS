<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class JoinResearchClass
{
    public function handle(User $student, string $joinCode): ResearchClassEnrollment
    {
        try {
            return DB::transaction(function () use ($student, $joinCode): ResearchClassEnrollment {
                $researchClass = ResearchClass::query()
                    ->where('join_code_hash', ResearchClass::joinCodeFingerprint($joinCode))
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if ($researchClass === null) {
                    throw new ClassOperationException('No active class was found for that join code.');
                }

                $existing = ResearchClassEnrollment::query()
                    ->where('research_class_id', $researchClass->getKey())
                    ->where('student_id', $student->getKey())
                    ->first();

                if ($existing !== null) {
                    throw new DuplicateClassOperation('You have already joined this class.');
                }

                $activeStudents = ResearchClassEnrollment::query()
                    ->where('research_class_id', $researchClass->getKey())
                    ->where('status', 'active')
                    ->count();

                if ($activeStudents >= $researchClass->max_students) {
                    throw new ClassOperationException('This class has reached its enrollment limit.');
                }

                return ResearchClassEnrollment::query()->create([
                    'research_class_id' => $researchClass->getKey(),
                    'student_id' => $student->getKey(),
                    'status' => 'active',
                    'joined_at' => now(),
                ]);
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The class could not be joined. Please try again.');
        }
    }
}
