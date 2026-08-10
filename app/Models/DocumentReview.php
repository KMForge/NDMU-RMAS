<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'document_id',
    'reviewer_id',
    'supersedes_review_id',
    'is_superseded',
    'decision',
    'review_notes',
    'correction_reason',
    'reviewed_at',
])]
class DocumentReview extends Model
{
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_review_id');
    }

    protected function casts(): array
    {
        return [
            'is_superseded' => 'boolean',
            'reviewed_at' => 'immutable_datetime',
        ];
    }
}
