<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'research_project_id',
    'previous_title',
    'revised_title',
    'reason',
    'changed_by',
    'effective_at',
])]
class ResearchProjectTitleHistory extends Model
{
    public function researchProject(): BelongsTo
    {
        return $this->belongsTo(ResearchProject::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    protected function casts(): array
    {
        return ['effective_at' => 'immutable_datetime'];
    }
}
