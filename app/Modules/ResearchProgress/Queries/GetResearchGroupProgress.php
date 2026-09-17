<?php

namespace App\Modules\ResearchProgress\Queries;

use App\Models\ResearchClassGroup;
use App\Models\ResearchGroupMilestone;
use App\Models\User;
use App\Modules\ResearchProgress\Actions\InitializeGroupMilestones;
use App\Modules\ResearchProgress\Services\ResearchJourneyService;
use Illuminate\Support\Collection;

class GetResearchGroupProgress
{
    public function __construct(
        private readonly InitializeGroupMilestones $initialize,
        private readonly ResearchJourneyService $journey,
    ) {}

    /** @return array<string, mixed> */
    public function for(ResearchClassGroup $group, ?User $actor = null): array
    {
        $milestones = $group->isActive()
            ? $this->initialize->execute($group)
            : $this->existingFor($group);

        return $this->fromLoaded($group, $milestones, $actor);
    }

    /**
     * Build a progress summary from an already eager-loaded milestone collection.
     *
     * @param  Collection<int, ResearchGroupMilestone>  $milestones
     * @return array<string, mixed>
     */
    public function fromLoaded(ResearchClassGroup $group, Collection $milestones, ?User $actor = null): array
    {
        $milestones = $milestones->sortBy(fn (ResearchGroupMilestone $milestone): int => $milestone->definition->sequence)->values();
        $activeMilestones = $milestones->filter(fn ($milestone): bool => $milestone->definition->is_active);

        if ($activeMilestones->contains(fn ($milestone): bool => (float) $milestone->definition->weight <= 0)) {
            throw new \UnexpectedValueException('Active research milestone weights must be positive.');
        }

        // This is the single authoritative backend for every progress consumer.
        // It includes persisted milestones plus verified forms/documents/defenses,
        // inferred stages, optional stages, and curriculum auto-completion.
        $journey = $this->journey->getJourneyForGroup($group, $actor);
        $journeyStages = collect($journey['stages'] ?? []);
        $requiredStageCount = (int) ($journey['required_stage_count'] ?? $journeyStages
            ->reject(fn (array $stage): bool => ($stage['is_optional'] ?? false) || ($stage['is_not_applicable'] ?? false))
            ->count());
        $completedStageCount = (int) ($journey['completed_stage_count'] ?? $journeyStages
            ->filter(fn (array $stage): bool => ($stage['is_completed'] ?? false)
                && ! ($stage['is_optional'] ?? false)
                && ! ($stage['is_not_applicable'] ?? false))
            ->count());
        $current = $milestones->first(
            fn (ResearchGroupMilestone $milestone): bool => $milestone->definition->sequence === $journey['current_stage']
        );

        return [
            'group' => $group->loadMissing(['researchClass:id,name,facilitator_id', 'adviser:id,name,email']),
            'milestones' => $milestones,
            'progress_percentage' => $journey['percentage'],
            'completed_count' => $completedStageCount,
            'applicable_count' => $requiredStageCount,
            'current_milestone' => $current,
            'journey' => $journey,
        ];
    }

    /** @return Collection<int, ResearchGroupMilestone> */
    private function existingFor(ResearchClassGroup $group): Collection
    {
        return $group->milestones()
            ->with(['definition', 'evidences', 'events.actor:id,name,email'])
            ->join('milestone_definitions', 'milestone_definitions.id', '=', 'research_group_milestones.milestone_definition_id')
            ->orderBy('milestone_definitions.sequence')
            ->select('research_group_milestones.*')
            ->get();
    }
}
