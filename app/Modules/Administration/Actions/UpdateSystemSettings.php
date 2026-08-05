<?php

namespace App\Modules\Administration\Actions;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateSystemSettings
{
    /**
     * @param  array{system_name: string, support_email: string, student_registration_enabled: bool, email_notifications_enabled: bool, maintenance_notice: string|null, academic_year_id: int|null, academic_term_id: int|null}  $values
     */
    public function handle(User $actor, array $values): SystemSetting
    {
        abort_unless($actor->can('settings.manage'), 403);

        return DB::transaction(function () use ($actor, $values): SystemSetting {
            $settings = SystemSetting::query()->lockForUpdate()->firstOrFail();
            $oldValues = $settings->only([
                'system_name',
                'support_email',
                'student_registration_enabled',
                'email_notifications_enabled',
                'maintenance_notice',
            ]);

            $settings->update([
                'system_name' => $values['system_name'],
                'support_email' => $values['support_email'],
                'student_registration_enabled' => $values['student_registration_enabled'],
                'email_notifications_enabled' => $values['email_notifications_enabled'],
                'maintenance_notice' => $values['maintenance_notice'],
                'updated_by' => $actor->getKey(),
            ]);

            if ($values['academic_year_id'] !== null && $values['academic_term_id'] !== null) {
                AcademicYear::query()->where('is_current', true)->update(['is_current' => false]);
                AcademicYear::query()->whereKey($values['academic_year_id'])->update(['is_current' => true]);
                AcademicTerm::query()->where('is_current', true)->update(['is_current' => false]);
                AcademicTerm::query()->whereKey($values['academic_term_id'])->update(['is_current' => true]);
            }

            $this->audit($actor, $settings, $oldValues, $values);
            Cache::forget('system-settings');

            return $settings->refresh();
        });
    }

    /** @param array<string, mixed> $oldValues @param array<string, mixed> $newValues */
    private function audit(User $actor, SystemSetting $settings, array $oldValues, array $newValues): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        DB::table('audit_logs')->insert([
            'user_id' => $actor->getKey(),
            'event' => 'system-settings.updated',
            'auditable_type' => SystemSetting::class,
            'auditable_id' => $settings->getKey(),
            'description' => 'Protected system configuration was updated.',
            'old_values' => json_encode($oldValues, JSON_THROW_ON_ERROR),
            'new_values' => json_encode($newValues, JSON_THROW_ON_ERROR),
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 1000),
            'created_at' => now(),
        ]);
    }
}
