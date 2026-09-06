<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'research_class_id',
    'defense_type',
    'chairperson_id',
    'created_by',
    'updated_by',
])]
class ResearchClassPanelCommittee extends Model
{
    public function researchClass(): BelongsTo
    {
        return $this->belongsTo(ResearchClass::class);
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
        return $this->hasMany(ResearchClassPanelMember::class, 'committee_id');
    }

    public function member1(): ?ResearchClassPanelMember
    {
        return $this->members->firstWhere('panel_position', 'member_1');
    }

    public function member2(): ?ResearchClassPanelMember
    {
        return $this->members->firstWhere('panel_position', 'member_2');
    }
}
