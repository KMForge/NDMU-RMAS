<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\ConsultationRecord;
use App\Models\DocumentReview;
use App\Models\OfficialFormActorAssignment;
use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\OfficialFormVersion;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\RevisionRequest;
use App\Models\User;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateOfficialFormInstance
{
    public function __construct(
        private readonly OfficialFormAuthorization $authorization = new OfficialFormAuthorization
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(
        User $initiator,
        string $formCode,
        ?int $groupId = null,
        ?int $classId = null,
        string $contextKey = 'general',
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?int $actorUserId = null,
        array $payload = [],
    ): OfficialFormInstance {
        $formCodeUpper = strtoupper($formCode);

        // Block RES-036 and RES-037 pending Phase 21/22 Defense Panel Assignment sources
        if ($formCodeUpper === 'RES-036') {
            throw new InvalidArgumentException('RES-036 is blocked pending the authoritative Defense Panel Assignment source from Phase 21.');
        }
        if ($formCodeUpper === 'RES-037') {
            throw new InvalidArgumentException('RES-037 is blocked pending the authoritative Defense Panel/Evaluation source from Phase 21/22.');
        }

        $definition = OfficialFormDefinition::query()
            ->where('code', $formCodeUpper)
            ->where('is_active', true)
            ->firstOrFail();

        $group = $groupId !== null ? ResearchClassGroup::query()->find($groupId) : null;
        $class = $classId !== null ? ResearchClass::query()->find($classId) : null;

        $targetActorId = $actorUserId ?? $initiator->id;

        // Authoritative Source Enforcements per form
        if (in_array($formCodeUpper, ['RES-043A', 'RES-043B'], true)) {
            $targetActorId = $this->validateValidationRequestSourceAndValidator($groupId, $sourceType, $sourceId, $targetActorId);
        }

        if (! $this->authorization->canInitiate($initiator, $definition, $group, $class)) {
            throw new InvalidArgumentException("User #{$initiator->id} is not authorized to initiate form {$definition->code}.");
        }

        $this->validateOwnershipScope($definition, $groupId, $classId);

        return DB::transaction(function () use ($definition, $initiator, $groupId, $classId, $contextKey, $sourceType, $sourceId, $targetActorId, $payload) {
            // Lock owner record for update to prevent concurrent duplicate creation
            if ($groupId !== null) {
                ResearchClassGroup::query()->lockForUpdate()->find($groupId);
            } elseif ($classId !== null) {
                ResearchClass::query()->lockForUpdate()->find($classId);
            }

            $this->validateSourceLinkage($groupId, $sourceType, $sourceId);
            $this->validateCardinality($definition, $groupId, $classId, $contextKey, $targetActorId, $sourceId);

            $instance = OfficialFormInstance::query()->create([
                'official_form_definition_id' => $definition->id,
                'research_class_group_id' => $groupId,
                'research_class_id' => $classId,
                'context_key' => $contextKey,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'initiated_by' => $initiator->id,
                'status' => 'draft',
            ]);

            $version = OfficialFormVersion::query()->create([
                'official_form_instance_id' => $instance->id,
                'version_number' => 1,
                'payload' => $payload,
                'created_by' => $initiator->id,
                'is_current' => true,
            ]);

            $instance->update(['current_version_id' => $version->id]);

            // For per_actor cardinality forms, mirror the verified target actor assignment atomically
            if ($definition->cardinality === 'per_actor') {
                $actorType = AssignOfficialFormActor::FORM_ALLOWED_ACTOR_TYPES[strtoupper($definition->code)][0] ?? 'consultant';
                OfficialFormActorAssignment::query()->updateOrCreate(
                    [
                        'official_form_instance_id' => $instance->id,
                        'actor_type' => $actorType,
                        'user_id' => $targetActorId,
                    ],
                    [
                        'assigned_by' => $initiator->id,
                        'assigned_at' => now(),
                        'status' => 'active',
                    ]
                );
            }

            AuditLog::query()->create([
                'user_id' => $initiator->id,
                'actor_name' => $initiator->name,
                'actor_email' => $initiator->email,
                'event' => 'official_form.created',
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $instance->id,
                'description' => "Created official form instance {$definition->code} (v1).",
            ]);

            return $instance->load(['definition', 'currentVersion', 'actorAssignments']);
        });
    }

    private function validateOwnershipScope(OfficialFormDefinition $definition, ?int $groupId, ?int $classId): void
    {
        if ($definition->ownership_scope === 'research_group') {
            if ($groupId === null) {
                throw new InvalidArgumentException("Form {$definition->code} requires a research_class_group_id.");
            }
            if ($classId !== null) {
                $group = ResearchClassGroup::query()->find($groupId);
                if ($group !== null && (int) $group->research_class_id !== (int) $classId) {
                    throw new InvalidArgumentException('Mismatch between research_class_id and research_class_group_id.');
                }
            }
        } elseif ($definition->ownership_scope === 'research_class') {
            if ($classId === null) {
                throw new InvalidArgumentException("Form {$definition->code} requires a research_class_id.");
            }
            if ($groupId !== null) {
                throw new InvalidArgumentException("Class-owned form {$definition->code} must not specify a research_class_group_id.");
            }
        }
    }

    private function validateValidationRequestSourceAndValidator(?int $groupId, ?string $sourceType, ?int $sourceId, int $targetActorId): int
    {
        if ($sourceType !== OfficialFormInstance::class || $sourceId === null) {
            throw new InvalidArgumentException('RES-043A/B validation rating requires an authoritative RES-042 validation request source.');
        }

        $sourceForm = OfficialFormInstance::query()->find($sourceId);
        if (! $sourceForm || strtoupper($sourceForm->definition->code) !== 'RES-042') {
            throw new InvalidArgumentException('Source form instance must be an official RES-042 validation request.');
        }

        if ($groupId !== null && (int) $sourceForm->research_class_group_id !== (int) $groupId) {
            throw new InvalidArgumentException('Source RES-042 validation request does not belong to the specified research group.');
        }

        // Require pre-existing instrument_validator actor assignment on the source RES-042 request instance
        $isAssigned = $sourceForm->actorAssignments()
            ->where('user_id', $targetActorId)
            ->where('actor_type', 'instrument_validator')
            ->where('status', 'active')
            ->exists();

        if (! $isAssigned) {
            throw new InvalidArgumentException("Target user #{$targetActorId} is not an assigned instrument validator for the source RES-042 validation request.");
        }

        return $targetActorId;
    }

    private function validateEditorAssignment(?int $groupId, int $targetActorId, string $requiredActorType): void
    {
        if ($groupId === null) {
            return;
        }

        $group = ResearchClassGroup::query()->find($groupId);
        if (! $group) {
            throw new InvalidArgumentException('Target research group does not exist.');
        }

        $hasAssignment = OfficialFormInstance::query()
            ->where('research_class_group_id', $groupId)
            ->whereHas('actorAssignments', fn ($q) => $q->where('user_id', $targetActorId)->where('actor_type', $requiredActorType)->where('status', 'active'))
            ->exists();

        if (! $hasAssignment) {
            throw new InvalidArgumentException("Target user #{$targetActorId} does not have an active {$requiredActorType} assignment for this research group.");
        }
    }

    private function validateSourceLinkage(?int $groupId, ?string $sourceType, ?int $sourceId): void
    {
        if ($sourceType === null || $sourceId === null) {
            return;
        }

        if ($sourceType === ConsultationRecord::class) {
            $record = ConsultationRecord::query()->find($sourceId);
            if (! $record || ($groupId !== null && (int) $record->research_class_group_id !== (int) $groupId)) {
                throw new InvalidArgumentException('Source ConsultationRecord does not belong to the specified group.');
            }
        } elseif ($sourceType === DocumentReview::class) {
            $review = DocumentReview::query()->find($sourceId);
            if (! $review || ($groupId !== null && (int) $review->research_class_group_id !== (int) $groupId)) {
                throw new InvalidArgumentException('Source DocumentReview does not belong to the specified group.');
            }
        } elseif ($sourceType === RevisionRequest::class) {
            $request = RevisionRequest::query()->find($sourceId);
            if (! $request || ($groupId !== null && (int) $request->research_class_group_id !== (int) $groupId)) {
                throw new InvalidArgumentException('Source RevisionRequest does not belong to the specified group.');
            }
        }
    }

    private function validateCardinality(
        OfficialFormDefinition $definition,
        ?int $groupId,
        ?int $classId,
        string $contextKey,
        int $targetActorId,
        ?int $sourceId
    ): void {
        $query = OfficialFormInstance::query()
            ->where('official_form_definition_id', $definition->id);

        if ($definition->cardinality === 'single_per_group' && $groupId !== null) {
            $exists = (clone $query)->where('research_class_group_id', $groupId)->exists();
            if ($exists) {
                throw new InvalidArgumentException("Form {$definition->code} already exists for this research group.");
            }
        } elseif ($definition->cardinality === 'single_per_context' && $groupId !== null) {
            $exists = (clone $query)
                ->where('research_class_group_id', $groupId)
                ->where('context_key', $contextKey)
                ->exists();
            if ($exists) {
                throw new InvalidArgumentException("Form {$definition->code} already exists for this group context ({$contextKey}).");
            }
        } elseif ($definition->cardinality === 'per_actor' && $groupId !== null) {
            $exists = (clone $query)
                ->where('research_class_group_id', $groupId)
                ->where('context_key', $contextKey)
                ->whereHas('actorAssignments', function ($aq) use ($targetActorId) {
                    $aq->where('user_id', $targetActorId)->where('status', 'active');
                })
                ->when($sourceId !== null, fn ($q) => $q->where('source_id', $sourceId))
                ->exists();
            if ($exists) {
                throw new InvalidArgumentException("Form {$definition->code} already exists for this actor user in context ({$contextKey}).");
            }
        }
    }
}
