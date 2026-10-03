<?php

namespace App\Modules\Administration\Actions;

use App\Models\SystemBackup;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class DeleteSystemBackup
{
    public function __construct(private readonly AuditLogWriter $auditLogs) {}

    public function handle(User $actor, SystemBackup $backup, string $confirmation): void
    {
        abort_unless($actor->can('settings.manage'), 403);

        $requiredConfirmation = 'DELETE '.$backup->filename;
        if (! hash_equals($requiredConfirmation, trim($confirmation))) {
            throw ValidationException::withMessages([
                'backupDeleteConfirmation' => "Type {$requiredConfirmation} exactly to delete this backup.",
            ]);
        }

        if ($backup->status === 'running') {
            throw new RuntimeException('A running backup cannot be deleted.');
        }

        if ($backup->storage_path !== null) {
            Storage::disk($backup->storage_disk)->delete($backup->storage_path);
        }

        $name = $backup->filename;
        $id = $backup->getKey();
        $backup->delete();

        $this->auditLogs->write(
            actor: $actor,
            event: 'system-backup.deleted',
            description: 'A protected system backup was deleted.',
            requestContext: AuditRequestContext::fromRequest(request()),
            subjectName: $name,
            oldValues: ['backup_record_id' => $id],
            actorContext: 'administrator',
        );
    }
}
