<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'defense_evaluation_round_id',
    'round_panelist_id',
    'panelist_user_id',
    'status',
    'research_quality_score',
    'originality_score',
    'relevance_score',
    'research_paper_total',
    'general_comments',
    'recommendations',
    'submitted_at',
])]
class DefenseEvaluation extends Model
{
    public function round(): BelongsTo
    {
        return $this->belongsTo(DefenseEvaluationRound::class, 'defense_evaluation_round_id');
    }

    public function roundPanelist(): BelongsTo
    {
        return $this->belongsTo(DefenseEvaluationRoundPanelist::class, 'round_panelist_id');
    }

    public function panelist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'panelist_user_id');
    }

    public function studentScores(): HasMany
    {
        return $this->hasMany(DefenseEvaluationStudentScore::class, 'defense_evaluation_id');
    }

    public function officialFormInstance(): HasOne
    {
        return $this->hasOne(OfficialFormInstance::class, 'defense_evaluation_id');
    }

    protected function casts(): array
    {
        return [
            'research_quality_score' => 'decimal:2',
            'originality_score' => 'decimal:2',
            'relevance_score' => 'decimal:2',
            'research_paper_total' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }
}
