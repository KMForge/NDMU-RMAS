<?php

namespace App\Providers;

use App\APIs\Contracts\RealtimeProvider;
use App\APIs\Contracts\StorageProvider;
use App\Integrations\Supabase\SupabaseRealtimeService;
use App\Integrations\Supabase\SupabaseStorageService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StorageProvider::class, SupabaseStorageService::class);
        $this->app->singleton(RealtimeProvider::class, SupabaseRealtimeService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by(
            $request->user()?->getAuthIdentifier() ?: $request->ip(),
        ));

        RateLimiter::for('authentication', fn (Request $request) => Limit::perMinute(10)->by(
            strtolower((string) $request->input('email')).'|'.$request->ip(),
        ));
    }
}
