<?php

namespace App\Modules\Documents\Support;

use Illuminate\Support\Str;

class DocumentFilenameSanitizer
{
    public function sanitize(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        $filename = basename(str_replace('\\', '/', $filename));
        $filename = Str::ascii($filename);
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename) ?? '';
        $filename = preg_replace('/[^A-Za-z0-9._ -]/', '_', $filename) ?? '';
        $filename = preg_replace('/\s+/', ' ', $filename) ?? '';
        $filename = trim($filename, " .\t\n\r\0\x0B");

        if ($filename === '') {
            return 'document';
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $stem = pathinfo($filename, PATHINFO_FILENAME);
        $maximumStemLength = max(1, 240 - ($extension === '' ? 0 : strlen($extension) + 1));
        $stem = Str::limit($stem, $maximumStemLength, '');

        return $extension === '' ? $stem : "{$stem}.{$extension}";
    }
}
