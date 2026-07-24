<?php

namespace App\Modules\Documents\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class SecureDocumentFile implements ValidationRule
{
    private const DANGEROUS_EXTENSIONS = [
        'apk', 'bat', 'cmd', 'com', 'dll', 'exe', 'html', 'htm', 'jar', 'js',
        'msi', 'php', 'phar', 'ps1', 'scr', 'sh', 'svg', 'vbs',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('The document upload failed or is incomplete.');

            return;
        }

        $originalName = $value->getClientOriginalName();
        $path = $value->getRealPath();
        $size = $value->getSize();

        if (
            $originalName === ''
            || str_contains($originalName, "\0")
            || str_contains($originalName, '/')
            || str_contains($originalName, '\\')
            || $path === false
            || ! is_readable($path)
            || $size === false
            || $size < 1
        ) {
            $fail('The document is empty or has an invalid filename.');

            return;
        }

        if ($this->containsDangerousExtension($originalName)) {
            $fail('Executable or potentially dangerous files are not allowed.');

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        $allowedTypes = config('ndmu-rmas.document.allowed_types', []);

        if (! in_array($extension, ['pdf', 'docx'], true) || ! isset($allowedTypes[$extension])) {
            $fail('Only PDF and Microsoft Word (.docx) documents are allowed.');

            return;
        }

        $mimeType = strtolower((string) $value->getMimeType());

        if (! in_array($mimeType, $allowedTypes[$extension], true)) {
            $fail('The document content does not match its file type.');

            return;
        }

        $validSignature = $extension === 'pdf'
            ? $this->isValidPdf($path, (int) $size)
            : $this->isValidDocx($path);

        if (! $validSignature) {
            $fail('The document is corrupted or has an invalid file signature.');
        }
    }

    private function containsDangerousExtension(string $filename): bool
    {
        $extensions = implode('|', array_map('preg_quote', self::DANGEROUS_EXTENSIONS));

        return preg_match('/\.('.$extensions.')(?:\.|$)/i', $filename) === 1;
    }

    private function isValidPdf(string $path, int $size): bool
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            $header = fread($handle, 5);

            if ($header !== '%PDF-') {
                return false;
            }

            $tailLength = min($size, 8192);

            if (fseek($handle, -$tailLength, SEEK_END) !== 0) {
                return false;
            }

            $tail = fread($handle, $tailLength);

            return is_string($tail) && str_contains($tail, '%%EOF');
        } finally {
            fclose($handle);
        }
    }

    private function isValidDocx(string $path): bool
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $signature = fread($handle, 4);
        fclose($handle);

        if ($signature !== "PK\x03\x04") {
            return false;
        }

        $archive = new ZipArchive;

        if ($archive->open($path, ZipArchive::RDONLY) !== true) {
            return false;
        }

        try {
            if ($archive->numFiles < 1 || $archive->numFiles > 2000) {
                return false;
            }

            $uncompressedBytes = 0;

            for ($index = 0; $index < $archive->numFiles; $index++) {
                $name = $archive->getNameIndex($index);
                $statistics = $archive->statIndex($index);

                if (! is_string($name) || ! is_array($statistics) || $this->isUnsafeArchivePath($name)) {
                    return false;
                }

                if ($this->containsDangerousExtension($name)) {
                    return false;
                }

                $uncompressedBytes += (int) ($statistics['size'] ?? 0);

                if ($uncompressedBytes > 100 * 1024 * 1024) {
                    return false;
                }

                if ($archive->getFromIndex($index) === false) {
                    return false;
                }
            }

            $contentTypes = $archive->getFromName('[Content_Types].xml');
            $relationships = $archive->getFromName('_rels/.rels');
            $documentXml = $archive->getFromName('word/document.xml');

            return is_string($contentTypes)
                && str_contains($contentTypes, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml')
                && ! str_contains(strtolower($contentTypes), 'macroenabled')
                && is_string($relationships)
                && $relationships !== ''
                && is_string($documentXml)
                && str_contains($documentXml, '<w:document');
        } finally {
            $archive->close();
        }
    }

    private function isUnsafeArchivePath(string $name): bool
    {
        $normalized = str_replace('\\', '/', $name);

        return str_starts_with($normalized, '/')
            || preg_match('/^[A-Za-z]:\//', $normalized) === 1
            || in_array('..', explode('/', $normalized), true)
            || str_contains($normalized, "\0");
    }
}
