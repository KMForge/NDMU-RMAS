<?php

namespace App\Modules\Revisions\Actions;

use App\Enums\RevisionStatus;
use App\Models\Document;
use App\Models\DocumentReview;
use App\Models\RevisionRequest;
use App\Models\RevisionRequestEvent;
use Illuminate\Support\Facades\DB;

class CreateRevisionCycleFromReview
{
    public function handle(Document $document, DocumentReview $review): RevisionRequest
    {
        return DB::transaction(function () use ($document, $review): RevisionRequest {
            $existing = RevisionRequest::query()
                ->where('source_document_review_id', $review->getKey())
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $groupId = $document->research_class_group_id;
            $stageLabel = $document->document_stage?->value ?? 'Document';
            $title = "Revision Request for {$document->original_filename} (v{$document->version_number})";
            $instructions = $review->review_notes ?? 'Please review the adviser findings and submit a revised document.';

            $cycle = RevisionRequest::query()->create([
                'research_class_group_id' => $groupId,
                'document_id' => $document->getKey(),
                'source_document_review_id' => $review->getKey(),
                'requested_by' => $review->reviewer_id,
                'assigned_to' => null,
                'source_type' => 'document_review',
                'title' => $title,
                'instructions' => $instructions,
                'status' => RevisionStatus::Open,
            ]);

            RevisionRequestEvent::query()->create([
                'revision_request_id' => $cycle->getKey(),
                'actor_id' => $review->reviewer_id,
                'document_id' => $document->getKey(),
                'action' => 'created',
                'from_status' => null,
                'to_status' => RevisionStatus::Open->value,
                'notes' => 'Revision cycle created from adviser review decision.',
                'occurred_at' => now(),
                'metadata' => [
                    'source_review_id' => $review->getKey(),
                    'document_stage' => $document->document_stage?->value,
                    'version_number' => $document->version_number,
                ],
            ]);

            return $cycle;
        }, 3);
    }
}
