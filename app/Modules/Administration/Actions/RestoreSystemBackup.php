<?php

namespace App\Modules\Administration\Actions;

use App\Models\SystemBackup;
use App\Models\User;
use App\Modules\Administration\Services\SystemBackupArchiveInspector;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

final class RestoreSystemBackup
{
    public function __construct(
        private readonly CreateSystemBackup $createBackup,
        private readonly SystemBackupArchiveInspector $inspector,
        private readonly AuditLogWriter $auditLogs,
    ) {}

    public function handle(User $actor, SystemBackup $backup): SystemBackup
    {
        abort_unless($actor->can('settings.manage'), 403);
        if ($backup->status !== 'completed' || $backup->verification_status !== 'verified' || ! $backup->storage_path) {
            throw new RuntimeException('Only completed and verified backups can be restored.');
        }

        $lock = Cache::lock('system-backup:restoring', (int) config('backups.timeout_seconds', 600) + 300);
        if (! $lock->get()) {
            throw new RuntimeException('Another restore operation is already running.');
        }

        $workDirectory = storage_path('app/backup-work/restore-'.$backup->getKey().'-'.bin2hex(random_bytes(4)));
        $archivePath = $workDirectory.DIRECTORY_SEPARATOR.'backup.zip';
        $databaseDump = $workDirectory.DIRECTORY_SEPARATOR.'database.dump';
        $privateStage = $workDirectory.DIRECTORY_SEPARATOR.'private-files';
        $actorEmail = $actor->email;
        $backupSnapshot = $backup->toArray();
        $safetyBackup = null;
        $maintenanceEnabled = false;

        try {
            File::ensureDirectoryExists($workDirectory, 0700, true);
            $this->copyStoredArchive($backup, $archivePath);
            $inspection = $this->inspector->inspect($archivePath, $backup->size_bytes, $backup->sha256);
            $this->extractRestorePayload($archivePath, $databaseDump, $privateStage);

            // A current-state archive is mandatory before any destructive database operation.
            $safetyBackup = $this->createBackup->handle($actor, 'pre_restore');

            Artisan::call('down', ['--retry' => 60]);
            $maintenanceEnabled = true;
            $this->restoreDatabase($databaseDump);

            DB::purge((string) config('database.default'));
            DB::reconnect((string) config('database.default'));
            Artisan::call('migrate', ['--force' => true]);

            if (($inspection['manifest']['includes_private_files'] ?? false) === true) {
                $this->restorePrivateFiles($privateStage);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $restoredActor = User::query()->where('email', $actorEmail)->first();
            $restoredBackup = $this->registerArchiveAfterRestore($backupSnapshot, $restoredActor, [
                'restore_status' => 'restored',
                'restore_message' => 'Complete PostgreSQL data and included private files were restored successfully.',
                'restored_at' => now(),
                'restored_by' => $restoredActor?->getKey(),
                'manifest' => $inspection['manifest'],
            ]);
            $this->registerArchiveAfterRestore($safetyBackup->toArray(), $restoredActor);

            $this->auditLogs->write(
                actor: $restoredActor,
                event: 'system-backup.restored',
                description: 'The complete application database was restored from a verified backup.',
                requestContext: $restoredActor ? AuditRequestContext::fromRequest(request()) : AuditRequestContext::none(),
                auditable: $restoredBackup,
                subjectName: $restoredBackup->filename,
                newValues: [
                    'sha256' => $restoredBackup->sha256,
                    'database_scope' => $inspection['manifest']['database_scope'] ?? 'legacy_complete_dump',
                    'safety_backup' => $safetyBackup->filename,
                ],
                actorContext: $restoredActor ? 'administrator' : 'system',
                allowSystemActor: true,
            );

            return $restoredBackup->refresh();
        } catch (Throwable $exception) {
            $message = mb_substr(trim(strip_tags($exception->getMessage())), 0, 1000);
            $this->recordFailure($backupSnapshot, $actorEmail, $message);

            throw new RuntimeException($message, previous: $exception);
        } finally {
            if ($maintenanceEnabled) {
                Artisan::call('up');
            }
            File::deleteDirectory($workDirectory);
            $lock->release();
        }
    }

    private function copyStoredArchive(SystemBackup $backup, string $targetPath): void
    {
        $source = Storage::disk($backup->storage_disk)->readStream($backup->storage_path);
        $target = fopen($targetPath, 'wb');
        if (! is_resource($source) || ! is_resource($target)) {
            throw new RuntimeException('The selected backup archive could not be opened.');
        }

        try {
            stream_copy_to_stream($source, $target);
        } finally {
            fclose($source);
            fclose($target);
        }
    }

    private function extractRestorePayload(string $archivePath, string $databaseDump, string $privateStage): void
    {
        $zip = new ZipArchive;
        if ($zip->open($archivePath, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('The selected backup archive could not be opened.');
        }

        try {
            $this->copyZipEntry($zip, 'database/database.dump', $databaseDump);

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = str_replace('\\', '/', (string) $zip->getNameIndex($index));
                if (! str_starts_with($entry, 'private-files/') || str_ends_with($entry, '/')) {
                    continue;
                }

                $relative = substr($entry, strlen('private-files/'));
                $this->copyZipEntry($zip, $entry, $privateStage.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative));
            }
        } finally {
            $zip->close();
        }
    }

    private function copyZipEntry(ZipArchive $zip, string $entry, string $targetPath): void
    {
        $source = $zip->getStream($entry);
        if (! is_resource($source)) {
            throw new RuntimeException("The backup entry {$entry} could not be read.");
        }

        File::ensureDirectoryExists(dirname($targetPath), 0700, true);
        $target = fopen($targetPath, 'wb');
        if (! is_resource($target)) {
            fclose($source);
            throw new RuntimeException('A temporary restore file could not be created.');
        }

        try {
            stream_copy_to_stream($source, $target);
        } finally {
            fclose($source);
            fclose($target);
        }
    }

