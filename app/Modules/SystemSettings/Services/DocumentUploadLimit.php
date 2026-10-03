<?php

namespace App\Modules\SystemSettings\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;

final class DocumentUploadLimit
{
    private ?int $resolvedMegabytes = null;

    public function megabytes(): int
    {
        if ($this->resolvedMegabytes !== null) {
            return $this->resolvedMegabytes;
        }

        $configured = Schema::hasColumn('system_settings', 'document_max_upload_mb')
            ? SystemSetting::query()->value('document_max_upload_mb')
            : null;

        return $this->resolvedMegabytes = min(
            max(1, (int) ($configured ?? $this->defaultMegabytes())),
            $this->hardLimitMegabytes(),
        );
    }

    public function kilobytes(): int
    {
        return $this->megabytes() * 1024;
    }

    public function hardLimitMegabytes(): int
    {
        return max(1, (int) config('ndmu-rmas.document.max_upload_limit_megabytes', 100));
    }

    public function serverLimitMegabytes(): ?int
    {
        $limits = array_filter([
            $this->iniMegabytes((string) ini_get('upload_max_filesize')),
            $this->iniMegabytes((string) ini_get('post_max_size')),
        ], fn (?int $value): bool => $value !== null);

        return $limits === [] ? null : min($limits);
    }

    private function defaultMegabytes(): int
    {
        return max(1, (int) ceil((int) config('ndmu-rmas.document.max_upload_kilobytes', 10240) / 1024));
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
