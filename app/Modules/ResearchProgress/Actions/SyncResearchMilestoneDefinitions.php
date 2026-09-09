<?php

namespace App\Modules\ResearchProgress\Actions;

use App\Models\MilestoneDefinition;
use App\Models\ResearchClassGroup;
use App\Models\ResearchGroupMilestone;
use Illuminate\Support\Facades\DB;

class SyncResearchMilestoneDefinitions
{
    public function execute(): void
    {
        $canonical = collect(config('research-progress.milestones', []));

        if ($canonical->isEmpty()) {
            throw new \UnexpectedValueException('Canonical research milestones configuration cannot be empty.');
        }

        DB::transaction(function () use ($canonical): void {
            $canonicalCodes = $canonical->pluck('code')->all();

            // 1. Mark non-canonical definitions as inactive and shift sequences out of range
            $inactiveDefinitions = MilestoneDefinition::query()
                ->whereNotIn('code', $canonicalCodes)
                ->get();

            foreach ($inactiveDefinitions as $inactive) {
                $inactive->update([
                    'is_active' => false,
                    'sequence' => 1000 + $inactive->id,
                ]);
            }

            // Temporarily move existing canonical sequences so inserting a stage
            // between them cannot collide with the unique sequence constraint.
            MilestoneDefinition::query()
                ->whereIn('code', $canonicalCodes)
                ->get()
                ->each(fn (MilestoneDefinition $definition) => $definition->update([
                    'sequence' => 2000 + $definition->getKey(),
                ]));

            // 2. Upsert canonical active definitions
            foreach ($canonical as $item) {
                MilestoneDefinition::query()->updateOrCreate(
                    ['code' => $item['code']],
                    [
                        'name' => $item['name'],
                        'sequence' => $item['sequence'],
                        'weight' => $item['weight'] ?? 1,
                        'is_active' => true,
                    ]
                );
            }

            // 3. Process legacy group milestones under inactive definitions
            $inactiveDefinitionIds = MilestoneDefinition::query()
                ->where('is_active', false)
                ->pluck('id');

            if ($inactiveDefinitionIds->isNotEmpty()) {
                $legacyMilestones = ResearchGroupMilestone::query()
                    ->whereIn('milestone_definition_id', $inactiveDefinitionIds)
                    ->get();

                foreach ($legacyMilestones as $legacy) {
                    $hasEvents = DB::table('research_group_milestone_events')
                        ->where('research_group_milestone_id', $legacy->id)
                        ->exists();

                    $hasEvidence = DB::table('milestone_evidences')
                        ->where('research_group_milestone_id', $legacy->id)
                        ->exists();

                    $isPurePlaceholder = $legacy->status->value === 'pending'
                        && $legacy->started_at === null
                        && $legacy->completed_at === null
                        && $legacy->due_at === null
                        && ($legacy->remarks === null || trim((string) $legacy->remarks) === '')
                        && $legacy->not_applicable_reason === null
                        && ! $hasEvents
                        && ! $hasEvidence;

                    if ($isPurePlaceholder) {
                        $legacy->delete();
                    }
                    // Else: PRESERVE intact under inactive definition without modification
                }
            }

            // 4. Ensure active research groups have all canonical active group milestones
            $activeDefinitions = MilestoneDefinition::query()
                ->where('is_active', true)
                ->get();

            $activeGroupIds = ResearchClassGroup::query()->pluck('id');

            foreach ($activeGroupIds as $groupId) {
                foreach ($activeDefinitions as $definition) {
                    ResearchGroupMilestone::query()->firstOrCreate([
                        'research_class_group_id' => $groupId,
                        'milestone_definition_id' => $definition->id,
                    ]);
                }
            }
        }, 3);
    }
}
