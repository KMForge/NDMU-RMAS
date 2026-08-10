<?php

namespace App\Modules\Revisions\Queries;

use App\Models\ResearchClassGroupMember;
use App\Models\ResearchClassGroupMemberHistory;
use App\Models\RevisionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class GetStudentRevisionData
{
    /**
     * @return array<string, mixed>
     */
    public function for(User $student): array
    {
        if (! Schema::hasTable('revision_requests')) {
            return [
                'activeRevisions' => collect(),
                'revisionHistory' => new LengthAwarePaginator([], 0, 10),
            ];
        }

        $studentGroupIds = DB_TABLE_GROUPS_FALLBACK($student);

        $query = RevisionRequest::query()
            ->where(function (Builder $q) use ($student, $studentGroupIds): void {
                $q->whereIn('research_class_group_id', $studentGroupIds)
                    ->orWhere('assigned_to', $student->getKey());
            })
            ->with([
                'researchClassGroup:id,name,research_title,adviser_id,group_leader_id,status',
                'researchClassGroup.adviser:id,name,email',
                'sourceDocument:id,user_id,original_filename,file_type,file_size,document_stage,version_number,submitted_at,status',
                'sourceDocument.reviewComments' => fn ($comments) => $comments->latest(),
                'sourceReview:id,reviewer_id,decision,review_notes,reviewed_at',
                'submittedDocument:id,user_id,original_filename,file_type,file_size,document_stage,version_number,submitted_at,status',
                'requester:id,name,email',
                'events' => fn ($events) => $events->with('actor:id,name')->latest('occurred_at'),
            ]);

        $activeRevisions = (clone $query)
            ->whereIn('status', ['open', 'in_progress', 'submitted'])
            ->latest()
            ->get();

        $revisionHistory = (clone $query)
            ->whereIn('status', ['resolved', 'cancelled'])
            ->latest()
            ->paginate(10, ['*'], 'history_page');

        return [
            'activeRevisions' => $activeRevisions,
            'revisionHistory' => $revisionHistory,
        ];
    }
}

function DB_TABLE_GROUPS_FALLBACK(User $student): array
{
    $activeGroupIds = ResearchClassGroupMember::query()
        ->where('student_id', $student->getKey())
        ->whereHas('researchClassEnrollment', fn ($e) => $e->where('status', 'active'))
        ->pluck('research_class_group_id')
        ->toArray();

    $historicalGroupIds = ResearchClassGroupMemberHistory::query()
        ->where('student_id', $student->getKey())
        ->pluck('research_class_group_id')
        ->toArray();

    return array_values(array_unique(array_merge($activeGroupIds, $historicalGroupIds)));
}
