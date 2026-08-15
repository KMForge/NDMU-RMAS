<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'defense_evaluation_round_id',
    'student_id',
    'student_name_snapshot',
    'group_member_id',
    'created_at',
])]
class DefenseEvaluationRoundStudent extends Model
{
    public const UPDATED_AT = null;

    public function round(): BelongsTo
    {
        return $this->belongsTo(DefenseEvaluationRound::class, 'defense_evaluation_round_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function groupMember(): BelongsTo
    {
        return $this->belongsTo(ResearchClassGroupMember::class, 'group_member_id');
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
