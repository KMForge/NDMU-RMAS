<?php

namespace App\Modules\Consultations\Queries;

use App\Models\ConsultationRecord;
use App\Models\ConsultationRequest;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use Illuminate\Support\Collection;

class GetStudentConsultationData
{
    /**
     * @return array{
     *     assignedGroup: mixed,
     *     assignedAdviser: mixed,
     *     consultationRequests: Collection<int, ConsultationRequest>,
     *     consultationRecords: Collection<int, ConsultationRecord>
     * }
     */
    public function for(User $student): array
    {
        $groupMember = ResearchClassGroupMember::query()
            ->with(['researchClassGroup.adviser:id,name,email', 'researchClassGroup.members.student:id,name,email'])
            ->where('student_id', $student->id)
            ->whereHas('researchClassGroup', fn ($g) => $g->where('status', 'active')->whereNull('disbanded_at'))
            ->latest()
            ->first();

        $group = $groupMember?->researchClassGroup;
        $adviser = $group?->adviser;

        if (! $group) {
            return [
                'assignedGroup' => null,
                'assignedAdviser' => null,
                'consultationRequests' => collect(),
                'consultationRecords' => collect(),
            ];
        }

        $requests = ConsultationRequest::query()
            ->with([
                'requester:id,name,email',
                'assignedAdviser:id,name,email',
                'reviewer:id,name,email',
                'canceller:id,name,email',
                'relatedDocument:id,original_filename,document_stage,version_number,is_current',
                'proposals' => fn ($q) => $q->with(['proposer:id,name', 'responder:id,name'])->latest(),
            ])
            ->where('research_class_group_id', $group->id)
            ->latest('created_at')
            ->get();

        $records = ConsultationRecord::query()
            ->with([
                'conductedBy:id,name,email',
                'attendances.student:id,name,email',
                'supersedes',
            ])
            ->where('research_class_group_id', $group->id)
            ->latest('consulted_at')
            ->get();

        return [
            'assignedGroup' => $group,
            'assignedAdviser' => $adviser,
            'consultationRequests' => $requests,
            'consultationRecords' => $records,
        ];
    }
}
