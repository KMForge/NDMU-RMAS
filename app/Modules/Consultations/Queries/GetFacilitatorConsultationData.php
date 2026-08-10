<?php

namespace App\Modules\Consultations\Queries;

use App\Models\ConsultationRecord;
use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GetFacilitatorConsultationData
{
    /**
     * @return array{
     *     facilitatorRequests: LengthAwarePaginator,
     *     facilitatorRecords: Collection<int, ConsultationRecord>,
     *     facilitatorSearch: string,
     *     facilitatorStatus: string
     * }
     */
    public function for(
        User $facilitator,
        string $search = '',
        string $status = 'all',
    ): array {
        $search = Str::limit(trim($search), 100, '');
        $allowedStatuses = ['pending', 'reschedule_proposed', 'approved', 'rejected', 'cancelled', 'completed', 'all'];
        $status = in_array($status, $allowedStatuses, true) ? $status : 'all';

        $requests = ConsultationRequest::query()
            ->with([
                'researchClassGroup:id,name,research_class_id,adviser_id',
                'researchClassGroup.adviser:id,name,email',
                'researchClassGroup.researchClass:id,name',
                'requester:id,name,email,student_id',
                'assignedAdviser:id,name,email',
                'reviewer:id,name,email',
            ])
            ->whereHas('researchClassGroup.researchClass', fn (Builder $c) => $c->where('facilitator_id', $facilitator->id))
            ->when($status !== 'all', fn (Builder $q) => $q->where('status', $status))
            ->when($search !== '', function (Builder $q) use ($search): void {
                $pattern = '%'.Str::lower($search).'%';
                $q->where(function (Builder $sq) use ($pattern): void {
                    $sq->whereRaw('LOWER(agenda) LIKE ?', [$pattern])
                        ->orWhereHas('requester', fn ($u) => $u->whereRaw('LOWER(name) LIKE ?', [$pattern]))
                        ->orWhereHas('researchClassGroup', fn ($g) => $g->whereRaw('LOWER(name) LIKE ?', [$pattern]));
                });
            })
            ->latest('created_at')
            ->paginate(10, ['*'], 'facilitator_consultations_page')
            ->withQueryString();

        $records = ConsultationRecord::query()
            ->with([
                'researchClassGroup:id,name',
                'conductedBy:id,name,email',
                'attendances.student:id,name,email',
            ])
            ->whereHas('researchClassGroup.researchClass', fn (Builder $c) => $c->where('facilitator_id', $facilitator->id))
            ->where('is_superseded', false)
            ->latest('consulted_at')
            ->get();

        return [
            'facilitatorRequests' => $requests,
            'facilitatorRecords' => $records,
            'facilitatorSearch' => $search,
            'facilitatorStatus' => $status,
        ];
    }
}
