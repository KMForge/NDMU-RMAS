<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'research_class_id',
    'defense_type',
    'room_id',
    'session_date',
    'starts_at',
    'ends_at',
    'status',
    'scheduled_by',
    'notes',
])]
class DefenseSession extends Model
{
    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function researchClass(): BelongsTo
    {
        return $this->belongsTo(ResearchClass::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(DefenseRoom::class, 'room_id');
    }

    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DefenseSchedule::class, 'defense_session_id')->orderBy('presentation_order');
    }
}
