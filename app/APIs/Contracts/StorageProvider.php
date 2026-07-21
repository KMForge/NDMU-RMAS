<?php

namespace App\APIs\Contracts;

interface StorageProvider
{
    public function put(string $bucket, string $path, string $contents, string $contentType): string;

    public function signedUrl(string $bucket, string $path, int $expiresIn = 300): string;

    public function delete(string $bucket, string $path): void;
}
