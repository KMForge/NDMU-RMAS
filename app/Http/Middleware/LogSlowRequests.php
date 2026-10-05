<?php

namespace App\Http\Middleware;

use App\Support\SlowRequestMetrics;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogSlowRequests
{
    public function __construct(private readonly SlowRequestMetrics $metrics) {}

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $response = null;
        $this->metrics->start();

        try {
            return $response = $next($request);
        } finally {
            $metrics = $this->metrics->finish();
            $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
            if ($durationMs >= (int) config('performance.slow_request_ms', 750)) {
                Log::warning('Slow HTTP request', [
                    'route' => $request->route()?->getName() ?? 'unnamed',
                    'method' => $request->method(),
                    'status' => $response?->getStatusCode() ?? 500,
                    'duration_ms' => round($durationMs, 1),
                    'database_ms' => round($metrics['database_ms'], 1),
                    'query_count' => $metrics['query_count'],
                ]);
            }
        }
    }
}
