<?php

namespace App\Modules\ResearchProgress\Actions;

use App\Models\ResearchGroupMilestone;
use App\Models\ResearchGroupMilestoneEvent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateMilestoneDueDate
{
    public function execute(User $actor, ResearchGroupMilestone $milestone, ?string $dueAt, ?string $reason, ?string $ipAddress): ResearchGroupMilestone
    {
        return DB::transaction(function () use ($actor, $milestone, $dueAt, $reason, $ipAddress): ResearchGroupMilestone {
            $locked = ResearchGroupMilestone::query()->with(['definition', 'group.researchClass'])->whereKey($milestone->getKey())->lockForUpdate()->firstOrFail();
            if (! $actor->can('manage', $locked)) {
                throw new AuthorizationException;
            }

            $old = $locked->due_at?->toAtomString();
            $new = $dueAt === null ? null : Carbon::parse($dueAt)->endOfDay();
            $locked->update(['due_at' => $new, 'updated_by' => $actor->getKey()]);
            ResearchGroupMilestoneEvent::query()->create([
                'research_group_milestone_id' => $locked->getKey(), 'actor_id' => $actor->getKey(),
                'event' => 'due_date_changed', 'from_status' => $locked->status, 'to_status' => $locked->status,
                'reason' => $this->clean($reason), 'old_values' => ['due_at' => $old],
                'new_values' => ['due_at' => $new?->toAtomString()], 'override_order' => false,
                'ip_address' => $ipAddress, 'occurred_at' => now(),
            ]);

            return $locked->fresh(['definition', 'evidences', 'events.actor:id,name,email']);
        }, 3);
    }

    private function clean(?string $value): ?string
    {
        $value = trim(strip_tags((string) $value));

        return $value === '' ? null : mb_substr($value, 0, 2000);
    }
}
