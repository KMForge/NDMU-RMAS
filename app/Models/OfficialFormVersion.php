<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'official_form_instance_id',
    'version_number',
    'payload',
    'created_by',
    'supersedes_version_id',
    'is_current',
])]
class OfficialFormVersion extends Model
{
    public function instance(): BelongsTo
    {
        return $this->belongsTo(OfficialFormInstance::class, 'official_form_instance_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function supersedesVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_version_id');
    }

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'payload' => 'array',
            'is_current' => 'boolean',
        ];
    }
}
