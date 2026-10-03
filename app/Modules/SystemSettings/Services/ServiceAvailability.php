<?php

namespace App\Modules\SystemSettings\Services;

use App\Models\SystemSetting;

final class ServiceAvailability
{
    /** @var list<string>|null */
    private ?array $disabledServices = null;

    public function studentRegistrationAvailable(): bool
    {
        return $this->available('student-registration');
    }

    public function available(string $service): bool
    {
        return ! in_array($service, $this->disabledServices(), true);
    }

    /** @return list<string> */
    public function disabledServices(): array
    {
        if ($this->disabledServices !== null) {
            return $this->disabledServices;
        }

        $settings = SystemSetting::query()->first(['student_registration_enabled', 'maintenance_services']);
        $disabled = array_values(array_intersect(
            array_keys(config('service-maintenance.services', [])),
            $settings?->maintenance_services ?? [],
        ));

        if ($settings !== null && ! $settings->student_registration_enabled) {
            $disabled[] = 'student-registration';
        }

        return $this->disabledServices = array_values(array_unique($disabled));
    }
}
