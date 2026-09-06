<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'research_class_group_id',
    'defense_type',
    'chairperson_id',
    'is_custom',
    'created_by',
    'updated_by',
])]
class ResearchGroupPanelCommittee extends Model
{
    protected function casts(): array
    {
        return [
            'is_custom' => 'boolean',
        ];
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchClassGroup::class, 'research_class_group_id');
    }

    public function group(): BelongsTo
    {
        return $this->classGroup();
    }

    public function chairperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chairperson_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ResearchGroupPanelMember::class, 'committee_id');
    }

    public function member1(): ?ResearchGroupPanelMember
    {
        return $this->members->firstWhere('panel_position', 'member_1');
    }

    public function member2(): ?ResearchGroupPanelMember
    {
        return $this->members->firstWhere('panel_position', 'member_2');
    }
}
