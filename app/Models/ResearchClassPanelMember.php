<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'committee_id',
    'user_id',
    'panel_position',
])]
class ResearchClassPanelMember extends Model
{
    public function committee(): BelongsTo
    {
        return $this->belongsTo(ResearchClassPanelCommittee::class, 'committee_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
