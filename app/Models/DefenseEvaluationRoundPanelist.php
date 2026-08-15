<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'defense_evaluation_round_id',
    'defense_panel_assignment_id',
    'panelist_user_id',
    'position',
    'created_at',
])]
class DefenseEvaluationRoundPanelist extends Model
{
    public const UPDATED_AT = null;

    public function round(): BelongsTo
    {
        return $this->belongsTo(DefenseEvaluationRound::class, 'defense_evaluation_round_id');
    }

    public function panelAssignment(): BelongsTo
    {
        return $this->belongsTo(DefensePanelAssignment::class, 'defense_panel_assignment_id');
    }

    public function panelist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'panelist_user_id');
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
