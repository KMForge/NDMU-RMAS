<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['enabled', 'frequency', 'run_time', 'retention_count', 'max_import_mb', 'last_scheduled_for', 'updated_by'])]
class SystemBackupSetting extends Model
{
    protected $table = 'system_backup_settings';

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'retention_count' => 'integer',
            'max_import_mb' => 'integer',
            'last_scheduled_for' => 'immutable_datetime',
        ];
    }
}
