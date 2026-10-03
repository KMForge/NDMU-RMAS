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
        $activeDefinitionIds = MilestoneDefinition::query()
            ->where('is_active', true)
            ->orderBy('sequence')
            ->pluck('id');

        if ($activeDefinitionIds->isEmpty()) {
            throw new \UnexpectedValueException('No active research milestone definitions available for group initialization.');
        }

        $existingDefinitionIds = ResearchGroupMilestone::query()
            ->where('research_class_group_id', $group->getKey())
            ->whereIn('milestone_definition_id', $activeDefinitionIds)
            ->pluck('milestone_definition_id');
        $missingDefinitionIds = $activeDefinitionIds->diff($existingDefinitionIds);

        if ($missingDefinitionIds->isNotEmpty()) {
            DB::transaction(function () use ($group, $missingDefinitionIds): void {
                ResearchClassGroup::query()->whereKey($group->getKey())->lockForUpdate()->firstOrFail();
                $timestamp = now();

                ResearchGroupMilestone::query()->insertOrIgnore(
                    $missingDefinitionIds
                        ->map(static fn (int $definitionId): array => [
                            'research_class_group_id' => $group->getKey(),
                            'milestone_definition_id' => $definitionId,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ])
                        ->all(),
                );
            }, 3);
        }

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
