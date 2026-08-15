<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'defense_evaluation_summary_id',
    'round_student_id',
    'student_id',
    'presentation_average',
    'created_at',
])]
class DefenseEvaluationStudentSummary extends Model
{
    public const UPDATED_AT = null;

    public function summary(): BelongsTo
    {
        return $this->belongsTo(DefenseEvaluationSummary::class, 'defense_evaluation_summary_id');
    }

    public function roundStudent(): BelongsTo
    {
        return $this->belongsTo(DefenseEvaluationRoundStudent::class, 'round_student_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    protected function casts(): array
    {
        return [
            'presentation_average' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }
}
