<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'defense_evaluation_round_id',
    'research_paper_average',
    'status',
    'finalized_at',
    'signed_at',
    'released_at',
])]
class DefenseEvaluationSummary extends Model
{
    public function round(): BelongsTo
    {
        return $this->belongsTo(DefenseEvaluationRound::class, 'defense_evaluation_round_id');
    }

    public function studentSummaries(): HasMany
    {
        return $this->hasMany(DefenseEvaluationStudentSummary::class, 'defense_evaluation_summary_id');
    }

    protected function casts(): array
    {
        return [
            'research_paper_average' => 'decimal:2',
            'finalized_at' => 'datetime',
            'signed_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }
}
