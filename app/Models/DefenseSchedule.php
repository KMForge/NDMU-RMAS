<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'defense_id',
    'room_id',
    'starts_at',
    'ends_at',
    'status',
    'scheduled_by',
    'reason',
    'supersedes_schedule_id',
])]
class DefenseSchedule extends Model
{
    public function defense(): BelongsTo
    {
        return $this->belongsTo(Defense::class, 'defense_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(DefenseRoom::class, 'room_id');
    }

    public function scheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function supersedesSchedule(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_schedule_id');
    }

    public function officialFormInstances(): HasMany
    {
        return $this->hasMany(OfficialFormInstance::class, 'source_id')->where('source_type', self::class);
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
