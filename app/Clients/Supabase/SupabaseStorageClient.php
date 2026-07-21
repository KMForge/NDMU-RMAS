<?php

namespace App\Clients\Supabase;

use Illuminate\Http\Client\Response;
use RuntimeException;

class SupabaseStorageClient
{
    public function __construct(private readonly SupabaseClient $client) {}

    public function upload(string $bucket, string $path, string $contents, string $contentType): Response
    {
        return $this->client->request()
            ->withBody($contents, $contentType)
            ->put('object/'.$this->objectPath($bucket, $path))
            ->throw();
    }

    public function createSignedUrl(string $bucket, string $path, int $expiresIn): string
    {
        $response = $this->client->request()
            ->post('object/sign/'.$this->objectPath($bucket, $path), ['expiresIn' => $expiresIn])
            ->throw();

        $signedPath = $response->json('signedURL') ?? $response->json('signedUrl');

        if (! is_string($signedPath) || $signedPath === '') {
            throw new RuntimeException('Supabase did not return a signed URL.');
        }

        return str_starts_with($signedPath, 'http')
            ? $signedPath
            : rtrim((string) config('supabase.url'), '/').'/storage/v1'.$signedPath;
    }

    public function delete(string $bucket, string $path): void
    {
        $this->client->request()
            ->delete('object/'.$this->objectPath($bucket, $path))
            ->throw();
    }

    private function objectPath(string $bucket, string $path): string
    {
        return collect(explode('/', trim($bucket.'/'.$path, '/')))
            ->map(fn (string $segment): string => rawurlencode($segment))
            ->implode('/');
    }
}
