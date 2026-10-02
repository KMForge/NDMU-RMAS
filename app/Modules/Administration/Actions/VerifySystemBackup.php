<?php

namespace App\Modules\Administration\Actions;

use App\Models\SystemBackup;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use ZipArchive;

final class VerifySystemBackup
{
    public function __construct(private readonly AuditLogWriter $auditLogs) {}

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

            if (($backup->size_bytes !== null && filesize($archive) !== $backup->size_bytes)
                || ! hash_equals((string) $backup->sha256, (string) hash_file('sha256', $archive))) {
                throw new RuntimeException('The backup archive checksum or size does not match its protected record.');
            }

            $zip = new ZipArchive;
            if ($zip->open($archive, ZipArchive::RDONLY) !== true) {
                throw new RuntimeException('The backup archive is not a readable ZIP file.');
            }
            try {
                $manifest = $zip->getFromName('manifest.json');
                $database = $zip->statName('database/database.dump');
                if (! is_string($manifest) || $database === false || (int) ($database['size'] ?? 0) === 0) {
                    throw new RuntimeException('The backup is missing its manifest or PostgreSQL database dump.');
                }
                $decoded = json_decode($manifest, true, flags: JSON_THROW_ON_ERROR);
                if (($decoded['application'] ?? null) !== config('app.name') || ($decoded['database_driver'] ?? null) !== 'pgsql') {
                    throw new RuntimeException('The backup manifest does not belong to this PostgreSQL application.');
                }
            } finally {
                $zip->close();
            }

            $backup->update([
                'verification_status' => 'verified',
                'verification_message' => 'Checksum, archive structure, manifest, and database dump validated.',
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
