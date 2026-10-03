<?php

namespace App\Modules\Administration\Actions;

use App\Models\SystemBackup;
use App\Models\User;
use App\Modules\Administration\Services\SystemBackupArchiveInspector;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class VerifySystemBackup
{
    public function __construct(
        private readonly AuditLogWriter $auditLogs,
        private readonly SystemBackupArchiveInspector $inspector,
    ) {}

    public function handle(User $actor, SystemBackup $backup): SystemBackup
    {
        abort_unless($actor->can('settings.manage'), 403);
        if ($backup->status !== 'completed' || ! $backup->storage_path) {
            throw new RuntimeException('Only completed backups can be verified.');
        }

        $directory = storage_path('app/backup-work/verify-'.$backup->getKey());
        $archive = $directory.DIRECTORY_SEPARATOR.$backup->filename;

        try {
            File::ensureDirectoryExists($directory, 0700, true);
            $source = Storage::disk($backup->storage_disk)->readStream($backup->storage_path);
            $target = fopen($archive, 'wb');
            if (! is_resource($source) || ! is_resource($target)) {
                throw new RuntimeException('The backup archive could not be opened for verification.');
            }
            stream_copy_to_stream($source, $target);
            fclose($source);
            fclose($target);

            $inspection = $this->inspector->inspect($archive, $backup->size_bytes, $backup->sha256);

            $backup->update([
                'verification_status' => 'verified',
                'verification_message' => 'Checksum, safe archive structure, manifest, and complete PostgreSQL database dump validated.',
                'manifest' => $inspection['manifest'],
                'verified_at' => now(),
                'verified_by' => $actor->getKey(),
            ]);
            $this->audit($actor, $backup, 'succeeded');

            return $backup->refresh();
        } catch (Throwable $exception) {
            $message = mb_substr(strip_tags($exception->getMessage()), 0, 1000);
            $backup->update([
                'verification_status' => 'failed',
                'verification_message' => $message,
                'verified_at' => now(),
                'verified_by' => $actor->getKey(),
            ]);
            $this->audit($actor, $backup, 'failed');

            throw new RuntimeException($message, previous: $exception);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    private function audit(User $actor, SystemBackup $backup, string $outcome): void
    {
        $this->auditLogs->write(
            actor: $actor,
            event: 'system-backup.verified',
            description: $outcome === 'succeeded' ? 'A system backup passed integrity verification.' : 'A system backup failed integrity verification.',
            requestContext: AuditRequestContext::fromRequest(request()),
            auditable: $backup,
            subjectName: $backup->filename,
            newValues: ['verification_status' => $backup->verification_status, 'verification_message' => $backup->verification_message],
            actorContext: 'administrator',
            outcome: $outcome,
        );
    }
}
