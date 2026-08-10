<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'consultation_record_id',
    'student_id',
    'attended',
])]
class ConsultationAttendance extends Model
{
    public function record(): BelongsTo
    {
        return $this->belongsTo(ConsultationRecord::class, 'consultation_record_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    protected function casts(): array
    {
        return [
            'attended' => 'boolean',
        ];
    }
}
