<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'defense_evaluation_id',
    'round_student_id',
    'student_id',
    'communication_score',
    'organization_score',
    'effectiveness_score',
    'presentation_criterion_scores',
    'presentation_total',
])]
class DefenseEvaluationStudentScore extends Model
{
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(DefenseEvaluation::class, 'defense_evaluation_id');
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
            'communication_score' => 'decimal:2',
            'organization_score' => 'decimal:2',
            'effectiveness_score' => 'decimal:2',
            'presentation_criterion_scores' => 'array',
            'presentation_total' => 'decimal:2',
        ];
    }
}
