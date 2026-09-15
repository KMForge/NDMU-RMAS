<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ActivateAdviserFromInvitation
{
    public function handle(OfficialFormInstance $instance, User $adviser): void
    {
        $code = strtoupper($instance->definition->code ?? '');
        if ($code !== 'RES-027') {
            throw new InvalidArgumentException("ActivateAdviserFromInvitation requires RES-027 instance, given {$code}.");
        }

        DB::transaction(function () use ($instance, $adviser) {
            $group = ResearchClassGroup::query()->lockForUpdate()->findOrFail($instance->research_class_group_id);

            if (! $adviser->isActiveAndApproved() || $adviser->email_verified_at === null || ! $adviser->can('classes.serve-as-adviser')) {
                throw new InvalidArgumentException('The invited adviser is not active and eligible to serve as an adviser.');
            }
            if ($group->adviser_id !== null && (int) $group->adviser_id !== (int) $adviser->id) {
                throw new InvalidArgumentException('An active adviser can only be changed through an approved RES-030 Adviser Change Request Form.');
            }
            if ((int) $group->adviser_id === (int) $adviser->id) {
                return;
            }

            $group->update(['adviser_id' => $adviser->id]);

            // Create new active adviser history record
            ResearchClassGroupAdviserHistory::query()->create([
                'research_class_group_id' => $group->id,
                'adviser_id' => $adviser->id,
                'assigned_by' => $adviser->id,
                'assigned_at' => now(),
            ]);

            AuditLog::query()->create([
                'user_id' => $adviser->id,
                'actor_name' => $adviser->name,
                'actor_email' => $adviser->email,
                'event' => 'adviser.activated_from_invitation',
                'auditable_type' => ResearchClassGroup::class,
                'auditable_id' => $group->id,
                'description' => "Adviser {$adviser->name} activated assignment for research group #{$group->id} via RES-027 conforme.",
            ]);
        });
    }
}
