<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'research_group_id',
    'title',
    'abstract',
    'keywords',
    'category',
    'status',
    'created_by',
    'approved_at',
    'completed_at',
    'archived_at',
])]
class ResearchProject extends Model
{
    public function researchGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchGroup::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'approved_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
        ];
    }
}
