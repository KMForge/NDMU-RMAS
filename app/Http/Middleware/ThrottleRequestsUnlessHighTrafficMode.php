<?php

namespace App\Http\Middleware;

use App\Modules\SystemSettings\Services\RateLimitSettings;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Routing\Middleware\ThrottleRequests;

class ThrottleRequestsUnlessHighTrafficMode extends ThrottleRequests
{
    public function __construct(
        RateLimiter $limiter,
        private readonly RateLimitSettings $settings,
    ) {
        parent::__construct($limiter);
    }

    /**
     * Bypass route throttles for signed-in users only while an administrator has
     * explicitly enabled high-traffic mode. Guest-facing security limits remain.
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        if ($request->user() !== null && $this->settings->highTrafficModeEnabled()) {
            return $next($request);
        }

        if (func_num_args() === 3) {
            return parent::handle($request, $next, $maxAttempts);
        }

        return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
    }
}
