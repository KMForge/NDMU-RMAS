<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\DefensePanelAssignment;
use App\Models\DefenseSchedule;
use App\Models\OfficialFormInstance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReplaceDefensePanelist
{
    public function handle(OfficialFormInstance $instance, User $outgoingPanelist, User $incomingPanelist, User $authorizedBy): void
    {
        $code = strtoupper($instance->definition->code ?? '');
        if ($code !== 'RES-030') {
            throw new InvalidArgumentException("ReplaceDefensePanelist requires RES-030 instance, given {$code}.");
        }

        DB::transaction(function () use ($instance, $outgoingPanelist, $incomingPanelist, $authorizedBy) {
            $schedules = DefenseSchedule::query()
                ->where('group_id', $instance->research_class_group_id)
                ->where('status', 'scheduled')
                ->get();

            foreach ($schedules as $schedule) {
                DefensePanelAssignment::query()
                    ->where('defense_id', $schedule->defense_id)
                    ->where('user_id', $outgoingPanelist->id)
                    ->whereNull('ended_at')
                    ->update([
                        'ended_at' => now(),
                    ]);

                DefensePanelAssignment::query()->create([
                    'defense_id' => $schedule->defense_id,
                    'user_id' => $incomingPanelist->id,
                    'assigned_by' => $authorizedBy->id,
                    'assigned_at' => now(),
                ]);
            }

            AuditLog::query()->create([
                'user_id' => $authorizedBy->id,
                'actor_name' => $authorizedBy->name,
                'actor_email' => $authorizedBy->email,
                'event' => 'defense_panelist.replaced_via_res030',
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $instance->id,
                'description' => "Replaced panelist #{$outgoingPanelist->id} with panelist #{$incomingPanelist->id} via RES-030 approval.",
            ]);
        });
    }
}
