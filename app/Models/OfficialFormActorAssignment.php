<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'official_form_instance_id',
    'user_id',
    'actor_type',
    'assigned_by',
    'assigned_at',
    'status',
])]
class OfficialFormActorAssignment extends Model
{
    public function instance(): BelongsTo
    {
        return $this->belongsTo(OfficialFormInstance::class, 'official_form_instance_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }
}
