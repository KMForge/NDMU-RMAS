<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormActorAssignment;
use App\Models\OfficialFormInstance;
use App\Models\User;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssignOfficialFormActor
{
    public function __construct(
        private readonly OfficialFormAuthorization $authorization = new OfficialFormAuthorization
    ) {}

    /** @var array<string, list<string>> */
    public const FORM_ALLOWED_ACTOR_TYPES = [
        'RES-027' => ['adviser'],
        'RES-028' => ['panelist'],
        'RES-029' => ['language_editor'],
        'RES-030' => ['adviser', 'panelist', 'language_editor'],
        'RES-032' => ['consultant', 'language_editor', 'technical_editor', 'instrument_validator', 'panelist'],
        'RES-034' => ['adviser', 'panelist'],
        'RES-035' => ['adviser', 'panelist'],
        'RES-036' => ['panelist'],
        'RES-037' => ['panelist'],
        'RES-038' => ['adviser'],
        'RES-040' => ['research_instructor'],
        'RES-041' => ['research_instructor', 'program_coordinator'],
        'RES-042' => ['instrument_validator'],
        'RES-043A' => ['instrument_validator'],
        'RES-043B' => ['instrument_validator'],
        'RES-044' => ['adviser', 'panelist'],
        'RES-045' => ['language_editor'],
        'RES-046' => ['technical_editor'],
        'RES-047' => ['dean', 'program_coordinator'],
    ];

    public function handle(
        User $assigner,
        OfficialFormInstance $instance,
        int $userId,
        string $actorType
    ): OfficialFormActorAssignment {
        if (! $this->authorization->canAssignActor($assigner, $instance)) {
            throw new InvalidArgumentException("User #{$assigner->id} is not authorized to assign actors for form instance #{$instance->id}.");
        }

        $formCode = strtoupper($instance->definition->code);

        if (! isset(self::FORM_ALLOWED_ACTOR_TYPES[$formCode])) {
            throw new InvalidArgumentException("Actor assignment is not configured for form {$formCode}.");
        }

        $allowedTypes = self::FORM_ALLOWED_ACTOR_TYPES[$formCode];

        if (! in_array($actorType, $allowedTypes, true)) {
            throw new InvalidArgumentException("Actor type [{$actorType}] is not valid for form {$formCode}.");
        }

        $user = User::query()->findOrFail($userId);

        $userTypeVal = is_object($user->user_type) ? ($user->user_type->value ?? (string) $user->user_type) : (string) $user->user_type;
        if (in_array($actorType, ['adviser', 'panelist', 'language_editor', 'technical_editor', 'instrument_validator', 'research_instructor', 'program_coordinator', 'dean'], true)) {
            if ($userTypeVal !== 'faculty' && ! $user->can('users.manage')) {
                throw new InvalidArgumentException("Specialist actor type [{$actorType}] requires a faculty user.");
            }
        }

        return DB::transaction(function () use ($assigner, $instance, $user, $actorType) {
            $assignment = OfficialFormActorAssignment::query()->updateOrCreate(
                [
                    'official_form_instance_id' => $instance->id,
                    'actor_type' => $actorType,
                    'user_id' => $user->id,
                ],
                [
                    'assigned_by' => $assigner->id,
                    'assigned_at' => now(),
                    'status' => 'active',
                ]
            );

            AuditLog::query()->create([
                'user_id' => $assigner->id,
                'actor_name' => $assigner->name,
                'actor_email' => $assigner->email,
                'event' => 'official_form.actor_assigned',
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $instance->id,
                'description' => "Assigned {$user->name} as {$actorType} on form instance #{$instance->id}.",
            ]);

            return $assignment;
        });
    }
}
