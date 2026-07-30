<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'revision_request_id',
    'actor_id',
    'document_id',
    'action',
    'from_status',
    'to_status',
    'notes',
    'ip_address',
    'metadata',
    'occurred_at',
])]
class RevisionRequestEvent extends Model
{
    public function revisionRequest(): BelongsTo
    {
        return $this->belongsTo(RevisionRequest::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
