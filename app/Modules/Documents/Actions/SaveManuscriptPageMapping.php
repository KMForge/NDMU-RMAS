<?php

namespace App\Modules\Documents\Actions;

use App\Models\Document;
use App\Models\DocumentReviewAudit;
use App\Models\User;
use App\Modules\Documents\Support\DocumentReviewerAccess;
use Illuminate\Support\Facades\DB;

class SaveManuscriptPageMapping
{
    public function handle(User $reviewer, Document $document, array $mapping): void
    {
        DB::transaction(function () use ($reviewer, $document, $mapping): void {
            $locked = Document::query()->lockForUpdate()->findOrFail($document->id);
            abort_unless($locked->is_current, 422, 'Only the current paper version can be configured.');
            abort_unless($reviewer->can('review', $locked)
                && app(DocumentReviewerAccess::class)->canCommentAsAssignedPanelist($reviewer, $locked), 403);
            $locked->update(['page_mapping' => $mapping]);
            DocumentReviewAudit::query()->create([
                'document_id' => $locked->id,
                'reviewer_id' => $reviewer->id,
                'student_id' => $locked->user_id,
                'action' => 'page_mapping_updated',
                'decision' => $locked->status->value,
                'occurred_at' => now(),
                'metadata' => ['body_start' => $mapping['body_start'], 'preliminary_page_count' => count($mapping['preliminary_labels'])],
            ]);
        });
    }
}
