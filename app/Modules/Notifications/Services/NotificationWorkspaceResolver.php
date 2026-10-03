<?php

namespace App\Modules\Notifications\Services;

class NotificationWorkspaceResolver
{
    /** @param array<string, mixed> $data */
    public function fromData(array $data): ?string
    {
        return $this->resolve(
            isset($data['route_name']) && is_string($data['route_name']) ? $data['route_name'] : null,
            isset($data['acting_as']) && is_string($data['acting_as']) ? $data['acting_as'] : null,
            isset($data['target_workspace']) && is_string($data['target_workspace']) ? $data['target_workspace'] : null,
        );
    }

    public function resolve(?string $routeName, ?string $actingAs = null, ?string $explicitWorkspace = null): ?string
    {
        $labels = config('notifications.workspace_labels', []);

        if (is_string($explicitWorkspace) && array_key_exists($explicitWorkspace, $labels)) {
            return $explicitWorkspace;
        }

        if (is_string($routeName)) {
            $routeWorkspace = str($routeName)->before('.')->toString();

            if (array_key_exists($routeWorkspace, $labels)) {
                return $routeWorkspace;
            }
        }

        if (! is_string($actingAs) || $actingAs === '') {
            return null;
        }

        $workspace = config('notifications.acting_as_workspaces.'.str($actingAs)->lower()->snake()->toString());

        return is_string($workspace) ? $workspace : null;
    }

    /** @param array<string, mixed> $data */
    public function labelForData(array $data): ?string
    {
        $workspace = $this->fromData($data);

        if ($workspace !== null) {
            $label = config("notifications.workspace_labels.{$workspace}");

            if (is_string($label) && $label !== '') {
                return $label;
            }
        }

        $actingAs = $data['acting_as'] ?? null;

        return is_string($actingAs) && $actingAs !== '' ? $actingAs : null;
    }
}
