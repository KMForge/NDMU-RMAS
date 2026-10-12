<?php

namespace App\Modules\Administration\Services;

use App\Models\SystemBackup;
use App\Models\User;
use App\Modules\Administration\Actions\ImportSystemBackup;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class ChunkedBackupUpload
{
    public function __construct(
        private readonly BackupImportLimit $importLimit,
        private readonly ImportSystemBackup $importBackup,
    ) {}

    /** @return array{upload_id: string, chunk_size_bytes: int, total_chunks: int} */
    public function initiate(User $actor, string $originalName, int $sizeBytes): array
    {
        $this->authorize($actor);

        $maximumBytes = $this->importLimit->megabytes() * 1024 * 1024;
        if ($sizeBytes < 1 || $sizeBytes > $maximumBytes) {
            throw new RuntimeException('The backup archive exceeds the configured import size limit.');
        }

        if (! Str::endsWith(Str::lower($originalName), '.zip')) {
            throw new RuntimeException('Select a ZIP archive created by NDMU-RMAS Backup Management.');
        }

        $uploadId = (string) Str::uuid();
        $chunkSize = $this->chunkSizeBytes();
        $metadata = [
            'user_id' => (int) $actor->getKey(),
            'original_name' => mb_substr(basename(str_replace('\\', '/', $originalName)), 0, 255),
            'size_bytes' => $sizeBytes,
            'chunk_size_bytes' => $chunkSize,
            'total_chunks' => (int) ceil($sizeBytes / $chunkSize),
            'created_at' => now()->toIso8601String(),
        ];

        $disk = Storage::disk('local');
        $directory = $this->directory($actor, $uploadId);
        if (! $disk->makeDirectory($directory.'/chunks')
            || ! $disk->put($directory.'/metadata.json', json_encode($metadata, JSON_THROW_ON_ERROR))) {
            $disk->deleteDirectory($directory);

            throw new RuntimeException('The backup upload session could not be created.');
        }

        return [
            'upload_id' => $uploadId,
            'chunk_size_bytes' => $chunkSize,
            'total_chunks' => $metadata['total_chunks'],
        ];
    }

    public function storeChunk(User $actor, string $uploadId, int $index, UploadedFile $chunk): void
    {
        $this->authorize($actor);
        $metadata = $this->metadata($actor, $uploadId);

        if ($index < 0 || $index >= $metadata['total_chunks']) {
            throw new RuntimeException('The backup upload contains an invalid chunk number.');
        }

        $expectedBytes = $index === $metadata['total_chunks'] - 1
            ? $metadata['size_bytes'] - ($index * $metadata['chunk_size_bytes'])
            : $metadata['chunk_size_bytes'];
        $actualBytes = $chunk->getSize();

        if ($actualBytes === false || $actualBytes !== $expectedBytes || $actualBytes > $this->chunkSizeBytes()) {
            throw new RuntimeException('A backup upload chunk has an invalid size.');
        }

        $stream = fopen($chunk->getRealPath(), 'rb');
        if (! is_resource($stream)) {
            throw new RuntimeException('A backup upload chunk could not be opened.');
        }

        try {
            $stored = Storage::disk('local')->put(
                $this->directory($actor, $uploadId)."/chunks/{$index}.part",
                $stream,
            );
        } finally {
            fclose($stream);
        }

        if (! $stored) {
            throw new RuntimeException('A backup upload chunk could not be stored.');
        }
    }

    public function complete(User $actor, string $uploadId): SystemBackup
    {
        $this->authorize($actor);

        return Cache::lock("backup-upload:{$actor->getKey()}:{$uploadId}", 600)
            ->block(5, function () use ($actor, $uploadId): SystemBackup {
                $metadata = $this->metadata($actor, $uploadId);
                $disk = Storage::disk('local');
                $directory = $this->directory($actor, $uploadId);
                $assembledPath = $disk->path($directory.'/assembled.zip');
                $output = fopen($assembledPath, 'wb');

                if (! is_resource($output)) {
                    throw new RuntimeException('The backup archive could not be assembled.');
                }

                try {
                    for ($index = 0; $index < $metadata['total_chunks']; $index++) {
                        $chunkPath = $directory."/chunks/{$index}.part";
                        if (! $disk->exists($chunkPath)) {
                            throw new RuntimeException('The backup upload is incomplete. Please upload it again.');
                        }

                        $input = fopen($disk->path($chunkPath), 'rb');
                        if (! is_resource($input)) {
                            throw new RuntimeException('A backup upload chunk could not be read.');
                        }

                        try {
                            stream_copy_to_stream($input, $output);
                        } finally {
                            fclose($input);
                        }
                    }
                } finally {
                    fclose($output);
                }

                if (filesize($assembledPath) !== $metadata['size_bytes']) {
                    throw new RuntimeException('The assembled backup archive has an invalid size.');
                }

                try {
                    return $this->importBackup->handle(
                        $actor,
                        $assembledPath,
                        $metadata['original_name'],
                    );
                } finally {
                    $disk->deleteDirectory($directory);
                }
            });
    }

    public function cancel(User $actor, string $uploadId): void
    {
        $this->authorize($actor);
        Storage::disk('local')->deleteDirectory($this->directory($actor, $uploadId));
    }

    public function chunkSizeKilobytes(): int
    {
        return max(1, (int) config('backups.chunk_upload_kilobytes', 8192));
    }

    private function chunkSizeBytes(): int
    {
        return $this->chunkSizeKilobytes() * 1024;
    }

    /** @return array{user_id: int, original_name: string, size_bytes: int, chunk_size_bytes: int, total_chunks: int, created_at: string} */
    private function metadata(User $actor, string $uploadId): array
    {
        if (! Str::isUuid($uploadId)) {
            throw new RuntimeException('The backup upload session is invalid.');
        }

        $contents = Storage::disk('local')->get($this->directory($actor, $uploadId).'/metadata.json');
        if (! is_string($contents) || $contents === '') {
            throw new RuntimeException('The backup upload session has expired. Please upload it again.');
        }

        $metadata = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($metadata) || (int) ($metadata['user_id'] ?? 0) !== (int) $actor->getKey()) {
            throw new RuntimeException('The backup upload session is invalid.');
        }

        return [
            'user_id' => (int) $metadata['user_id'],
            'original_name' => (string) $metadata['original_name'],
            'size_bytes' => (int) $metadata['size_bytes'],
            'chunk_size_bytes' => (int) $metadata['chunk_size_bytes'],
            'total_chunks' => (int) $metadata['total_chunks'],
            'created_at' => (string) $metadata['created_at'],
        ];
    }

    private function directory(User $actor, string $uploadId): string
    {
        return "backup-upload-chunks/{$actor->getKey()}/{$uploadId}";
    }

    private function authorize(User $actor): void
    {
        abort_unless($actor->can('settings.manage'), 403);
    }
}
