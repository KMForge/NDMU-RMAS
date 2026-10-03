<?php

namespace App\Http\Middleware;

use App\Modules\SystemSettings\Services\ServiceAvailability;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class EnsureServiceIsAvailable
{
    public function __construct(private readonly ServiceAvailability $availability) {}

    public function handle(Request $request, Closure $next, ?string $service = null): Response
    {
        $service ??= $this->serviceFor($request);

        if ($service === null || $this->availability->available($service)) {
            return $next($request);
        }

        $definition = config("service-maintenance.services.{$service}");
        abort_unless(is_array($definition), 500, 'Unknown maintenance service.');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "{$definition['label']} is temporarily under maintenance.",
                'service' => $service,
            ], 503, ['Retry-After' => '300']);
        }

        return response()
            ->view('pages.service-maintenance', [
                'serviceName' => $definition['label'],
                'maintenanceMessage' => "{$definition['description']} This service is temporarily unavailable while maintenance is in progress. Other NDMU-RMAS services remain available.",
            ], 503)
            ->header('Retry-After', '300');
    }

    private function serviceFor(Request $request): ?string
    {
        $routeName = $request->route()?->getName() ?? '';
        $tab = (string) $request->query('tab', '');

        foreach (config('service-maintenance.services', []) as $key => $definition) {
            if (Str::is($definition['routes'] ?? [], $routeName)) {
                return $key;
            }

            if (str_ends_with($routeName, '.dashboard') && in_array($tab, $definition['tabs'] ?? [], true)) {
                return $key;
            }
        }

        return null;
    }
}
