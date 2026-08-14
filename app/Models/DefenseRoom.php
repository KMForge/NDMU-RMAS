<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'location_notes',
    'is_active',
])]
class DefenseRoom extends Model
{
    public function schedules(): HasMany
    {
        return $this->hasMany(DefenseSchedule::class, 'room_id');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