    private function restoreDatabase(string $databaseDump): void
    {
        $connection = config('database.connections.'.config('database.default'));
        if (($connection['driver'] ?? null) !== 'pgsql') {
            throw new RuntimeException('System restore requires the PostgreSQL database driver.');
        }

        $binary = (string) config('backups.pg_restore_binary', 'pg_restore');
        $input = null;
        if (trim((string) config('backups.pg_dump_docker_container')) === '' && $this->binaryIsAvailable($binary)) {
            $process = new Process([
                $binary,
                '--clean', '--if-exists', '--no-owner', '--no-privileges', '--single-transaction', '--exit-on-error',
                '--host='.(string) $connection['host'],
                '--port='.(string) $connection['port'],
                '--username='.(string) $connection['username'],
                '--dbname='.(string) $connection['database'],
                $databaseDump,
            ], null, [
                'PGPASSWORD' => (string) ($connection['password'] ?? ''),
                'PGSSLMODE' => (string) ($connection['sslmode'] ?? 'prefer'),
            ]);
        } else {
            $container = trim((string) config('backups.pg_dump_docker_container'));
            if ($container === '') {
                throw new RuntimeException('PostgreSQL pg_restore is unavailable. Install PostgreSQL client tools or configure the backup Docker container.');
            }

            $process = new Process([
                (string) config('backups.docker_binary', 'docker'), 'exec', '-i',
                '-e', 'PGPASSWORD',
                '-e', 'PGSSLMODE',
                $container,
                'pg_restore', '--clean', '--if-exists', '--no-owner', '--no-privileges', '--single-transaction', '--exit-on-error',
                '--host='.(string) $connection['host'],
                '--port='.(string) $connection['port'],
                '--username='.(string) $connection['username'],
                '--dbname='.(string) $connection['database'],
            ], null, [
                'PGPASSWORD' => (string) ($connection['password'] ?? ''),
                'PGSSLMODE' => (string) ($connection['sslmode'] ?? 'prefer'),
            ]);
            $input = fopen($databaseDump, 'rb');
            if (! is_resource($input)) {
                throw new RuntimeException('The PostgreSQL dump could not be opened for Docker restore.');
            }
            $process->setInput($input);
        }

        $process->setTimeout((int) config('backups.timeout_seconds', 600));
        try {
            $process->run();
        } finally {
            if (is_resource($input)) {
                fclose($input);
            }
        }
        if (! $process->isSuccessful()) {
            throw new RuntimeException('PostgreSQL restore failed. The pre-restore safety backup remains available. Review the server log before retrying.');
        }
    }

    private function restorePrivateFiles(string $privateStage): void
    {
        $root = storage_path('app/private');
        $backupDirectory = trim((string) config('backups.directory', 'system-backups'), '/');

        if (is_dir($root)) {
            foreach (File::allFiles($root) as $file) {
                $relative = str_replace('\\', '/', $file->getRelativePathname());
                if ($relative === $backupDirectory || str_starts_with($relative, $backupDirectory.'/')) {
                    continue;
                }
                File::delete($file->getPathname());
            }
        }

        if (! is_dir($privateStage)) {
            return;
        }
        foreach (File::allFiles($privateStage) as $file) {
            $target = $root.DIRECTORY_SEPARATOR.$file->getRelativePathname();
            File::ensureDirectoryExists(dirname($target), 0700, true);
            File::copy($file->getPathname(), $target);
        }
    }

    /** @param array<string, mixed> $snapshot @param array<string, mixed> $overrides */
    private function registerArchiveAfterRestore(array $snapshot, ?User $actor, array $overrides = []): SystemBackup
    {
        return SystemBackup::query()->updateOrCreate(
            ['storage_path' => $snapshot['storage_path']],
            [...collect($snapshot)->only([
                'filename', 'storage_disk', 'status', 'trigger', 'size_bytes', 'sha256', 'manifest',
                'verification_status', 'verification_message', 'verified_at', 'started_at', 'completed_at',
            ])->all(), 'triggered_by' => $actor?->getKey(), 'verified_by' => $actor?->getKey(), ...$overrides],
        );
    }

    /** @param array<string, mixed> $snapshot */
    private function recordFailure(array $snapshot, string $actorEmail, string $message): void
    {
        try {
            DB::purge((string) config('database.default'));
            DB::reconnect((string) config('database.default'));
            $actor = User::query()->where('email', $actorEmail)->first();
            $backup = $this->registerArchiveAfterRestore($snapshot, $actor, [
                'restore_status' => 'failed',
                'restore_message' => $message,
            ]);
            $this->auditLogs->write(
                actor: $actor,
                event: 'system-backup.restore-failed',
                description: 'A protected backup restore attempt failed.',
                requestContext: $actor ? AuditRequestContext::fromRequest(request()) : AuditRequestContext::none(),
                auditable: $backup,
                subjectName: $backup->filename,
                newValues: ['reason' => $message],
                actorContext: $actor ? 'administrator' : 'system',
                outcome: 'failed',
                allowSystemActor: true,
            );
        } catch (Throwable) {
            // Do not hide the original restore failure when failure logging is unavailable.
        }
    }

    private function binaryIsAvailable(string $binary): bool
    {
        try {
            $process = new Process([$binary, '--version']);
            $process->setTimeout(3);
            $process->run();

            return $process->isSuccessful();
        } catch (Throwable) {
            return false;
        }
    }
}
