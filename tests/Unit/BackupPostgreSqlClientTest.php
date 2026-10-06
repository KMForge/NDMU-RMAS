<?php

namespace Tests\Unit;

use App\Modules\Administration\Actions\CreateSystemBackup;
use App\Modules\Administration\Actions\RestoreSystemBackup;
use ReflectionMethod;
use Tests\TestCase;

class BackupPostgreSqlClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->markTestSkipped('Exercises Windows executable selection using CMD fixtures.');
        }
        config([
            'database.default' => 'backup_probe',
            'database.connections.backup_probe' => [
                'driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => 5432,
                'username' => 'fixture', 'password' => 'fixture', 'database' => 'fixture',
            ],
            'backups.pg_dump_docker_container' => 'fixture-container',
            'backups.docker_binary' => base_path('tests/Fixtures/backup-docker-client.cmd'),
            'backups.pg_dump_binary' => base_path('tests/Fixtures/backup-native-client.cmd'),
            'backups.pg_restore_binary' => base_path('tests/Fixtures/backup-native-client.cmd'),
        ]);
    }

    public function test_configured_docker_dump_is_used_even_when_a_native_client_is_installed(): void
    {
        $target = tempnam(sys_get_temp_dir(), 'backup-client-');
        try {
            (new ReflectionMethod(CreateSystemBackup::class, 'dumpDatabase'))->invoke(app(CreateSystemBackup::class), $target);
            $this->assertStringContainsString('PGDMP-docker-client-fixture', file_get_contents($target));
        } finally {
            unlink($target);
        }
    }

    public function test_restore_uses_the_same_configured_docker_client_instead_of_the_native_client(): void
    {
        $target = tempnam(sys_get_temp_dir(), 'restore-client-');
        try {
            (new ReflectionMethod(RestoreSystemBackup::class, 'restoreDatabase'))->invoke(app(RestoreSystemBackup::class), $target);
            $this->assertTrue(true); // The native fixture exits 42; reaching here proves Docker was used.
        } finally {
            unlink($target);
        }
    }
}
