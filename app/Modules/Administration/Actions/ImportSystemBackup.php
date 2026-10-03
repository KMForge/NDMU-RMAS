<?php

namespace App\Modules\Administration\Actions;

use App\Models\SystemBackup;
use App\Models\User;
use App\Modules\Administration\Services\BackupImportLimit;
use App\Modules\Administration\Services\SystemBackupArchiveInspector;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class ImportSystemBackup
{
    public function __construct(
        private readonly AuditLogWriter $auditLogs,
        private readonly SystemBackupArchiveInspector $inspector,
        private readonly BackupImportLimit $importLimit,
    ) {}

    public function handle(User $actor, string $sourcePath, string $originalName): SystemBackup
    {
        abort_unless($actor->can('settings.manage'), 403);

        $maxBytes = $this->importLimit->megabytes() * 1024 * 1024;
        $sourceSize = filesize($sourcePath);
        if ($sourceSize === false || $sourceSize > $maxBytes) {
            throw new RuntimeException('The backup archive exceeds the configured import size limit.');
        }
        $inspection = $this->inspector->inspect($sourcePath);

        $displayName = $this->safeFilename($originalName);
        $diskName = (string) config('backups.disk', 'local');
        $directory = trim((string) config('backups.directory', 'system-backups'), '/');
        $storagePath = $directory.'/imports/'.Str::uuid().'.zip';
        $stream = fopen($sourcePath, 'rb');
        if (! is_resource($stream)) {
            throw new RuntimeException('The uploaded backup could not be opened.');
        }

        try {
            if (! Storage::disk($diskName)->put($storagePath, $stream)) {
                throw new RuntimeException('The verified backup could not be saved to protected storage.');
            }
        } finally {
            fclose($stream);
        }

        try {
            return DB::transaction(function () use ($actor, $displayName, $diskName, $storagePath, $inspection): SystemBackup {
                $backup = SystemBackup::query()->create([
                    'filename' => $displayName,
                    'storage_disk' => $diskName,
                    'storage_path' => $storagePath,
                    'status' => 'completed',
                    'trigger' => 'imported',
                    'size_bytes' => $inspection['archive_size'],
                    'sha256' => $inspection['archive_sha256'],
                    'manifest' => $inspection['manifest'],
                    'verification_status' => 'verified',
                    'verification_message' => 'Imported archive and complete PostgreSQL dump passed integrity validation.',
                    'verified_at' => now(),
                    'verified_by' => $actor->getKey(),
                    'triggered_by' => $actor->getKey(),
                    'started_at' => now(),
                    'completed_at' => now(),
                ]);

                $this->auditLogs->write(
                    actor: $actor,
                    event: 'system-backup.imported',
                    description: 'A complete system backup archive was imported and verified.',
                    requestContext: AuditRequestContext::fromRequest(request()),
                    auditable: $backup,
                    subjectName: $displayName,
                    newValues: [
                        'size_bytes' => $inspection['archive_size'],
                        'sha256' => $inspection['archive_sha256'],
                        'database_dump_bytes' => $inspection['database_size'],
                        'private_file_count' => $inspection['private_file_count'],
                    ],
                    actorContext: 'administrator',
                );

                return $backup;
            });
        } catch (Throwable $exception) {
            Storage::disk($diskName)->delete($storagePath);

            throw $exception;
        }
    }

    private function safeFilename(string $originalName): string
    {
        $basename = basename(str_replace('\\', '/', $originalName));
        $stem = Str::of(pathinfo($basename, PATHINFO_FILENAME))->ascii()->slug('-')->limit(120, '')->toString();

        return ($stem !== '' ? $stem : 'ndmu-rmas-import').'.zip';
    }
}
