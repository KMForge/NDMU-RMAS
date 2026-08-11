<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'description', 'sequence', 'weight', 'is_active'])]
class MilestoneDefinition extends Model
{
    public function groupMilestones(): HasMany
    {
        return $this->hasMany(ResearchGroupMilestone::class);
    }

    protected function casts(): array
    {
        return ['sequence' => 'integer', 'weight' => 'decimal:4', 'is_active' => 'boolean'];
    }
}
