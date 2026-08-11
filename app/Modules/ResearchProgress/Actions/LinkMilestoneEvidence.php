<?php

namespace App\Modules\ResearchProgress\Actions;

use App\Models\ConsultationRecord;
use App\Models\Document;
use App\Models\DocumentReview;
use App\Models\MilestoneEvidence;
use App\Models\ResearchGroupMilestone;
use App\Models\ResearchGroupMilestoneEvent;
use App\Models\RevisionRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LinkMilestoneEvidence
{
    public function execute(User $actor, ResearchGroupMilestone $milestone, string $type, int $id, ?string $summary, ?string $ipAddress): MilestoneEvidence
    {
        return DB::transaction(function () use ($actor, $milestone, $type, $id, $summary, $ipAddress): MilestoneEvidence {
            $locked = ResearchGroupMilestone::query()->with(['group.researchClass'])->whereKey($milestone->getKey())->lockForUpdate()->firstOrFail();
            if (! $actor->can('manage', $locked)) {
                throw new AuthorizationException;
            }

            $evidence = $this->resolve($type, $id);
            if ($evidence === null || $this->groupId($type, $evidence) !== $locked->research_class_group_id) {
                throw ValidationException::withMessages(['evidence_id' => 'The selected evidence does not belong to this research group.']);
            }

            $link = MilestoneEvidence::query()->firstOrCreate(
                ['research_group_milestone_id' => $locked->getKey(), 'evidence_type' => $type, 'evidence_id' => $id],
                ['linked_by' => $actor->getKey(), 'summary' => $this->clean($summary), 'linked_at' => now()],
            );

            if ($link->wasRecentlyCreated) {
                ResearchGroupMilestoneEvent::query()->create([
                    'research_group_milestone_id' => $locked->getKey(), 'actor_id' => $actor->getKey(),
                    'event' => 'evidence_linked', 'from_status' => $locked->status, 'to_status' => $locked->status,
                    'new_values' => ['evidence_type' => $type, 'evidence_id' => $id], 'override_order' => false,
                    'ip_address' => $ipAddress, 'occurred_at' => now(),
                ]);
            }

            return $link;
        }, 3);
    }

    private function resolve(string $type, int $id): ?Model
    {
        return match ($type) {
            'document' => Document::query()->find($id),
            'document_review' => DocumentReview::query()->with('document:id,research_class_group_id')->find($id),
            'revision_request' => RevisionRequest::query()->find($id),
            'consultation_record' => ConsultationRecord::query()->find($id),
            default => null,
        };
    }

    private function groupId(string $type, Model $evidence): ?int
    {
        $id = match ($type) {
            'document' => $evidence->research_class_group_id,
            'document_review' => $evidence->document?->research_class_group_id,
            'revision_request', 'consultation_record' => $evidence->research_class_group_id,
            default => null,
        };

        return $id === null ? null : (int) $id;
    }

    private function clean(?string $value): ?string
    {
        $value = trim(strip_tags((string) $value));

        return $value === '' ? null : mb_substr($value, 0, 500);
    }
}
