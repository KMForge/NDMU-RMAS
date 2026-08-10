<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'document_id',
    'user_id',
    'research_class_group_id',
    'original_filename',
    'ip_address',
    'attempted_at',
    'upload_status',
    'failure_reason',
])]
class DocumentUploadAudit extends Model
{
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function researchClassGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchClassGroup::class);
    }

    protected function casts(): array
    {
        return [
            'attempted_at' => 'immutable_datetime',
        ];
    }
}
