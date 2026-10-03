<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'filename', 'storage_disk', 'storage_path', 'status', 'trigger', 'size_bytes',
    'sha256', 'manifest', 'verification_status', 'verification_message', 'verified_at', 'verified_by',
    'restore_status', 'restore_message', 'restored_at', 'restored_by',
    'failure_message', 'triggered_by', 'started_at', 'completed_at',
])]
class SystemBackup extends Model
{
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function restoredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by');
    }

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'manifest' => 'array',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'verified_at' => 'immutable_datetime',
            'restored_at' => 'immutable_datetime',
        ];
    }
}
