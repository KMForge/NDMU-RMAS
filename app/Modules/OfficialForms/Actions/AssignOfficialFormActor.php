<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormActorAssignment;
use App\Models\OfficialFormInstance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssignOfficialFormActor
{
    /** @var list<string> */
    public const ALLOWED_ACTOR_TYPES = [
        'adviser',
        'panelist',
        'language_editor',
        'technical_editor',
        'instrument_validator',
        'research_instructor',
        'program_coordinator',
        'dean',
        'consultant',
    ];

    public function handle(
        User $assigner,
        OfficialFormInstance $instance,
        int $userId,
        string $actorType
    ): OfficialFormActorAssignment {
        if (! in_array($actorType, self::ALLOWED_ACTOR_TYPES, true)) {
            throw new InvalidArgumentException("Invalid form actor type [{$actorType}].");
        }

        $user = User::query()->findOrFail($userId);

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
