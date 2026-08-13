<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormActorAssignment;
use App\Models\OfficialFormInstance;
use App\Models\User;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeactivateOfficialFormActor
{
    public function __construct(
        private readonly OfficialFormAuthorization $authorization = new OfficialFormAuthorization
    ) {}

    public function handle(User $actor, OfficialFormInstance $instance, OfficialFormActorAssignment $assignment): void
    {
        if ((int) $assignment->official_form_instance_id !== (int) $instance->id) {
            throw new InvalidArgumentException('The actor assignment does not belong to this form instance.');
        }

        if (! $this->authorization->canAssignActor($actor, $instance)) {
            throw new InvalidArgumentException("User #{$actor->id} is not authorized to manage actors for form instance #{$instance->id}.");
        }

        DB::transaction(function () use ($actor, $instance, $assignment): void {
            $locked = OfficialFormActorAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            if ($locked->status !== 'active') {
                throw new InvalidArgumentException('The form actor assignment is already inactive.');
            }

            $locked->update(['status' => 'inactive']);

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'official_form.actor_deactivated',
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $instance->id,
                'description' => "Deactivated {$locked->actor_type} assignment for user #{$locked->user_id} on form instance #{$instance->id}.",
                'subject_snapshot' => [
                    'actor_user_id' => $locked->user_id,
                    'actor_type' => $locked->actor_type,
                    'actor_function' => $locked->actor_type,
                    'old_status' => 'active',
                    'new_status' => 'inactive',
                ],
            ]);
        });
    }
}
