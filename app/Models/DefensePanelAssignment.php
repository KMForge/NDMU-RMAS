<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'defense_id',
    'user_id',
    'panel_position',
    'assigned_by',
    'assigned_at',
    'ended_at',
    'change_reason',
])]
class DefensePanelAssignment extends Model
{
    public function defense(): BelongsTo
    {
        return $this->belongsTo(Defense::class, 'defense_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }
}
