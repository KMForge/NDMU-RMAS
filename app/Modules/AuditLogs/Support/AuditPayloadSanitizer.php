<?php

namespace App\Modules\AuditLogs\Support;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

final class AuditPayloadSanitizer
{
    private const MAX_DEPTH = 6;

    private const MAX_ITEMS = 100;

    private const MAX_STRING = 500;

    private const MAX_JSON_BYTES = 16384;

    /** @var list<string> */
    private const SENSITIVE_FRAGMENTS = [
        'password', 'passwd', 'secret', 'token', 'credential', 'authorization',
        'cookie', 'session', 'csrf', 'signature_path', 'signature_image',
        'storage_path', 'stored_path', 'private_path', 'signed_url', 'temporary_url',
        'file_content', 'binary', 'hmac', 'api_key', 'access_key', 'refresh_key',
    ];

    /** @param array<string, mixed>|null $payload @return array<string, mixed>|null */
    public function sanitize(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        $normalized = $this->normalize($payload, 0);
        $json = json_encode($normalized, JSON_THROW_ON_ERROR);

        if (strlen($json) <= self::MAX_JSON_BYTES) {
            return $normalized;
        }

        return [
            '_truncated' => true,
            '_sha256' => hash('sha256', $json),
            '_original_bytes' => strlen($json),
        ];
    }

    /** @return array<string, mixed> */
    private function normalize(mixed $value, int $depth): array
    {
        if ($depth >= self::MAX_DEPTH) {
            return ['_truncated' => true, '_reason' => 'maximum depth'];
        }

        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if (! is_array($value)) {
            return ['value' => $this->scalar($value)];
        }

        $result = [];
        foreach (array_slice($value, 0, self::MAX_ITEMS, true) as $key => $item) {
            $safeKey = mb_substr((string) $key, 0, 100);

            if ($this->isSensitive($safeKey)) {
                $result[$safeKey] = '[REDACTED]';

                continue;
            }

            $result[$safeKey] = is_array($item) || $item instanceof Arrayable
                ? $this->normalize($item, $depth + 1)
                : $this->scalar($item);
        }

        if (count($value) > self::MAX_ITEMS) {
            $result['_truncated_items'] = count($value) - self::MAX_ITEMS;
        }

        return $result;
    }

    private function scalar(mixed $value): bool|float|int|string|null
    {
        return match (true) {
            $value === null, is_bool($value), is_int($value), is_float($value) => $value,
            is_string($value) => mb_substr(strip_tags($value), 0, self::MAX_STRING),
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof DateTimeInterface => $value->format(DATE_ATOM),
            is_resource($value) => '[UNSUPPORTED RESOURCE]',
            is_object($value) => '[UNSUPPORTED OBJECT:'.class_basename($value).']',
            default => '[UNSUPPORTED VALUE]',
        };
    }

    private function isSensitive(string $key): bool
    {
        $key = Str::lower($key);

        return collect(self::SENSITIVE_FRAGMENTS)->contains(
            fn (string $fragment): bool => str_contains($key, $fragment)
        );
    }
}
