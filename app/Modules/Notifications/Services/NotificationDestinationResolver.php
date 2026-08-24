<?php

namespace App\Modules\Notifications\Services;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;

class NotificationDestinationResolver
{
    public function resolve(DatabaseNotification $notification): string
    {
        $routeName = $notification->data['route_name'] ?? null;
        $parameters = $notification->data['route_parameters'] ?? [];
        $allowed = config('notifications.safe_destinations', []);

        if (! is_string($routeName)
            || ! array_key_exists($routeName, $allowed)
            || ! Route::has($routeName)
            || ! is_array($parameters)) {
            return route('notifications.index');
        }

        $allowedParameters = $allowed[$routeName];

        if (! is_array($allowedParameters)) {
            return route('notifications.index');
        }

        $safeParameters = collect($parameters)
            ->only($allowedParameters)
            ->filter(fn (mixed $value): bool => is_scalar($value) || $value === null)
            ->all();

        try {
            return route($routeName, $safeParameters);
        } catch (\Throwable) {
            return route('notifications.index');
        }
    }
}
