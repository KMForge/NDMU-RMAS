<?php

namespace App\Modules\OfficialForms\Actions;

use App\Enums\AccountStatus;
use App\Models\AuditLog;
use App\Models\ResearchClass;
use App\Models\ResearchClassActorAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssignResearchClassFormActor
{
    /** @var array<string, list<string>> */
    private const ACTOR_PERMISSIONS = [
        'research_instructor' => ['forms.res-041.fill', 'forms.res-041.endorse'],
        'program_coordinator' => ['forms.res-041.receive'],
        'program_head' => ['forms.res-030.approve', 'forms.res-033.endorse', 'forms.res-038.endorse'],
        'dean' => ['forms.res-047.approve'],
    ];

    public function handle(User $assigner, ResearchClass $class, User $actor, string $actorType): ResearchClassActorAssignment
    {
        if (! $assigner->can('users.manage') && (int) $class->facilitator_id !== (int) $assigner->id) {
            throw new InvalidArgumentException("User #{$assigner->id} cannot assign institutional actors for research class #{$class->id}.");
        }

        $permissions = self::ACTOR_PERMISSIONS[$actorType] ?? null;
        if ($permissions === null) {
            throw new InvalidArgumentException("Unsupported class actor type [{$actorType}].");
        }

        $userType = is_object($actor->user_type) ? $actor->user_type->value : $actor->user_type;
        if ($userType !== 'faculty'
            || $actor->status !== AccountStatus::Active
            || $actor->approved_at === null
            || ! collect($permissions)->contains(fn (string $permission) => $actor->hasPermissionTo($permission))) {
            throw new InvalidArgumentException("User #{$actor->id} is not eligible for class actor type [{$actorType}].");
        }

        return DB::transaction(function () use ($assigner, $class, $actor, $actorType): ResearchClassActorAssignment {
            ResearchClass::query()->lockForUpdate()->findOrFail($class->id);

            ResearchClassActorAssignment::query()
                ->where('research_class_id', $class->id)
                ->where('actor_type', $actorType)
                ->where('user_id', '!=', $actor->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->update(['status' => 'inactive']);

            $assignment = ResearchClassActorAssignment::query()->updateOrCreate(
                [
                    'research_class_id' => $class->id,
                    'user_id' => $actor->id,
                    'actor_type' => $actorType,
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
                'event' => 'official_form.class_actor_assigned',
                'auditable_type' => ResearchClass::class,
                'auditable_id' => $class->id,
                'description' => "Assigned {$actor->name} as {$actorType} for research class #{$class->id}.",
                'subject_snapshot' => [
                    'actor_user_id' => $actor->id,
                    'actor_type' => $actorType,
                    'actor_function' => $actorType,
                    'old_status' => null,
                    'new_status' => 'active',
                ],
            ]);

            return $assignment;
        });
    }
}
