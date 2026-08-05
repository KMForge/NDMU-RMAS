<?php

namespace App\Providers;

use App\APIs\Contracts\RealtimeProvider;
use App\APIs\Contracts\StorageProvider;
use App\Integrations\Supabase\SupabaseRealtimeService;
use App\Integrations\Supabase\SupabaseStorageService;
use App\Modules\Documents\Actions\RecordDocumentUploadAttempt;
use App\Support\PortableSchemaBlueprint;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Blueprint::class, function ($app, array $parameters): PortableSchemaBlueprint {
            return new PortableSchemaBlueprint(
                $parameters['connection'],
                $parameters['table'],
                $parameters['callback'] ?? null,
            );
        });

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

        RateLimiter::for('password-reset-links', fn (Request $request) => Limit::perHour(5)->by(
            'password-reset-link|'.strtolower((string) $request->input('email')).'|'.$request->ip(),
        ));

        RateLimiter::for('password-resets', fn (Request $request) => Limit::perMinute(10)->by(
            'password-reset|'.strtolower((string) $request->input('email')).'|'.$request->ip(),
        ));

        RateLimiter::for('document-uploads', function (Request $request): Limit {
            return Limit::perMinute(5)
                ->by('document-upload|'.($request->user()?->getAuthIdentifier() ?: $request->ip()))
                ->response(function (Request $request, array $headers) {
                    $message = 'Too many upload attempts. Please wait before trying again.';
                    $file = $request->file('document');

                    app(RecordDocumentUploadAttempt::class)->failure(
                        $request->user(),
                        $file instanceof UploadedFile ? $file : null,
                        $request->ip(),
                        $message,
                    );

                    if ($request->expectsJson()) {
                        return response()->json(['message' => $message], 429, $headers);
                    }

                    $route = $request->routeIs('adviser.*')
                        ? 'adviser.dashboard'
                        : 'student.dashboard';
                    $parameters = $route === 'adviser.dashboard'
                        ? ['tab' => 'repository']
                        : [];

                    return to_route($route, $parameters)
                        ->withErrors(['document' => $message])
                        ->with('document_error', $message);
                });
        });

        RateLimiter::for('document-reviews', fn (Request $request) => Limit::perMinute(60)->by(
            'document-review|'.($request->user()?->getAuthIdentifier() ?: $request->ip()),
        ));

        RateLimiter::for('signature-enrollment', fn (Request $request) => Limit::perMinute(10)->by(
            'signature-enrollment|'.($request->user()?->getAuthIdentifier() ?: $request->ip()),
        ));

        RateLimiter::for('consultation-bookings', fn (Request $request) => Limit::perHour(5)->by(
            'consultation-booking|'.($request->user()?->getAuthIdentifier() ?: $request->ip()),
        ));

        RateLimiter::for('consultation-decisions', fn (Request $request) => Limit::perMinute(30)->by(
            'consultation-decision|'.($request->user()?->getAuthIdentifier() ?: $request->ip()),
        ));

        RateLimiter::for('class-creation', fn (Request $request) => Limit::perHour(10)->by(
            'class-creation|'.($request->user()?->getAuthIdentifier() ?: $request->ip()),
        ));

        RateLimiter::for('class-joining', fn (Request $request) => Limit::perMinute(10)->by(
            'class-joining|'.($request->user()?->getAuthIdentifier() ?: $request->ip()),
        ));

        RateLimiter::for('class-join-decisions', fn (Request $request) => Limit::perMinute(30)->by(
            'class-join-decision|'.($request->user()?->getAuthIdentifier() ?: $request->ip()),
        ));

        RateLimiter::for('revision-actions', fn (Request $request) => Limit::perMinute(30)->by(
            'revision-action|'.($request->user()?->getAuthIdentifier() ?: $request->ip()),
        ));
    }
}
