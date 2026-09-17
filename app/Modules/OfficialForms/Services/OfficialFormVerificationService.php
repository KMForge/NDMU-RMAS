<?php

namespace App\Modules\OfficialForms\Services;

use App\Models\OfficialFormSignature;
use App\Models\OfficialFormVersion;
use Illuminate\Support\Facades\Storage;

class OfficialFormVerificationService
{
    public function __construct(
        private readonly OfficialFormSignatureHasher $hasher
    ) {}

    /**
     * Dynamically evaluate the verification status of an OfficialFormVersion.
     *
     * @return array{
     *     status: string,
     *     is_valid: bool,
     *     is_current: bool,
     *     calculated_payload_hash: string,
     *     signatures_evaluated: int,
     *     valid_signatures_count: int,
     *     signatures: array<int, array<string, mixed>>
     * }
     */
    public function evaluateVerification(OfficialFormVersion $version): array
    {
        $instance = $version->instance;
        $calculatedPayloadHash = $this->hasher->hashVersion($version);
        $signatures = $version->signatures;

        if ($signatures->isEmpty()) {
            return [
                'status' => 'UNSIGNED',
                'is_valid' => false,
                'is_current' => (int) $instance->current_version_id === (int) $version->id,
                'calculated_payload_hash' => $calculatedPayloadHash,
                'signatures_evaluated' => 0,
                'valid_signatures_count' => 0,
                'signatures' => [],
            ];
        }

        $isCurrent = (int) $instance->current_version_id === (int) $version->id;
        $secretKey = config('signatures.verification_key');

        if (empty($secretKey)) {
            return [
                'status' => 'KEY_UNCONFIGURED',
                'is_valid' => false,
                'is_current' => $isCurrent,
                'calculated_payload_hash' => $calculatedPayloadHash,
                'signatures_evaluated' => $signatures->count(),
                'valid_signatures_count' => 0,
                'signatures' => [],
            ];
        }

        $evaluatedSignatures = [];
        $validCount = 0;
        $versionHashValid = true;

        foreach ($signatures as $sig) {
            $sigStatus = 'VALID';

            if ($sig->version_payload_sha256 !== $calculatedPayloadHash) {
                $sigStatus = 'INVALID_VERSION_HASH';
                $versionHashValid = false;
            } elseif (! Storage::disk($sig->signature_storage_disk)->exists($sig->signature_storage_path)) {
                $sigStatus = 'MISSING_FILE';
            } else {
                $bytes = Storage::disk($sig->signature_storage_disk)->get($sig->signature_storage_path);
                $fileHash = hash('sha256', (string) $bytes);

                if ($fileHash !== $sig->signature_sha256) {
                    $sigStatus = 'INVALID_SIGNATURE_HASH';
                } else {
                    $expectedHmac = $this->hasher->calculateHmac([
                        'instance_id' => (int) $instance->id,
                        'version_id' => (int) $version->id,
                        'version_number' => (int) $version->version_number,
                        'signer_user_id' => (int) $sig->signer_user_id,
                        'actor_type' => $sig->actor_type,
                        'academic_action' => $sig->academic_action,
                        'version_payload_sha256' => $sig->version_payload_sha256,
                        'signature_sha256' => $sig->signature_sha256,
                        'key_version' => $sig->attestation_key_version,
                        'signed_at' => $sig->signed_at->toIso8601String(),
                    ], (string) $secretKey);

                    if (! hash_equals($expectedHmac, $sig->attestation_hash)) {
                        $sigStatus = 'INVALID_ATTESTATION';
                    }
                }
            }

            if ($sigStatus === 'VALID') {
                $validCount++;
            }

            $evaluatedSignatures[] = [
                'id' => $sig->id,
                'signer_name' => $sig->signer_name_snapshot,
                'actor_type' => $sig->actor_type,
                'academic_action' => $sig->academic_action,
                'signed_at' => $sig->signed_at->toIso8601String(),
                'status' => $sigStatus,
                'is_valid' => $sigStatus === 'VALID',
            ];
        }

        $totalCount = $signatures->count();

        if (! $versionHashValid) {
            $aggregateStatus = 'INVALID_VERSION_HASH';
            $isValid = false;
        } elseif ($validCount === $totalCount) {
            $aggregateStatus = $isCurrent ? 'VALID_CURRENT' : 'VALID_HISTORICAL';
            $isValid = true;
        } elseif ($validCount > 0) {
            $aggregateStatus = 'PARTIALLY_INVALID';
            $isValid = false;
        } else {
            $aggregateStatus = 'INVALID_ALL_SIGNATURES';
            $isValid = false;
        }

        return [
            'status' => $aggregateStatus,
            'is_valid' => $isValid,
            'is_current' => $isCurrent,
            'calculated_payload_hash' => $calculatedPayloadHash,
            'signatures_evaluated' => $totalCount,
            'valid_signatures_count' => $validCount,
            'signatures' => $evaluatedSignatures,
        ];
    }

    /** @return array<string, mixed> */
    public function evaluateSignature(OfficialFormSignature $signature): array
    {
        $signature->loadMissing('version.instance');
        $versionEvaluation = $this->evaluateVerification($signature->version);
        $signatureEvaluation = collect($versionEvaluation['signatures'] ?? [])
            ->firstWhere('id', $signature->getKey());

        return [
            'status' => $signatureEvaluation['status'] ?? $versionEvaluation['status'],
            'is_valid' => (bool) ($signatureEvaluation['is_valid'] ?? false),
            'is_current_version' => (bool) $versionEvaluation['is_current'],
            'document_status' => $versionEvaluation['status'],
            'signer_name' => $signature->signer_name_snapshot,
            'actor_type' => $signature->actor_type,
            'academic_action' => $signature->academic_action,
            'signed_at' => $signature->signed_at->toIso8601String(),
            'signature_sha256' => $signature->signature_sha256,
            'version_payload_sha256' => $signature->version_payload_sha256,
            'attestation_hash' => $signature->attestation_hash,
            'attestation_key_version' => $signature->attestation_key_version,
        ];
    }
}
