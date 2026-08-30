<?php

namespace App\Modules\Consultations\Queries;

use App\Models\ConsultationRecord;
use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GetAdviserConsultationData
{
    /**
     * @return array{
     *     consultationRequests: LengthAwarePaginator,
     *     consultationRecords: Collection<int, ConsultationRecord>,
     *     consultationStats: array{pending: int, approved: int, completed: int, rejected: int, total: int},
     *     consultationSearch: string,
     *     consultationStatus: string
     * }
     */
    public function for(
        User $adviser,
        string $search = '',
        string $status = 'all',
    ): array {
        $search = Str::limit(trim($search), 100, '');
        $allowedStatuses = ['pending', 'reschedule_proposed', 'approved', 'rejected', 'cancelled', 'completed', 'all'];
        $status = in_array($status, $allowedStatuses, true) ? $status : 'all';

        $query = ConsultationRequest::query()
            ->with([
                'researchClassGroup:id,name,leader_student_id',
                'researchClassGroup.leader:id,name',
                'researchClassGroup.members.student:id,name,email',
                'requester:id,name,email,student_id',
                'assignedAdviser:id,name,email',
                'reviewer:id,name,email',
                'relatedDocument:id,original_filename,document_stage,version_number',
                'proposals' => fn ($q) => $q->with(['proposer:id,name', 'responder:id,name'])->latest(),
            ])
            ->whereHas('researchClassGroup', fn (Builder $g) => $g->where('adviser_id', $adviser->id)->where('status', 'active')->whereNull('disbanded_at'))
            ->when($status !== 'all', fn (Builder $q) => $q->where('status', $status))
            ->when($search !== '', function (Builder $q) use ($search): void {
                $pattern = '%'.Str::lower($search).'%';
                $q->where(function (Builder $sq) use ($pattern): void {
                    $sq->whereRaw('LOWER(agenda) LIKE ?', [$pattern])
                        ->orWhereHas('requester', fn ($u) => $u->whereRaw('LOWER(name) LIKE ?', [$pattern]))
                        ->orWhereHas('researchClassGroup', fn ($g) => $g->whereRaw('LOWER(name) LIKE ?', [$pattern]));
                });
            })
            ->latest('created_at');

        $paginatedRequests = $query->paginate(10, ['*'], 'consultations_page')->withQueryString();

        $records = ConsultationRecord::query()
            ->with([
                'researchClassGroup:id,name',
                'conductedBy:id,name,email',
                'attendances.student:id,name,email',
                'supersedes',
            ])
            ->whereHas('researchClassGroup', fn (Builder $g) => $g->where('adviser_id', $adviser->id))
            ->latest('consulted_at')
            ->get();

        $statsQuery = ConsultationRequest::query()
            ->whereHas('researchClassGroup', fn (Builder $g) => $g->where('adviser_id', $adviser->id)->where('status', 'active')->whereNull('disbanded_at'));

        $counts = (clone $statsQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $completedCount = ConsultationRecord::query()
            ->whereHas('researchClassGroup', fn (Builder $g) => $g->where('adviser_id', $adviser->id))
            ->where('is_superseded', false)
            ->count();

        return [
            'consultationRequests' => $paginatedRequests,
            'consultationRecords' => $records,
            'consultationStats' => [
                'pending' => (int) $counts->get('pending', 0),
                'approved' => (int) $counts->get('approved', 0),
                'completed' => $completedCount,
                'rejected' => (int) $counts->get('rejected', 0),
                'total' => (int) $counts->sum(),
            ],
            'consultationSearch' => $search,
            'consultationStatus' => $status,
        ];
    }
}
