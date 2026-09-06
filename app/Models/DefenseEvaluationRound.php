<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'defense_id',
    'defense_schedule_id',
    'research_class_group_id',
    'program_code',
    'status',
    'summary_signer_user_id',
    'opened_by',
    'opened_at',
    'all_submitted_at',
    'finalized_at',
    'released_at',
    'released_by',
])]
class DefenseEvaluationRound extends Model
{
    public function defense(): BelongsTo
    {
        return $this->belongsTo(Defense::class, 'defense_id');
    }

    public function defenseSchedule(): BelongsTo
    {
        return $this->belongsTo(DefenseSchedule::class, 'defense_schedule_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ResearchClassGroup::class, 'research_class_group_id');
    }

    public function summarySigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'summary_signer_user_id');
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function roundPanelists(): HasMany
    {
        return $this->hasMany(DefenseEvaluationRoundPanelist::class, 'defense_evaluation_round_id');
    }

    public function roundStudents(): HasMany
    {
        return $this->hasMany(DefenseEvaluationRoundStudent::class, 'defense_evaluation_round_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(DefenseEvaluation::class, 'defense_evaluation_round_id');
    }

    public function summary(): HasOne
    {
        return $this->hasOne(DefenseEvaluationSummary::class, 'defense_evaluation_round_id');
    }

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'all_submitted_at' => 'datetime',
            'finalized_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }
}
