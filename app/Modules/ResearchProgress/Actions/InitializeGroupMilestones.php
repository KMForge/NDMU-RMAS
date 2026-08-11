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
        DB::transaction(function () use ($group): void {
            ResearchClassGroup::query()->whereKey($group->getKey())->lockForUpdate()->firstOrFail();

            $activeDefinitions = MilestoneDefinition::query()
                ->where('is_active', true)
                ->orderBy('sequence')
                ->get();

            if ($activeDefinitions->isEmpty()) {
                throw new \UnexpectedValueException('No active research milestone definitions available for group initialization.');
            }

            foreach ($activeDefinitions as $definition) {
                ResearchGroupMilestone::query()->firstOrCreate([
                    'research_class_group_id' => $group->getKey(),
                    'milestone_definition_id' => $definition->getKey(),
                ]);
            }
        }, 3);

        return ResearchGroupMilestone::query()
            ->where('research_class_group_id', $group->getKey())
            ->whereHas('definition', fn ($q) => $q->where('is_active', true))
            ->with(['definition', 'evidences', 'events.actor:id,name,email'])
            ->join('milestone_definitions', 'milestone_definitions.id', '=', 'research_group_milestones.milestone_definition_id')
            ->orderBy('milestone_definitions.sequence')
            ->select('research_group_milestones.*')
            ->get();
    }
}
