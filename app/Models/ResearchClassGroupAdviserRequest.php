<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'research_class_group_id',
    'adviser_id',
    'requested_by',
    'status',
    'requested_at',
    'responded_at',
    'cancelled_at',
])]
class ResearchClassGroupAdviserRequest extends Model
{
    public function group(): BelongsTo
    {
        return $this->belongsTo(ResearchClassGroup::class, 'research_class_group_id');
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    protected function casts(): array
    {
        return [
            'requested_at' => 'immutable_datetime',
            'responded_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }
}
