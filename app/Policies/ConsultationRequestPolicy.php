<?php

namespace App\Policies;

use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConsultationRequestPolicy
{
    public function manage(User $user, ConsultationRequest $consultationRequest): bool
    {
        if (! $user->can('consultations.manage-assigned')) {
            return false;
        }

        return DB::table('adviser_assignments as assignments')
            ->join('faculty_profiles as faculty', 'faculty.id', '=', 'assignments.adviser_id')
            ->where('assignments.id', $consultationRequest->adviser_assignment_id)
            ->where('faculty.user_id', $user->getKey())
            ->where('assignments.status', 'active')
            ->whereNull('assignments.ended_at')
            ->exists();
    }
}
