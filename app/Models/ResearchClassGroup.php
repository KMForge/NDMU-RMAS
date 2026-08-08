<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'research_class_id',
    'research_group_id',
    'creation_token',
    'name',
    'adviser_id',
    'created_by',
    'status',
    'disbanded_at',
])]
class ResearchClassGroup extends Model
{
    public function researchClass(): BelongsTo
    {
        return $this->belongsTo(ResearchClass::class);
    }

    public function researchGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchGroup::class);
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ResearchClassGroupMember::class);
    }

    public function adviserRequests(): HasMany
    {
        return $this->hasMany(ResearchClassGroupAdviserRequest::class);
    }

    public function adviserHistories(): HasMany
    {
        return $this->hasMany(ResearchClassGroupAdviserHistory::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->disbanded_at === null;
    }

    protected function casts(): array
    {
        return [
            'disbanded_at' => 'immutable_datetime',
        ];
    }
}
