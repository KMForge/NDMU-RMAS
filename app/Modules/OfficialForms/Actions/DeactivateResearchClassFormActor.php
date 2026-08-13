<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\ResearchClass;
use App\Models\ResearchClassActorAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeactivateResearchClassFormActor
{
    public function handle(User $actor, ResearchClass $class, ResearchClassActorAssignment $assignment): void
    {
        if ((int) $assignment->research_class_id !== (int) $class->getKey()) {
            throw new InvalidArgumentException('The actor assignment does not belong to this research class.');
        }

        if (! $actor->can('users.manage') && (int) $class->facilitator_id !== (int) $actor->id) {
            throw new InvalidArgumentException("User #{$actor->id} cannot manage institutional actors for research class #{$class->id}.");
        }

        DB::transaction(function () use ($actor, $class, $assignment): void {
            /** @var ResearchClassActorAssignment $locked */
            $locked = ResearchClassActorAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            if ($locked->status !== 'active') {
                throw new InvalidArgumentException('The institutional actor assignment is already inactive.');
            }

            $oldStatus = $locked->status;
            $locked->update(['status' => 'inactive']);

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'official_form.class_actor_deactivated',
                'auditable_type' => ResearchClass::class,
                'auditable_id' => $class->id,
                'description' => "Deactivated {$locked->actor_type} assignment for user #{$locked->user_id} in research class #{$class->id}.",
                'subject_snapshot' => [
                    'actor_user_id' => $locked->user_id,
                    'actor_type' => $locked->actor_type,
                    'old_status' => $oldStatus,
                    'new_status' => 'inactive',
                ],
            ]);
        });
    }
}
