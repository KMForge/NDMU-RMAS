<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'research_class_id',
    'student_id',
    'status',
    'joined_at',
])]
class ResearchClassEnrollment extends Model
{
    public function researchClass(): BelongsTo
    {
        return $this->belongsTo(ResearchClass::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    protected function casts(): array
    {
        return [
            'joined_at' => 'immutable_datetime',
        ];
    }
}
