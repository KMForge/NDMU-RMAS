<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'consultation_request_id',
    'proposed_by',
    'proposed_start_at',
    'duration_minutes',
    'reason',
    'status',
    'responded_by',
    'responded_at',
])]
class ConsultationScheduleProposal extends Model
{
    public function request(): BelongsTo
    {
        return $this->belongsTo(ConsultationRequest::class, 'consultation_request_id');
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    protected function casts(): array
    {
        return [
            'proposed_start_at' => 'immutable_datetime',
            'responded_at' => 'immutable_datetime',
        ];
    }
}
