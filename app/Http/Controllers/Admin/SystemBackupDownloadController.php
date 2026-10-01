<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemBackup;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SystemBackupDownloadController extends Controller
{
    public function __invoke(Request $request, SystemBackup $backup, AuditLogWriter $auditLogs): StreamedResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);
        abort_unless($backup->status === 'completed' && $backup->storage_path !== null, 404);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($backup->storage_disk);
        abort_unless($disk->exists($backup->storage_path), 404);

        $auditLogs->write(
            actor: $request->user(),
            event: 'system-backup.downloaded',
            description: 'A protected system backup was downloaded.',
            requestContext: AuditRequestContext::fromRequest($request),
            auditable: $backup,
            subjectName: $backup->filename,
            actorContext: 'administrator',
        );

        return $disk->download($backup->storage_path, $backup->filename, [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
