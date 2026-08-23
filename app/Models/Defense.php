<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'research_class_group_id',
    'defense_type',
    'status',
    'current_schedule_id',
    'created_by',
    'completed_at',
    'completed_by',
])]
class Defense extends Model
{
    public function group(): BelongsTo
    {
        return $this->belongsTo(ResearchClassGroup::class, 'research_class_group_id');
    }

    public function currentSchedule(): BelongsTo
    {
        return $this->belongsTo(DefenseSchedule::class, 'current_schedule_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DefenseSchedule::class, 'defense_id');
    }

    public function panelAssignments(): HasMany
    {
        return $this->hasMany(DefensePanelAssignment::class, 'defense_id');
    }

    public function activePanelAssignments(): HasMany
    {
        return $this->hasMany(DefensePanelAssignment::class, 'defense_id')->whereNull('ended_at');
    }

    public function evaluationRounds(): HasMany
    {
        return $this->hasMany(DefenseEvaluationRound::class, 'defense_id');
    }

    public function titlePresentation(): HasOne
    {
        return $this->hasOne(TitlePresentation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }
}
