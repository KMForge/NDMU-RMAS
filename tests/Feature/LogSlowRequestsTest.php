<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class LogSlowRequestsTest extends TestCase
{
    public function test_slow_request_logs_only_aggregate_metrics(): void
    {
        config()->set('performance.slow_request_ms', 100);
        Log::spy();

        Route::get('/__slow-request-test', function () {
            DB::select('select 1');
            usleep(120_000);

            return response('ok');
        })->name('performance.test');

        $this->get('/__slow-request-test')->assertOk();

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Slow HTTP request', Mockery::on(fn (array $context): bool => $context['route'] === 'performance.test'
                && $context['status'] === 200
                && $context['query_count'] >= 1
                && $context['duration_ms'] >= 100
                && ! array_key_exists('sql', $context)
                && ! array_key_exists('user_id', $context)));
    }
}
