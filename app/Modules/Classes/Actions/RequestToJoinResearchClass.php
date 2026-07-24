<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RequestToJoinResearchClass
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
                    ->lockForUpdate()
                    ->first();

                if ($existing?->status === 'active') {
                    throw new DuplicateClassOperation('You are already enrolled in this class.');
                }

                if ($existing?->status === 'pending') {
                    throw new DuplicateClassOperation('Your join request is already pending adviser review.');
                }

                $activeStudents = ResearchClassEnrollment::query()
                    ->where('research_class_id', $researchClass->getKey())
                    ->where('status', 'active')
                    ->count();

                if ($activeStudents >= $researchClass->max_students) {
                    throw new ClassOperationException('This class has reached its enrollment limit.');
                }

                $requestData = [
                    'status' => 'pending',
                    'requested_at' => now(),
                    'joined_at' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ];

                if ($existing !== null) {
                    $existing->update($requestData);

                    return $existing->refresh();
                }

                return ResearchClassEnrollment::query()->create([
                    'research_class_id' => $researchClass->getKey(),
                    'student_id' => $student->getKey(),
                    ...$requestData,
                ]);
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The join request could not be submitted. Please try again.');
        }
    }
}
