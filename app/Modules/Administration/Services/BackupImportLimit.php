<?php

namespace App\Modules\Administration\Services;

use App\Models\SystemBackupSetting;
use Illuminate\Support\Facades\Schema;

final class BackupImportLimit
{
    public function megabytes(): int
    {
        $configured = Schema::hasTable('system_backup_settings')
            ? SystemBackupSetting::query()->value('max_import_mb')
            : null;

        return min(
            max(1, (int) ($configured ?? config('backups.default_import_limit_mb', 10))),
            $this->hardLimitMegabytes(),
        );
    }

    public function hardLimitMegabytes(): int
    {
        return max(1, (int) config('backups.max_import_limit_mb', 1024));
    }

    public function serverLimitMegabytes(): ?int
    {
        $limits = array_filter([
            $this->iniMegabytes((string) ini_get('upload_max_filesize')),
            $this->iniMegabytes((string) ini_get('post_max_size')),
        ], fn (?int $value): bool => $value !== null);

        return $limits === [] ? null : min($limits);
    }

    private function iniMegabytes(string $value): ?int
    {
        $value = trim($value);
        if ($value === '' || $value === '0') {
            return null;
        }

        if (preg_match('/^(\d+(?:\.\d+)?)\s*([KMG])?$/i', $value, $matches) !== 1) {
            return null;
        }

        $number = (float) $matches[1];
        $bytes = match (strtoupper($matches[2] ?? '')) {
            'G' => $number * 1024 * 1024 * 1024,
            'M' => $number * 1024 * 1024,
            'K' => $number * 1024,
            default => $number,
        };

        return max(1, (int) floor($bytes / 1024 / 1024));
    }
}
