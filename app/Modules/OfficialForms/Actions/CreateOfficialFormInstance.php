<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\OfficialFormVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateOfficialFormInstance
{
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
        array $payload = [],
    ): OfficialFormInstance {
        $definition = OfficialFormDefinition::query()
            ->where('code', $formCode)
            ->where('is_active', true)
            ->firstOrFail();

        if ($definition->ownership_scope === 'research_group' && $groupId === null) {
            throw new InvalidArgumentException("Form {$formCode} requires a research_class_group_id.");
        }

        if ($definition->ownership_scope === 'research_class' && $classId === null) {
            throw new InvalidArgumentException("Form {$formCode} requires a research_class_id.");
        }

        return DB::transaction(function () use ($definition, $initiator, $groupId, $classId, $contextKey, $sourceType, $sourceId, $payload) {
            $this->validateCardinality($definition, $groupId, $classId, $contextKey, $initiator->id);

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

            AuditLog::query()->create([
                'user_id' => $initiator->id,
                'actor_name' => $initiator->name,
                'actor_email' => $initiator->email,
                'event' => 'official_form.created',
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $instance->id,
                'description' => "Created official form instance {$definition->code} (v1).",
            ]);

            return $instance->load(['definition', 'currentVersion']);
        });
    }

    private function validateCardinality(
        OfficialFormDefinition $definition,
        ?int $groupId,
        ?int $classId,
        string $contextKey,
        int $initiatorId
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
                ->where('initiated_by', $initiatorId)
                ->exists();
            if ($exists) {
                throw new InvalidArgumentException("Form {$definition->code} already exists for this actor in context ({$contextKey}).");
            }
        }
    }
}
