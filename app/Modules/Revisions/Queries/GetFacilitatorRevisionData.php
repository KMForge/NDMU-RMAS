<?php

namespace App\Modules\Revisions\Queries;

use App\Models\RevisionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class GetFacilitatorRevisionData
{
    /**
     * @return array<string, mixed>
     */
    public function for(User $facilitator, mixed $searchInput = null, mixed $statusInput = null): array
    {
        if (! Schema::hasTable('revision_requests')) {
            return [
                'monitoredRevisions' => new LengthAwarePaginator([], 0, 10),
            ];
        }

        $revisions = RevisionRequest::query()
            ->whereHas('researchClassGroup.researchClass', fn ($c) => $c->where('facilitator_id', $facilitator->getKey()))
            ->with([
                'researchClassGroup:id,name,research_title,adviser_id,research_class_id,status',
                'researchClassGroup.adviser:id,name,email',
                'researchClassGroup.researchClass:id,name,course_code',
                'sourceDocument:id,user_id,original_filename,file_type,file_size,document_stage,version_number,submitted_at,status',
                'submittedDocument:id,user_id,original_filename,file_type,file_size,document_stage,version_number,submitted_at,status',
                'requester:id,name,email',
            ])
            ->when($statusInput !== null && $statusInput !== 'all', fn (Builder $q) => $q->where('status', $statusInput))
            ->latest()
            ->paginate(10, ['*'], 'facilitator_revisions_page')
            ->withQueryString();

        return [
            'monitoredRevisions' => $revisions,
        ];
    }
}
