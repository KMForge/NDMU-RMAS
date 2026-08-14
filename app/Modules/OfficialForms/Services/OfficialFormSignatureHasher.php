<?php

namespace App\Modules\OfficialForms\Services;

use App\Models\OfficialFormVersion;

class OfficialFormSignatureHasher
{
    /**
     * Compute canonical SHA-256 hash for an OfficialFormVersion payload and metadata.
     */
    public function hashVersion(OfficialFormVersion $version): string
    {
        $instance = $version->instance;

        $canonicalStructure = [
            'form_code' => strtoupper($instance->definition->code),
            'official_form_instance_id' => (int) $instance->id,
            'official_form_version_id' => (int) $version->id,
            'version_number' => (int) $version->version_number,
            'payload' => $this->sortKeysRecursive($version->payload ?? []),
            'source_snapshot' => $this->sortKeysRecursive($version->source_snapshot ?? []),
        ];

        $canonicalJson = json_encode($canonicalStructure, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', (string) $canonicalJson);
    }

    /**
     * Compute HMAC-SHA256 attestation string over structured signing evidence.
     *
     * @param  array<string, mixed>  $attestationData
     */
    public function calculateHmac(array $attestationData, string $secretKey): string
    {
        $sortedData = $this->sortKeysRecursive($attestationData);
        $canonicalJson = json_encode($sortedData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash_hmac('sha256', (string) $canonicalJson, $secretKey);
    }

    /**
     * Recursively sort array keys to guarantee identical JSON encoding.
     */
    private function sortKeysRecursive(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        if (array_is_list($data)) {
            return array_map([$this, 'sortKeysRecursive'], $data);
        }

        ksort($data);

        foreach ($data as $key => $value) {
            $data[$key] = $this->sortKeysRecursive($value);
        }

        return $data;
    }
}
