<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'research_class_group_id',
    'adviser_id',
    'assigned_by',
    'assigned_at',
    'ended_at',
    'ended_by',
])]
class ResearchClassGroupAdviserHistory extends Model
{
    public function group(): BelongsTo
    {
        return $this->belongsTo(ResearchClassGroup::class, 'research_class_group_id');
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function ender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    protected function casts(): array
    {
        return [
            'assigned_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
        ];
    }
}
