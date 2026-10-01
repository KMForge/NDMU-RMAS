<?php

namespace App\Modules\Administration\Actions;

use App\Models\SystemBackup;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

final class CreateSystemBackup
{
    public function __construct(private readonly AuditLogWriter $auditLogs) {}

    public function handle(?User $actor, string $trigger = 'manual'): SystemBackup
    {
        if ($actor !== null) {
            abort_unless($actor->can('settings.manage'), 403);
        }

        if (! in_array($trigger, ['manual', 'scheduled'], true)) {
            throw new RuntimeException('Unsupported backup trigger.');
        }

        $lock = Cache::lock('system-backup:running', (int) config('backups.timeout_seconds', 600) + 60);
        if (! $lock->get()) {
            throw new RuntimeException('Another system backup is already running.');
        }

        $stamp = now()->format('Y-m-d-His');
        $backup = SystemBackup::query()->create([
            'filename' => "ndmu-rmas-{$stamp}.zip",
            'storage_disk' => (string) config('backups.disk', 'local'),
            'status' => 'running',
            'trigger' => $trigger,
            'triggered_by' => $actor?->getKey(),
            'started_at' => now(),
        ]);

        $temporaryDirectory = storage_path("app/backup-work/{$backup->getKey()}");

        try {
            File::ensureDirectoryExists($temporaryDirectory, 0700, true);
            $databaseDump = $temporaryDirectory.DIRECTORY_SEPARATOR.'database.dump';
            $archive = $temporaryDirectory.DIRECTORY_SEPARATOR.$backup->filename;

            $this->dumpDatabase($databaseDump);
            $this->buildArchive($archive, $databaseDump, $backup);

            $path = trim((string) config('backups.directory', 'system-backups'), '/').'/'.$backup->filename;
            $stream = fopen($archive, 'rb');
            if ($stream === false || ! Storage::disk($backup->storage_disk)->put($path, $stream)) {
                throw new RuntimeException('The completed backup archive could not be stored.');
            }
            if (is_resource($stream)) {
                fclose($stream);
            }

            $backup->update([
                'storage_path' => $path,
                'status' => 'completed',
                'size_bytes' => filesize($archive) ?: null,
                'sha256' => hash_file('sha256', $archive),
                'completed_at' => now(),
            ]);

            $this->auditLogs->write(
                actor: $actor,
                event: 'system-backup.created',
                description: 'A protected system backup was created.',
                requestContext: $actor ? AuditRequestContext::fromRequest(request()) : AuditRequestContext::none(),
                auditable: $backup,
                subjectName: $backup->filename,
                newValues: ['trigger' => $trigger, 'size_bytes' => $backup->size_bytes, 'sha256' => $backup->sha256],
                actorContext: $actor ? 'administrator' : 'system',
                allowSystemActor: true,
            );

            return $backup->refresh();
        } catch (Throwable $exception) {
            $message = $this->safeFailureMessage($exception);
            $backup->update([
                'status' => 'failed',
                'failure_message' => $message,
                'completed_at' => now(),
            ]);

            $this->auditLogs->write(
                actor: $actor,
                event: 'system-backup.failed',
                description: 'A system backup attempt failed.',
                requestContext: $actor ? AuditRequestContext::fromRequest(request()) : AuditRequestContext::none(),
                auditable: $backup,
                subjectName: $backup->filename,
                newValues: ['trigger' => $trigger, 'reason' => $message],
                actorContext: $actor ? 'administrator' : 'system',
                outcome: 'failed',
                allowSystemActor: true,
            );

            throw new RuntimeException($message, previous: $exception);
        } finally {
            File::deleteDirectory($temporaryDirectory);
            $lock->release();
        }
    }

    private function dumpDatabase(string $target): void
    {
        $connection = config('database.connections.'.config('database.default'));
        if (($connection['driver'] ?? null) !== 'pgsql') {
            throw new RuntimeException('System backups currently require the PostgreSQL database driver.');
        }

        $binary = (string) config('backups.pg_dump_binary', 'pg_dump');
        $command = [
            $binary,
            '--format=custom',
            '--no-owner',
            '--no-privileges',
            '--host='.(string) $connection['host'],
            '--port='.(string) $connection['port'],
            '--username='.(string) $connection['username'],
            '--file='.$target,
            (string) $connection['database'],
        ];

        $process = new Process($command, null, [
            'PGPASSWORD' => (string) ($connection['password'] ?? ''),
            'PGSSLMODE' => (string) ($connection['sslmode'] ?? 'prefer'),
        ]);
        $process->setTimeout((int) config('backups.timeout_seconds', 600));

        try {
            $process->mustRun();
        } catch (Throwable $exception) {
            if (str_contains(mb_strtolower($exception->getMessage()), 'not found') || str_contains(mb_strtolower($exception->getMessage()), 'executable')) {
                throw new RuntimeException('PostgreSQL pg_dump is unavailable. Install PostgreSQL client tools or set BACKUP_PG_DUMP_BINARY to the full executable path.');
            }

            throw new RuntimeException('PostgreSQL could not create the database dump. Check the database connection and pg_dump compatibility.');
        }

        if (! is_file($target) || filesize($target) === 0) {
            throw new RuntimeException('PostgreSQL returned an empty database dump.');
        }
    }

    private function buildArchive(string $archivePath, string $databaseDump, SystemBackup $backup): void
    {
        $zip = new ZipArchive;
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('The backup ZIP archive could not be created.');
        }

        try {
            $zip->addFile($databaseDump, 'database/database.dump');
            $zip->addFromString('manifest.json', json_encode([
                'application' => config('app.name'),
                'created_at' => now()->toIso8601String(),
                'database_driver' => 'pgsql',
                'laravel_version' => app()->version(),
                'includes_private_files' => (bool) config('backups.include_private_files', true),
                'backup_record_id' => $backup->getKey(),
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            if ((bool) config('backups.include_private_files', true)) {
                $root = storage_path('app/private');
                $backupDirectory = trim((string) config('backups.directory', 'system-backups'), '/');
                if (is_dir($root)) {
                    foreach (File::allFiles($root) as $file) {
                        $relative = str_replace('\\', '/', $file->getRelativePathname());
                        if ($relative === $backupDirectory || str_starts_with($relative, $backupDirectory.'/')) {
                            continue;
                        }
                        $zip->addFile($file->getPathname(), 'private-files/'.$relative);
                    }
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function safeFailureMessage(Throwable $exception): string
    {
        return mb_substr(trim(strip_tags($exception->getMessage())), 0, 1000);
    }
}
