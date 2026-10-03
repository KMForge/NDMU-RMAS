<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

$email = $argv[1] ?? null;
$workspace = $argv[2] ?? null;
$allowedWorkspaces = ['admin', 'facilitator', 'dean', 'adviser', 'panelist', 'student'];

if (! is_string($email) || ! is_string($workspace) || ! in_array($workspace, $allowedWorkspaces, true)) {
    fwrite(STDERR, "Usage: php scripts/profile-dashboard.php <email> <workspace>\n");
    exit(1);
}

$user = User::query()->where('email', $email)->firstOrFail();
$session = app('session')->driver();
$session->put('active_workspace', $workspace);

$request = Request::create("/{$workspace}/dashboard", 'GET');
$request->setLaravelSession($session);
$request->setUserResolver(static fn (): User => $user);
$app->instance('request', $request);
Auth::setUser($user);

$queries = [];
DB::listen(static function ($query) use (&$queries): void {
    $queries[] = [
        'sql' => $query->sql,
        'time_ms' => (float) $query->time,
    ];
});

$startedAt = hrtime(true);
$response = $app->make(HttpKernel::class)->handle($request);
$elapsedMs = round((hrtime(true) - $startedAt) / 1_000_000, 1);
$queryMs = round((float) collect($queries)->sum('time_ms'), 1);

$result = [
    'workspace' => $workspace,
    'status' => $response->getStatusCode(),
    'elapsed_ms' => $elapsedMs,
    'query_count' => count($queries),
    'query_ms' => $queryMs,
    'application_ms' => round($elapsedMs - $queryMs, 1),
    'response_kb' => round(strlen((string) $response->getContent()) / 1024, 1),
    'slowest_queries' => collect($queries)
        ->sortByDesc('time_ms')
        ->take(10)
        ->map(static fn (array $query): array => [
            'time_ms' => $query['time_ms'],
            'sql' => Str::limit(preg_replace('/\s+/', ' ', $query['sql']) ?? $query['sql'], 180),
        ])
        ->values()
        ->all(),
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
