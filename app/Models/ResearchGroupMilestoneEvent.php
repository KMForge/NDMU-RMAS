<?php

namespace App\Models;

use App\Enums\ResearchMilestoneStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'research_group_milestone_id', 'actor_id', 'event', 'from_status', 'to_status', 'reason',
    'old_values', 'new_values', 'override_order', 'ip_address', 'occurred_at',
])]
class ResearchGroupMilestoneEvent extends Model
{
    public const UPDATED_AT = null;

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ResearchGroupMilestone::class, 'research_group_milestone_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return [
            'from_status' => ResearchMilestoneStatus::class,
            'to_status' => ResearchMilestoneStatus::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'override_order' => 'boolean',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
