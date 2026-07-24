<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ReviewResearchClassJoinRequest
{
    public function approve(
        User $adviser,
        ResearchClass $researchClass,
        ResearchClassEnrollment $joinRequest,
    ): ResearchClassEnrollment {
        return $this->review($adviser, $researchClass, $joinRequest, 'active');
    }

    public function reject(
        User $adviser,
        ResearchClass $researchClass,
        ResearchClassEnrollment $joinRequest,
    ): ResearchClassEnrollment {
        return $this->review($adviser, $researchClass, $joinRequest, 'rejected');
    }

    private function review(
        User $adviser,
        ResearchClass $researchClass,
        ResearchClassEnrollment $joinRequest,
        string $decision,
    ): ResearchClassEnrollment {
        try {
            return DB::transaction(function () use (
                $adviser,
                $researchClass,
                $joinRequest,
                $decision,
            ): ResearchClassEnrollment {
                $lockedClass = ResearchClass::query()
                    ->whereKey($researchClass->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedClass->adviser_id !== $adviser->getKey()) {
                    throw new ClassOperationException('This join request does not belong to your class.');
                }

                $lockedRequest = ResearchClassEnrollment::query()
                    ->whereKey($joinRequest->getKey())
                    ->where('research_class_id', $lockedClass->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedRequest === null) {
                    throw new ClassOperationException('The join request was not found for this class.');
                }

                if ($lockedRequest->status !== 'pending') {
                    throw new DuplicateClassOperation('This join request has already been reviewed.');
                }

                if ($decision === 'active') {
                    $activeStudents = ResearchClassEnrollment::query()
                        ->where('research_class_id', $lockedClass->getKey())
                        ->where('status', 'active')
                        ->count();

                    if ($activeStudents >= $lockedClass->max_students) {
                        throw new ClassOperationException('This class has reached its enrollment limit.');
                    }
                }

                $lockedRequest->update([
                    'status' => $decision,
                    'joined_at' => $decision === 'active' ? now() : null,
                    'reviewed_by' => $adviser->getKey(),
                    'reviewed_at' => now(),
                ]);

                return $lockedRequest->refresh();
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The join request could not be reviewed. Please try again.');
        }
    }
}
