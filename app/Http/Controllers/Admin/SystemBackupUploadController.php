<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Administration\Services\BackupImportLimit;
use App\Modules\Administration\Services\ChunkedBackupUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class SystemBackupUploadController extends Controller
{
    public function store(
        Request $request,
        BackupImportLimit $importLimit,
        ChunkedBackupUpload $uploads,
    ): JsonResponse {
        $actor = $this->actor($request);
        $validated = $request->validate([
            'original_name' => ['required', 'string', 'max:255', 'regex:/\.zip$/i'],
            'size_bytes' => ['required', 'integer', 'min:1', 'max:'.($importLimit->megabytes() * 1024 * 1024)],
        ]);

        try {
            return response()->json($uploads->initiate(
                $actor,
                $validated['original_name'],
                (int) $validated['size_bytes'],
            ), 201);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function chunk(
        Request $request,
        string $upload,
        int $index,
        ChunkedBackupUpload $uploads,
    ): JsonResponse {
        $validated = $request->validate([
            'chunk' => ['required', 'file', 'max:'.$uploads->chunkSizeKilobytes()],
        ]);

        try {
            $uploads->storeChunk($this->actor($request), $upload, $index, $validated['chunk']);

            return response()->json(['received' => $index]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function complete(
        Request $request,
        string $upload,
        ChunkedBackupUpload $uploads,
    ): JsonResponse {
        try {
            $backup = $uploads->complete($this->actor($request), $upload);

            return response()->json([
                'message' => "Backup {$backup->filename} was imported and verified. It has not been restored yet.",
                'backup_id' => $backup->getKey(),
            ]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function destroy(
        Request $request,
        string $upload,
        ChunkedBackupUpload $uploads,
    ): JsonResponse {
        $uploads->cancel($this->actor($request), $upload);

        return response()->json(status: 204);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->can('settings.manage'), 403);

        return $actor;
    }
}
