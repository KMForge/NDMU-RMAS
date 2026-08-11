<?php

namespace App\Modules\ResearchProgress\Actions;

use App\Models\MilestoneDefinition;
use App\Models\ResearchClassGroup;
use App\Models\ResearchGroupMilestone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InitializeGroupMilestones
{
    /** @return Collection<int, ResearchGroupMilestone> */
    public function execute(ResearchClassGroup $group): Collection
    {
        $configured = collect(config('research-progress.milestones', []));

        if ($configured->isEmpty() || $configured->contains(fn (array $item): bool => (float) ($item['weight'] ?? 0) <= 0)) {
            throw new \UnexpectedValueException('Research milestone definitions require at least one positive weight.');
        }

        DB::transaction(function () use ($group): void {
            ResearchClassGroup::query()->whereKey($group->getKey())->lockForUpdate()->firstOrFail();

            foreach (config('research-progress.milestones', []) as $item) {
                $definition = MilestoneDefinition::query()->firstOrCreate(
                    ['code' => $item['code']],
                    [
                        'name' => $item['name'],
                        'sequence' => $item['sequence'],
                        'weight' => $item['weight'],
                        'is_active' => true,
                    ],
                );

                ResearchGroupMilestone::query()->firstOrCreate([
                    'research_class_group_id' => $group->getKey(),
                    'milestone_definition_id' => $definition->getKey(),
                ]);
            }
        }, 3);

        return ResearchGroupMilestone::query()
            ->where('research_class_group_id', $group->getKey())
            ->with(['definition', 'evidences', 'events.actor:id,name,email'])
            ->join('milestone_definitions', 'milestone_definitions.id', '=', 'research_group_milestones.milestone_definition_id')
            ->orderBy('milestone_definitions.sequence')
            ->select('research_group_milestones.*')
            ->get();
    }
}
