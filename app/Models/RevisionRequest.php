<?php

namespace App\Models;

use App\Enums\RevisionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'research_project_id',
    'document_id',
    'requested_by',
    'assigned_to',
    'title',
    'instructions',
    'status',
    'due_at',
    'resolved_at',
])]
class RevisionRequest extends Model
{
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function submittedDocuments(): HasMany
    {
        return $this->hasMany(Document::class);
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
        ];
    }
}
