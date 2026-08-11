<?php

namespace App\Models;

use App\Enums\ResearchMilestoneStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'research_class_group_id', 'milestone_definition_id', 'status', 'due_at', 'started_at',
    'started_by', 'completed_at', 'completed_by', 'not_applicable_reason', 'remarks', 'updated_by',
])]
class ResearchGroupMilestone extends Model
{
    public function group(): BelongsTo
    {
        return $this->belongsTo(ResearchClassGroup::class, 'research_class_group_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(MilestoneDefinition::class, 'milestone_definition_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ResearchGroupMilestoneEvent::class);
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(MilestoneEvidence::class);
    }

    public function isOverdue(): bool
    {
        return $this->due_at?->isPast() === true
            && in_array($this->status, [ResearchMilestoneStatus::Pending, ResearchMilestoneStatus::InProgress], true);
    }

    protected function casts(): array
    {
        return [
            'status' => ResearchMilestoneStatus::class,
            'due_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
