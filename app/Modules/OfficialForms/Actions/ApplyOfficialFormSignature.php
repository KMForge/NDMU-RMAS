<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\DefenseEvaluationRound;
use App\Models\OfficialFormInstance;
use App\Models\OfficialFormSignature;
use App\Models\OfficialFormVerification;
use App\Models\OfficialFormVersion;
use App\Models\ResearchClassActorAssignment;
use App\Models\ResearchClassGroup;
use App\Models\TitlePresentation;
use App\Models\User;
use App\Models\UserSignature;
use App\Modules\Evaluations\Actions\FinalizeDefenseEvaluationRound;
use App\Modules\OfficialForms\Services\InstitutionalActorResolver;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;
use App\Modules\OfficialForms\Services\OfficialFormSignatureHasher;
use App\Modules\Research\Actions\EnsureCanonicalResearchGroup;
use App\Modules\ResearchProgress\Actions\SynchronizeWorkflowMilestone;
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
        private readonly OfficialFormSignatureHasher $hasher,
        private readonly EnsureCanonicalResearchGroup $ensureCanonicalResearchGroup,
        private readonly SynchronizeWorkflowMilestone $synchronizeMilestone,
    ) {}

    /**
     * Atomically execute a signed form action or attestation.
     */
    public function handle(
        User $actor,
        int $instanceId,
        int $expectedVersionId,
        string $academicAction,
        ?Request $request = null
    ): OfficialFormSignature {
        if (! $actor->isEligibleForSignatureEnrollment()) {
            throw new InvalidArgumentException('Your account type is not eligible for applying academic digital signatures.');
        }

        $disk = config('signatures.disk', 'local');

        return DB::transaction(function () use ($actor, $instanceId, $expectedVersionId, $academicAction, $request, $disk): OfficialFormSignature {
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

            // Derive authoritative actor type server-side
            $actorType = $this->authorization->requiredActorType($lockedInstance, $academicAction);
            if ($actorType === null) {
                throw new InvalidArgumentException("Action '{$academicAction}' is not supported for form {$lockedInstance->definition->code}.");
            }

            // Authorization check
            if ($academicAction === 'sign_authorship') {
                $formCode = strtoupper($lockedInstance->definition->code);
                if ($formCode !== 'RES-049') {
                    throw new InvalidArgumentException('Authorship signature attestation is only valid for RES-049.');
                }
                if (! $this->authorization->canSignAuthorship($actor, $lockedInstance)) {
                    throw new InvalidArgumentException('You are not authorized to sign RES-049 authorship attestation.');
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
            $isRes026PanelSignature = strtoupper($lockedInstance->definition->code) === 'RES-026'
                && in_array($academicAction, ['sign_chairperson', 'sign_member_1', 'sign_member_2'], true);

            if ($academicAction !== 'sign_authorship' && ! $isRes026PanelSignature) {
                if ($academicAction === 'certify') {
                    app(CertifyOfficialForm::class)->handle($actor, $lockedInstance);
                } else {
                    $transition = $this->authorization->transitionFor($lockedInstance, $academicAction);
                    if ($transition === null) {
                        throw new InvalidArgumentException("No valid workflow transition defined for action '{$academicAction}' on form {$lockedInstance->definition->code}.");
                    }
                    if ($lockedInstance->status !== $transition['to']) {
                        app(ApproveOfficialForm::class)->handle($actor, $lockedInstance, [], $transition['to'], $academicAction);
                    }
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

                if (strtoupper($lockedInstance->definition->code) === 'RES-026') {
                    $this->advanceRes026AfterSignature($lockedInstance, $signatureRecord, $actor);
                    $this->applyDualSignaturesIfEligible($actor, $lockedInstance, $currentVersion, $academicAction, $specimen, $specimenBytes, $request, $disk);
                }

                if (strtoupper($lockedInstance->definition->code) === 'RES-037' && $lockedInstance->source_type === DefenseEvaluationRound::class && $lockedInstance->source) {
                    app(FinalizeDefenseEvaluationRound::class)->handle($lockedInstance->source);
                }

                if (strtoupper($lockedInstance->definition->code) === 'RES-041' && in_array($lockedInstance->status, ['approved', 'completed'], true)) {
                    $class = $lockedInstance->researchClass ?? $lockedInstance->group?->researchClass;
                    if ($class) {
                        $class->loadMissing('groups');
                        foreach ($class->groups as $classGroup) {
                            $this->synchronizeMilestone->complete(
                                $classGroup,
                                'revision-research-proposal',
                                $actor,
                                'official_form',
                                $lockedInstance->id,
                                'Completed Revision of Research Proposal Paper (RES-041).',
                            );
                        }
                    } elseif ($lockedInstance->group) {
                        $this->synchronizeMilestone->complete(
                            $lockedInstance->group,
                            'revision-research-proposal',
                            $actor,
                            'official_form',
                            $lockedInstance->id,
                            'Completed Revision of Research Proposal Paper (RES-041).',
                        );
                    }
                }

                return $signatureRecord;
            } catch (Throwable $e) {
                if (Storage::disk($disk)->exists($snapshotPath)) {
                    Storage::disk($disk)->delete($snapshotPath);
                }
                throw $e;
            }
        });
    }

    private function applyDualSignaturesIfEligible(
        User $actor,
        OfficialFormInstance $instance,
        OfficialFormVersion $currentVersion,
        string $primaryAction,
        UserSignature $specimen,
        string $specimenBytes,
        ?Request $request,
        string $disk
    ): void {
        $code = strtoupper($instance->definition->code);

        if ($code === 'RES-026') {
            $potentialActions = [
                'sign_chairperson' => 'title_panel_chairperson',
                'sign_member_1' => 'title_panel_member_1',
                'sign_member_2' => 'title_panel_member_2',
                'endorse' => 'program_coordinator',
            ];

            foreach ($potentialActions as $action => $actorType) {
                if ($action === $primaryAction) {
                    continue;
                }

                $qualifies = false;
                if ($action === 'endorse') {
                    $class = $instance->researchClass ?? $instance->group?->researchClass;
                    $qualifies = app(InstitutionalActorResolver::class)->isProgramCoordinator($actor, $class, $instance->group)
                        || ($class !== null && ResearchClassActorAssignment::query()->where('research_class_id', $class->id)->where('user_id', $actor->id)->whereIn('actor_type', ['program_coordinator', 'program_head'])->where('status', 'active')->exists());
                } elseif (str_starts_with($action, 'sign_')) {
                    $position = match ($action) {
                        'sign_chairperson' => 'chairperson',
                        'sign_member_1' => 'member_1',
                        'sign_member_2' => 'member_2',
                        default => null,
                    };
                    $qualifies = $position !== null
                        && $instance->titlePresentation !== null
                        && $instance->titlePresentation->defense->activePanelAssignments()
                            ->where('user_id', $actor->id)
                            ->where('panel_position', $position)
                            ->exists();
                }

                if (! $qualifies) {
                    continue;
                }

                $alreadySigned = OfficialFormSignature::query()
                    ->where('official_form_version_id', $currentVersion->id)
                    ->where('signer_user_id', $actor->id)
                    ->where('academic_action', $action)
                    ->exists();

                if ($alreadySigned) {
                    continue;
                }

                $snapshotUuid = (string) Str::uuid();
                $snapshotPath = "official_form_signatures/{$instance->id}/v{$currentVersion->version_number}_{$actor->id}_{$snapshotUuid}.png";
                Storage::disk($disk)->put($snapshotPath, $specimenBytes);

                $payloadHash = $this->hasher->hashVersion($currentVersion);
                $sigHash = hash('sha256', (string) $specimenBytes);
                $secretKey = config('signatures.verification_key');
                $keyVersion = config('signatures.verification_key_version', 'v1');
                $signedAt = now();

                $attestationHash = $this->hasher->calculateHmac([
                    'instance_id' => (int) $instance->id,
                    'version_id' => (int) $currentVersion->id,
                    'version_number' => (int) $currentVersion->version_number,
                    'signer_user_id' => (int) $actor->id,
                    'actor_type' => $actorType,
                    'academic_action' => $action,
                    'version_payload_sha256' => $payloadHash,
                    'signature_sha256' => $sigHash,
                    'key_version' => (string) $keyVersion,
                    'signed_at' => $signedAt->toIso8601String(),
                ], (string) $secretKey);

                $dualSig = OfficialFormSignature::query()->create([
                    'official_form_instance_id' => $instance->id,
                    'official_form_version_id' => $currentVersion->id,
                    'signer_user_id' => $actor->id,
                    'user_signature_id' => $specimen->id,
                    'actor_type' => $actorType,
                    'academic_action' => $action,
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

                AuditLog::query()->create([
                    'user_id' => $actor->id,
                    'actor_name' => $actor->name,
                    'actor_email' => $actor->email,
                    'event' => 'official_form.signature_applied',
                    'auditable_type' => OfficialFormInstance::class,
                    'auditable_id' => $instance->id,
                    'description' => "Auto-applied co-assigned digital signature for {$action} ({$actorType}) on form {$instance->definition->code} v{$currentVersion->version_number}.",
                    'subject_snapshot' => [
                        'official_form_signature_id' => $dualSig->id,
                        'academic_action' => $action,
                        'actor_type' => $actorType,
                    ],
                ]);

                $this->advanceRes026AfterSignature($instance, $dualSig, $actor);
            }
        }
    }

    private function advanceRes026AfterSignature(OfficialFormInstance $instance, OfficialFormSignature $signature, User $actor): void
    {
        $presentation = TitlePresentation::query()->lockForUpdate()->where('official_form_instance_id', $instance->id)->firstOrFail();

        if (in_array($signature->academic_action, ['sign_chairperson', 'sign_member_1', 'sign_member_2'], true)) {
            $required = ['sign_chairperson', 'sign_member_1', 'sign_member_2'];
            $signed = OfficialFormSignature::query()
                ->where('official_form_version_id', $presentation->official_form_version_id)
                ->whereIn('academic_action', $required)
                ->distinct()
                ->pluck('academic_action')
                ->all();
            if (count(array_intersect($required, $signed)) === 3) {
                $coordinatorSigned = OfficialFormSignature::query()
                    ->where('official_form_version_id', $presentation->official_form_version_id)
                    ->where('academic_action', 'endorse')
                    ->exists();

                $nextPresStatus = $coordinatorSigned ? 'awaiting_dean' : 'awaiting_program_coordinator';
                $presentation->update(['status' => $nextPresStatus]);
                if ($coordinatorSigned) {
                    $instance->update(['status' => 'endorsed']);
                }
            }
            $event = 'RES026_PANEL_SIGNED';
        } elseif ($signature->academic_action === 'endorse') {
            $required = ['sign_chairperson', 'sign_member_1', 'sign_member_2'];
            $signed = OfficialFormSignature::query()
                ->where('official_form_version_id', $presentation->official_form_version_id)
                ->whereIn('academic_action', $required)
                ->distinct()
                ->pluck('academic_action')
                ->all();

            if (count(array_intersect($required, $signed)) === 3) {
                $presentation->update(['status' => 'awaiting_dean']);
                $instance->update(['status' => 'endorsed']);
            }
            $event = 'RES026_COORDINATOR_ACTION';
        } elseif ($signature->academic_action === 'approve') {
            $this->finalizeCanonicalTitle($presentation, $actor);
            $instance->update(['status' => 'approved']);
            $presentation->update(['status' => 'approved']);
            $event = 'RES026_DEAN_ACTION';
        } else {
            return;
        }

        AuditLog::query()->create([
            'user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'event' => $event,
            'auditable_type' => TitlePresentation::class,
            'auditable_id' => $presentation->id,
            'description' => "Recorded {$signature->academic_action} digital signature on RES-026.",
            'subject_snapshot' => ['academic_actor_type' => $signature->actor_type, 'official_form_signature_id' => $signature->id],
        ]);
    }

    private function finalizeCanonicalTitle(TitlePresentation $presentation, User $actor): void
    {
        if ($presentation->status !== 'awaiting_dean' || $presentation->approved_title_number === null) {
            throw new InvalidArgumentException('RES-026 is not ready for final Dean approval.');
        }

        $version = $presentation->formVersion()->lockForUpdate()->firstOrFail();
        $topics = array_values($version->payload['topics'] ?? []);
        $approvedTitle = trim((string) ($topics[$presentation->approved_title_number - 1] ?? ''));
        if ($approvedTitle === '') {
            throw new InvalidArgumentException('The approved title cannot be derived from the exact submitted RES-026 version.');
        }

        $group = ResearchClassGroup::query()->lockForUpdate()->findOrFail($presentation->defense->research_class_group_id);
        $group = $this->ensureCanonicalResearchGroup->handle($group, $actor);

        $project = DB::table('research_projects')->where('research_group_id', $group->research_group_id)->lockForUpdate()->first();
        if ($project === null) {
            DB::table('research_projects')->insert([
                'research_group_id' => $group->research_group_id,
                'title' => $approvedTitle,
                'status' => 'approved',
                'created_by' => $actor->id,
                'approved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('research_projects')->where('id', $project->id)->update([
                'title' => $approvedTitle,
                'status' => 'approved',
                'approved_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $presentation->update(['status' => 'finalized', 'finalized_at' => now(), 'finalized_by' => $actor->id]);

        $this->synchronizeMilestone->complete(
            $group,
            'research-title-presentation',
            $actor,
            'official_form',
            $presentation->official_form_instance_id,
            'Finalized RES-026 Research Title Approval with all required digital signatures.',
        );

        AuditLog::query()->create([
            'user_id' => $actor->id, 'actor_name' => $actor->name, 'actor_email' => $actor->email,
            'event' => 'CANONICAL_TITLE_FINALIZED', 'auditable_type' => TitlePresentation::class, 'auditable_id' => $presentation->id,
            'description' => 'Finalized the canonical research title from the exact approved RES-026 title option.',
            'subject_snapshot' => ['academic_actor_type' => 'dean', 'approved_title_number' => $presentation->approved_title_number, 'official_form_version_id' => $version->id],
        ]);
    }
}
