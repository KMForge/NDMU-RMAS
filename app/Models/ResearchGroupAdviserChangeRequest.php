<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'official_form_instance_id',
    'research_class_group_id',
    'previous_adviser_id',
    'requested_adviser_id',
    'requested_by',
    'reason',
    'supporting_explanation',
    'supporting_document_disk',
    'supporting_document_path',
    'supporting_document_original_name',
    'supporting_document_mime_type',
    'supporting_document_size',
    'supporting_document_sha256',
    'status',
    'leader_confirmed_at',
    'reviewed_by',
    'reviewer_remarks',
    'reviewed_at',
    'effective_at',
])]
class ResearchGroupAdviserChangeRequest extends Model
{
    public function officialFormInstance(): BelongsTo
    {
        return $this->belongsTo(OfficialFormInstance::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ResearchClassGroup::class, 'research_class_group_id');
    }

    public function previousAdviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'previous_adviser_id');
    }

    public function requestedAdviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_adviser_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected function casts(): array
    {
        return [
            'supporting_document_size' => 'integer',
            'leader_confirmed_at' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
            'effective_at' => 'immutable_datetime',
        ];
    }
}
