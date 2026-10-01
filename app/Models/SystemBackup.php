<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'filename', 'storage_disk', 'storage_path', 'status', 'trigger', 'size_bytes',
    'sha256', 'failure_message', 'triggered_by', 'started_at', 'completed_at',
])]
class SystemBackup extends Model
{
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
