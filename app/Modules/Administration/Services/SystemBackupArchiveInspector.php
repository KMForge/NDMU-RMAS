<?php

namespace App\Modules\Administration\Services;

use RuntimeException;
use ZipArchive;

final class SystemBackupArchiveInspector
{
    /**
     * @return array{manifest: array<string, mixed>, archive_size: int, archive_sha256: string, database_size: int, database_sha256: string, private_file_count: int, private_files_size: int}
     */
    public function inspect(string $archivePath, ?int $expectedSize = null, ?string $expectedSha256 = null): array
    {
        if (! is_file($archivePath) || ! is_readable($archivePath)) {
            throw new RuntimeException('The backup archive cannot be read.');
        }

        $archiveSize = filesize($archivePath);
        $archiveSha256 = hash_file('sha256', $archivePath);
        if ($archiveSize === false || $archiveSha256 === false) {
            throw new RuntimeException('The backup archive size or checksum could not be calculated.');
        }
        if ($expectedSize !== null && $archiveSize !== $expectedSize) {
            throw new RuntimeException('The backup archive size does not match its protected record.');
        }
        if ($expectedSha256 !== null && ! hash_equals($expectedSha256, $archiveSha256)) {
            throw new RuntimeException('The backup archive checksum does not match its protected record.');
        }

        $zip = new ZipArchive;
        if ($zip->open($archivePath, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('The imported file is not a readable ZIP backup.');
        }

        try {
            $uncompressedBytes = 0;
            $privateFileCount = 0;
            $privateFilesSize = 0;

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);
                $name = str_replace('\\', '/', (string) ($entry['name'] ?? ''));
                $size = (int) ($entry['size'] ?? 0);

                if ($name === '' || str_starts_with($name, '/') || preg_match('/^[A-Za-z]:\//', $name) === 1
                    || in_array('..', explode('/', $name), true)) {
                    throw new RuntimeException('The backup archive contains an unsafe file path.');
                }

                $uncompressedBytes += $size;
                if ($uncompressedBytes > ((int) config('backups.max_uncompressed_mb', 5120) * 1024 * 1024)) {
                    throw new RuntimeException('The backup expands beyond the configured safety limit.');
                }

                if (str_starts_with($name, 'private-files/') && ! str_ends_with($name, '/')) {
                    $privateFileCount++;
                    $privateFilesSize += $size;
                }
            }

            $manifestJson = $zip->getFromName('manifest.json');
            $databaseEntry = $zip->statName('database/database.dump');
            if (! is_string($manifestJson) || $databaseEntry === false || (int) ($databaseEntry['size'] ?? 0) === 0) {
                throw new RuntimeException('The backup is missing its manifest or complete PostgreSQL database dump.');
            }

            $manifest = json_decode($manifestJson, true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($manifest)
                || ($manifest['application'] ?? null) !== config('app.name')
                || ($manifest['database_driver'] ?? null) !== 'pgsql') {
                throw new RuntimeException('The backup manifest does not belong to this PostgreSQL application.');
            }

            $databaseSha256 = $this->hashEntry($zip, 'database/database.dump');
            if (isset($manifest['database_dump_sha256'])
                && ! hash_equals((string) $manifest['database_dump_sha256'], $databaseSha256)) {
                throw new RuntimeException('The PostgreSQL dump checksum does not match the backup manifest.');
            }

            return [
                'manifest' => $manifest,
                'archive_size' => $archiveSize,
                'archive_sha256' => $archiveSha256,
                'database_size' => (int) $databaseEntry['size'],
                'database_sha256' => $databaseSha256,
                'private_file_count' => $privateFileCount,
                'private_files_size' => $privateFilesSize,
            ];
        } finally {
            $zip->close();
        }
    }

    private function hashEntry(ZipArchive $zip, string $name): string
    {
        $stream = $zip->getStream($name);
        if (! is_resource($stream)) {
            throw new RuntimeException('The PostgreSQL dump cannot be read from the archive.');
        }

        $hash = hash_init('sha256');
        hash_update_stream($hash, $stream);
        fclose($stream);

        return hash_final($hash);
    }
}
