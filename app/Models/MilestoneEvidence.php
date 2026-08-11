<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['research_group_milestone_id', 'evidence_type', 'evidence_id', 'linked_by', 'summary', 'linked_at'])]
class MilestoneEvidence extends Model
{
    protected $table = 'milestone_evidences';

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ResearchGroupMilestone::class, 'research_group_milestone_id');
    }

    public function linker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }

    protected function casts(): array
    {
        return ['evidence_id' => 'integer', 'linked_at' => 'immutable_datetime'];
    }
}
