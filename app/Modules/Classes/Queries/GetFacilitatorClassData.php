<?php

namespace App\Modules\Classes\Queries;

use App\Models\ResearchClass;
use App\Models\User;
use Illuminate\Support\Collection;

class GetFacilitatorClassData
{
    /** @return array<string, mixed> */
    public function for(User $facilitator): array
    {
        $classes = ResearchClass::query()
            ->where('facilitator_id', $facilitator->getKey())
            ->withCount([
                'enrollments as active_students_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->latest()
            ->get();

        return [
            'researchClasses' => $classes,
            'classJoinRequests' => new Collection,
            'classRequestStats' => ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0],
            'requestStats' => ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0],
            'requestSearch' => '',
            'requestStatus' => 'all',
            'classAdviserOptions' => new Collection,
        ];
    }
}
