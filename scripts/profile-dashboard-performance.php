<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
View::share('errors', new ViewErrorBag);

$targets = [
    'student' => ['permission' => 'dashboards.student.view', 'path' => '/student/dashboard'],
    'adviser' => ['permission' => 'dashboards.adviser.view', 'path' => '/adviser/dashboard'],
    'panelist' => ['permission' => 'dashboards.panelist.view', 'path' => '/panelist/dashboard'],
    'facilitator' => ['permission' => 'dashboards.facilitator.view', 'path' => '/facilitator/dashboard'],
    'dean' => ['permission' => 'dashboards.dean.view', 'path' => '/dean/dashboard'],
    'admin' => ['permission' => 'dashboards.admin.view', 'path' => '/admin/dashboard'],
];

$current = null;
$queries = [];
DB::listen(function (QueryExecuted $query) use (&$current, &$queries): void {
    if ($current === null) {
        return;
    }

    $queries[$current][] = [
        'sql' => preg_replace('/\s+/', ' ', $query->sql),
        'time_ms' => (float) $query->time,
    ];
});

$results = [];
foreach ($targets as $workspace => $target) {
    $user = User::permission($target['permission'])->orderBy('id')->first();
    if ($user === null) {
        $results[$workspace] = ['error' => 'No authorized user found.'];

        continue;
    }

    foreach (['cold', 'warm'] as $run) {
        if ($run === 'cold') {
            clearstatcache();
        }

        $key = $workspace.'.'.$run;
        $queries[$key] = [];
        $current = $key;
        Auth::setUser($user);

        $request = Request::create($target['path'], 'GET', ['tab' => 'dashboard']);
        $request->setUserResolver(fn (): User => $user);
        $app->instance('request', $request);
        $startedAt = hrtime(true);
        $memoryBefore = memory_get_usage(true);

        try {
            $route = $app->make('router')->getRoutes()->match($request);
            $route->bind($request);
            $route->setContainer($app);
            $request->setRouteResolver(fn () => $route);
            $response = $app->make('router')::toResponse($request, $route->run());
            $content = $response->getContent();
            $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;
            $workspaceQueries = $queries[$key];
            $normalized = collect($workspaceQueries)
                ->groupBy(fn (array $entry): string => (string) $entry['sql'])
                ->map(fn ($entries, string $sql): array => [
                    'count' => $entries->count(),
                    'total_ms' => round((float) $entries->sum('time_ms'), 2),
                    'sql' => $sql,
                ])
                ->sortByDesc('count')
                ->values();

            $results[$workspace][$run] = [
                'status' => $response->getStatusCode(),
                'total_ms' => round($elapsedMs, 2),
                'query_count' => count($workspaceQueries),
                'query_ms' => round((float) collect($workspaceQueries)->sum('time_ms'), 2),
                'response_kb' => round(strlen((string) $content) / 1024, 2),
                'memory_delta_mb' => round((memory_get_peak_usage(true) - $memoryBefore) / 1048576, 2),
                'duplicate_query_groups' => $normalized->where('count', '>', 1)->count(),
                'top_duplicates' => $normalized->where('count', '>', 1)->take(5)->all(),
                'top_slowest_queries' => collect($workspaceQueries)
                    ->sortByDesc('time_ms')
                    ->take(5)
                    ->values()
                    ->all(),
            ];
        } catch (Throwable $exception) {
            $results[$workspace][$run] = [
                'error' => $exception::class.': '.$exception->getMessage(),
            ];
        } finally {
            $current = null;
        }
    }
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
