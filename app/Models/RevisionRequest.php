<?php

namespace App\Models;

use App\Enums\RevisionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'research_project_id',
    'research_class_group_id',
    'document_id',
    'source_document_review_id',
    'submitted_document_id',
    'requested_by',
    'assigned_to',
    'source_type',
    'title',
    'instructions',
    'status',
    'due_at',
    'resolved_at',
    'invalidated_at',
    'invalidated_reason',
])]
class RevisionRequest extends Model
{
    public function researchClassGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchClassGroup::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function sourceReview(): BelongsTo
    {
        return $this->belongsTo(DocumentReview::class, 'source_document_review_id');
    }

    public function submittedDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'submitted_document_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function events(): HasMany
    {
        return $this->hasMany(RevisionRequestEvent::class);
    }

    protected function casts(): array
    {
        return [
            'status' => RevisionStatus::class,
            'due_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
            'invalidated_at' => 'immutable_datetime',
        ];
    }
}
