<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'research_class_id',
    'student_id',
    'status',
    'requested_at',
    'joined_at',
    'reviewed_by',
    'reviewed_at',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected function casts(): array
    {
        return [
            'requested_at' => 'immutable_datetime',
            'joined_at' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
        ];
    }
}
