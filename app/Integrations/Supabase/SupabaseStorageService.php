<?php

namespace App\Integrations\Supabase;

use App\APIs\Contracts\StorageProvider;
use App\Clients\Supabase\SupabaseStorageClient;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SupabaseStorageService implements StorageProvider
{
    public function __construct(private readonly SupabaseStorageClient $client) {}

    public function put(string $bucket, string $path, string $contents, string $contentType): string
    {
        $this->guard($bucket, $path);
        $this->client->upload($bucket, $path, $contents, $contentType);

        return trim($bucket.'/'.$path, '/');
    }

    public function signedUrl(string $bucket, string $path, int $expiresIn = 300): string
    {
        $this->guard($bucket, $path);

        if ($expiresIn < 1 || $expiresIn > 3600) {
            throw new InvalidArgumentException('Signed URL lifetime must be between 1 and 3600 seconds.');
        }

        return $this->client->createSignedUrl($bucket, $path, $expiresIn);
    }

    public function delete(string $bucket, string $path): void
    {
        $this->guard($bucket, $path);
        $this->client->delete($bucket, $path);
    }

    public function randomObjectPath(string $directory, string $extension): string
    {
        return trim($directory, '/').'/'.Str::uuid().'.'.strtolower(ltrim($extension, '.'));
    }

    private function guard(string $bucket, string $path): void
    {
        $allowedBuckets = (array) config('supabase.storage.private_buckets', []);

        if (! in_array($bucket, $allowedBuckets, true)) {
            throw new InvalidArgumentException('Storage bucket is not allowed.');
        }

        if ($path === '' || str_contains($path, '..') || str_contains($path, '\\') || str_starts_with($path, '/')) {
            throw new InvalidArgumentException('Invalid storage object path.');
        }
    }
}
