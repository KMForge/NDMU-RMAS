<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReplaceCurrentAdviser
{
    public function handle(OfficialFormInstance $instance, User $incomingAdviser, User $authorizedBy): void
    {
        $code = strtoupper($instance->definition->code ?? '');
        if ($code !== 'RES-030') {
            throw new InvalidArgumentException("ReplaceCurrentAdviser requires RES-030 instance, given {$code}.");
        }

        DB::transaction(function () use ($instance, $incomingAdviser, $authorizedBy) {
            /** @var ResearchClassGroup $group */
            $group = ResearchClassGroup::query()->lockForUpdate()->findOrFail($instance->research_class_group_id);

            $oldAdviserId = $group->adviser_id;

            // End active adviser history
            ResearchClassGroupAdviserHistory::query()
                ->where('research_class_group_id', $group->id)
                ->whereNull('ended_at')
                ->update([
                    'ended_at' => now(),
                    'ended_by' => $authorizedBy->id,
                ]);

            // Set new adviser on group
            $group->update(['adviser_id' => $incomingAdviser->id]);

            // Create new active adviser history entry
            ResearchClassGroupAdviserHistory::query()->create([
                'research_class_group_id' => $group->id,
                'adviser_id' => $incomingAdviser->id,
                'assigned_by' => $authorizedBy->id,
                'assigned_at' => now(),
            ]);

            AuditLog::query()->create([
                'user_id' => $authorizedBy->id,
                'actor_name' => $authorizedBy->name,
                'actor_email' => $authorizedBy->email,
                'event' => 'adviser.replaced_via_res030',
                'auditable_type' => ResearchClassGroup::class,
                'auditable_id' => $group->id,
                'description' => "Replaced adviser for research group #{$group->id} (Old: #{$oldAdviserId}, New: #{$incomingAdviser->id}) via RES-030 approval by {$authorizedBy->name}.",
            ]);
        });
    }
}
