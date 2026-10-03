<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['system_name', 'support_email', 'student_registration_enabled', 'email_notifications_enabled', 'document_max_upload_mb', 'turnstile_enabled', 'defense_high_traffic_mode_enabled', 'maintenance_notice', 'maintenance_services', 'updated_by'])]
class SystemSetting extends Model
{
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function casts(): array
    {
        return [
            'student_registration_enabled' => 'boolean',
            'email_notifications_enabled' => 'boolean',
            'document_max_upload_mb' => 'integer',
            'turnstile_enabled' => 'boolean',
            'defense_high_traffic_mode_enabled' => 'boolean',
            'maintenance_services' => 'array',
        ];
    }
}
