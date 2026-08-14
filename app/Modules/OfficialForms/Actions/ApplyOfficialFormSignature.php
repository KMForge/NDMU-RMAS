<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormInstance;
use App\Models\OfficialFormSignature;
use App\Models\OfficialFormVerification;
use App\Models\User;
use App\Models\UserSignature;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;
use App\Modules\OfficialForms\Services\OfficialFormSignatureHasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class ApplyOfficialFormSignature
{
    public function __construct(
        private readonly OfficialFormAuthorization $authorization,
        private readonly OfficialFormSignatureHasher $hasher
    ) {}

    /**
     * Atomically execute a signed form action or attestation.
     */
    public function handle(
        User $actor,
        int $instanceId,
        int $expectedVersionId,
        string $academicAction,
        string $actorType,
        ?Request $request = null
    ): OfficialFormSignature {
        if (! $actor->isEligibleForSignatureEnrollment()) {
            throw new InvalidArgumentException('Your account type is not eligible for applying academic digital signatures.');
        }

        $disk = config('signatures.disk', 'local');

        return DB::transaction(function () use ($actor, $instanceId, $expectedVersionId, $academicAction, $actorType, $request, $disk): OfficialFormSignature {
            /** @var OfficialFormInstance $lockedInstance */
            $lockedInstance = OfficialFormInstance::query()
                ->with(['definition', 'currentVersion', 'group.members'])
                ->lockForUpdate()
                ->findOrFail($instanceId);

            $currentVersion = $lockedInstance->currentVersion;
            if ($currentVersion === null || (int) $currentVersion->id !== (int) $expectedVersionId) {
                $actualVersionId = $currentVersion?->id ?? 'none';
                throw new InvalidArgumentException("Stale form version. Expected version v{$expectedVersionId}, but instance current version is v{$actualVersionId}.");
            }

            // Authorization check
            if ($academicAction === 'sign_authorship') {
                $formCode = strtoupper($lockedInstance->definition->code);
                if ($formCode !== 'RES-049') {
                    throw new InvalidArgumentException('Authorship signature attestation is only valid for RES-049.');
                }
                if (! $actor->hasPermissionTo('forms.res-049.sign')) {
                    throw new InvalidArgumentException('You do not have permission to sign RES-049 authorship.');
                }
                $isGroupMember = $lockedInstance->group !== null
                    && $lockedInstance->group->members->contains('id', $actor->id);
                if (! $isGroupMember) {
                    throw new InvalidArgumentException('Only active research group members can sign RES-049 authorship.');
                }
            } else {
                if (! $this->authorization->canPerformAction($actor, $lockedInstance, $academicAction)) {
                    throw new InvalidArgumentException("User #{$actor->id} is not authorized to perform action '{$academicAction}' on form instance #{$lockedInstance->id}.");
                }
            }

            // Specimen check
            /** @var UserSignature|null $specimen */
            $specimen = UserSignature::query()
                ->where('user_id', $actor->id)
                ->first();

            if ($specimen === null) {
                throw new InvalidArgumentException('No digital signature registered. Please register a digital signature in Settings.');
            }

            if (! Storage::disk($specimen->storage_disk)->exists($specimen->storage_path)) {
                throw new InvalidArgumentException('Registered signature specimen file is missing. Please re-register your digital signature.');
            }

            $specimenBytes = Storage::disk($specimen->storage_disk)->get($specimen->storage_path);
            if (hash('sha256', (string) $specimenBytes) !== $specimen->content_sha256) {
                throw new InvalidArgumentException('Signature specimen integrity check failed. Please re-register your digital signature.');
            }

            // Duplicate check
            $duplicateExists = OfficialFormSignature::query()
                ->where('official_form_version_id', $currentVersion->id)
                ->where('signer_user_id', $actor->id)
                ->where('actor_type', $actorType)
                ->where('academic_action', $academicAction)
                ->exists();

            if ($duplicateExists) {
                throw new InvalidArgumentException("A digital signature attestation has already been recorded for action '{$academicAction}' as {$actorType}.");
            }

            // Execute Phase 19 domain action if not attestation-only
            if ($academicAction !== 'sign_authorship') {
                if ($academicAction === 'certify') {
                    app(CertifyOfficialForm::class)->handle($actor, $lockedInstance);
                } else {
                    $targetStatus = match ($academicAction) {
                        'endorse' => 'endorsed',
                        'receive', 'approve' => 'approved',
                        'validate' => 'completed',
                        default => 'approved',
                    };
                    app(ApproveOfficialForm::class)->handle($actor, $lockedInstance, [], $targetStatus, $academicAction);
                }
                $lockedInstance->refresh();
            }

            // Write snapshot file to disk
            $snapshotUuid = (string) Str::uuid();
            $snapshotPath = "official_form_signatures/{$lockedInstance->id}/v{$currentVersion->version_number}_{$actor->id}_{$snapshotUuid}.png";

            Storage::disk($disk)->put($snapshotPath, $specimenBytes);

            try {
                $payloadHash = $this->hasher->hashVersion($currentVersion);
                $sigHash = hash('sha256', (string) $specimenBytes);
                $secretKey = config('signatures.verification_key');
                $keyVersion = config('signatures.verification_key_version', 'v1');

                if (empty($secretKey)) {
                    throw new InvalidArgumentException('SIGNATURE_VERIFICATION_KEY is not configured in application environment.');
                }

                $signedAt = now();

                $attestationHash = $this->hasher->calculateHmac([
                    'instance_id' => (int) $lockedInstance->id,
                    'version_id' => (int) $currentVersion->id,
                    'version_number' => (int) $currentVersion->version_number,
                    'signer_user_id' => (int) $actor->id,
                    'actor_type' => $actorType,
                    'academic_action' => $academicAction,
                    'version_payload_sha256' => $payloadHash,
                    'signature_sha256' => $sigHash,
                    'key_version' => (string) $keyVersion,
                    'signed_at' => $signedAt->toIso8601String(),
                ], (string) $secretKey);

                $signatureRecord = OfficialFormSignature::query()->create([
                    'official_form_instance_id' => $lockedInstance->id,
                    'official_form_version_id' => $currentVersion->id,
                    'signer_user_id' => $actor->id,
                    'user_signature_id' => $specimen->id,
                    'actor_type' => $actorType,
                    'academic_action' => $academicAction,
                    'signer_name_snapshot' => $actor->name,
                    'signer_email_snapshot' => $actor->email,
                    'signature_storage_disk' => $disk,
                    'signature_storage_path' => $snapshotPath,
                    'signature_sha256' => $sigHash,
                    'version_payload_sha256' => $payloadHash,
                    'attestation_hash' => $attestationHash,
                    'attestation_key_version' => (string) $keyVersion,
                    'signed_at' => $signedAt,
                    'ip_address' => $request?->ip(),
                    'user_agent' => $request?->userAgent(),
                ]);

                OfficialFormVerification::query()->firstOrCreate(
                    ['official_form_version_id' => $currentVersion->id],
                    ['public_reference' => (string) Str::uuid()]
                );

                AuditLog::query()->create([
                    'user_id' => $actor->id,
                    'actor_name' => $actor->name,
                    'actor_email' => $actor->email,
                    'event' => 'official_form.signature_applied',
                    'auditable_type' => OfficialFormInstance::class,
                    'auditable_id' => $lockedInstance->id,
                    'description' => "Applied digital signature for {$academicAction} ({$actorType}) on form {$lockedInstance->definition->code} v{$currentVersion->version_number}.",
                    'subject_snapshot' => [
                        'official_form_signature_id' => $signatureRecord->id,
                        'academic_action' => $academicAction,
                        'actor_type' => $actorType,
                        'version_payload_sha256' => $payloadHash,
                        'attestation_hash' => $attestationHash,
                    ],
                ]);

                return $signatureRecord;
            } catch (Throwable $e) {
                if (Storage::disk($disk)->exists($snapshotPath)) {
                    Storage::disk($disk)->delete($snapshotPath);
                }
                throw $e;
            }
        });
    }
}
