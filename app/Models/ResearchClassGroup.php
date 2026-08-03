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
}
