<?php

namespace App\Modules\Administration\Actions;

use App\Models\SystemSetting;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class SetServiceAvailability
{
    public function __construct(private readonly AuditLogWriter $auditLogs) {}

    public function handle(User $actor, string $service, bool $available): SystemSetting
    {
        abort_unless($actor->can('settings.manage'), 403);
        $definition = config("service-maintenance.services.{$service}");
        if (! is_array($definition)) {
            throw new InvalidArgumentException("Unknown maintenance service [{$service}].");
        }

        return DB::transaction(function () use ($actor, $service, $available, $definition): SystemSetting {
            $settings = SystemSetting::query()->lockForUpdate()->firstOrFail();
            $disabled = $settings->maintenance_services ?? [];
            $previous = ! in_array($service, $disabled, true);

            if ($previous === $available) {
                return $settings;
            }

            $disabled = $available
                ? array_values(array_diff($disabled, [$service]))
                : array_values(array_unique([...$disabled, $service]));

            $settings->update([
                'maintenance_services' => $disabled,
                ...($service === 'student-registration' ? ['student_registration_enabled' => $available] : []),
                'maintenance_notice' => null,
                'updated_by' => $actor->getKey(),
            ]);

            $this->auditLogs->write(
                actor: $actor,
                event: 'system-service.availability-updated',
                description: $available
                    ? "{$definition['label']} was restored to normal operation."
                    : "{$definition['label']} was placed under maintenance.",
                requestContext: AuditRequestContext::fromRequest(request()),
                auditable: $settings,
                subjectName: $definition['label'],
                oldValues: ['available' => $previous],
                newValues: ['available' => $available],
                actorContext: 'administrator',
            );

            return $settings->refresh();
        });
    }
}
