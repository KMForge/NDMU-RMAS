<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'research_project_id',
    'adviser_assignment_id',
    'requested_by',
    'request_token',
    'preferred_at',
    'consultation_mode',
    'agenda',
    'status',
    'reviewed_by',
    'reviewed_at',
    'review_notes',
])]
class ConsultationRequest extends Model
{
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
            'preferred_at' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
        ];
    }
}
