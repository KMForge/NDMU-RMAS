<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use App\Modules\Authorization\Services\ResolveUserDashboard;
use App\Modules\Registration\Actions\ActivateVerifiedStudent;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$options = getopt('', ['email:', 'password:']);
$email = (string) ($options['email'] ?? '');
$password = (string) ($options['password'] ?? '');

if ($email === '' || $password === '') {
    fwrite(STDERR, "Usage: php scripts/profile-login-performance.php --email=user@example.edu.ph --password=secret\n");
    exit(2);
}

$session = new Store('login-profiler', new ArraySessionHandler(120));
$session->start();
$request = Request::create('/login', 'POST', ['email' => $email, 'password' => $password]);
$request->setLaravelSession($session);
$app->instance('request', $request);

$currentRun = null;
$queries = [];
DB::listen(function (QueryExecuted $query) use (&$currentRun, &$queries): void {
    if ($currentRun !== null) {
        $queries[$currentRun][] = $query->time;
    }
});

$measure = static function (callable $callback): array {
    $start = hrtime(true);
    $result = $callback();

    return ['ms' => round((hrtime(true) - $start) / 1_000_000, 2), 'result' => $result];
};

$results = [];
foreach (['cold', 'warm'] as $run) {
    Auth::guard('web')->logout();
    $session->flush();
    $session->start();
    $currentRun = $run;
    $queries[$run] = [];

    $authentication = $measure(fn (): bool => Auth::attempt(['email' => $email, 'password' => $password], false));
    if (! $authentication['result']) {
        fwrite(STDERR, "The supplied credentials were not accepted.\n");
        exit(1);
    }

    /** @var User $user */
    $user = Auth::user();
    $activation = $measure(fn (): User => app(ActivateVerifiedStudent::class)->handle($user, AuditRequestContext::none()));
    $route = $measure(fn (): ?string => app(ResolveUserDashboard::class)->routeFor($activation['result']));

    DB::beginTransaction();
    try {
        $audit = $measure(fn () => app(AuditLogWriter::class)->write(
            actor: $activation['result'],
            event: 'auth.login.succeeded',
            description: 'Login profiler measurement.',
            requestContext: AuditRequestContext::none(),
            auditable: $activation['result'],
            subjectName: $activation['result']->name,
            subjectEmail: $activation['result']->email,
        ));
    } finally {
        DB::rollBack();
    }

    $results[$run] = [
        'authentication_ms' => $authentication['ms'],
        'activation_check_ms' => $activation['ms'],
        'dashboard_resolution_ms' => $route['ms'],
        'audit_insert_ms' => $audit['ms'],
        'total_measured_ms' => round($authentication['ms'] + $activation['ms'] + $route['ms'] + $audit['ms'], 2),
        'query_count' => count($queries[$run]),
        'query_ms' => round((float) array_sum($queries[$run]), 2),
        'destination_route' => $route['result'],
    ];
}

$currentRun = null;
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
