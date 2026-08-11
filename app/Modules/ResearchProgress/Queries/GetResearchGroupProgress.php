<?php

namespace App\Modules\ResearchProgress\Queries;

use App\Enums\ResearchMilestoneStatus;
use App\Models\ResearchClassGroup;
use App\Models\ResearchGroupMilestone;
use App\Modules\ResearchProgress\Actions\InitializeGroupMilestones;
use Illuminate\Support\Collection;

class GetResearchGroupProgress
{
    public function __construct(private readonly InitializeGroupMilestones $initialize) {}

    /** @return array<string, mixed> */
    public function for(ResearchClassGroup $group): array
    {
        $milestones = $group->isActive()
            ? $this->initialize->execute($group)
            : $this->existingFor($group);

        return $this->fromLoaded($group, $milestones);
    }

    /**
     * Build a progress summary from an already eager-loaded milestone collection.
     *
     * @param  Collection<int, ResearchGroupMilestone>  $milestones
     * @return array<string, mixed>
     */
    public function fromLoaded(ResearchClassGroup $group, Collection $milestones): array
    {
        $milestones = $milestones->sortBy(fn (ResearchGroupMilestone $milestone): int => $milestone->definition->sequence)->values();

        $applicable = $milestones->filter(fn ($milestone): bool => $milestone->definition->is_active
            && $milestone->status !== ResearchMilestoneStatus::NotApplicable);

        if ($applicable->contains(fn ($milestone): bool => (float) $milestone->definition->weight <= 0)) {
            throw new \UnexpectedValueException('Active research milestone weights must be positive.');
        }
        $denominator = (float) $applicable->sum(fn ($milestone): float => (float) $milestone->definition->weight);
        $completedWeight = (float) $applicable
            ->filter(fn ($milestone): bool => $milestone->status === ResearchMilestoneStatus::Completed)
            ->sum(fn ($milestone): float => (float) $milestone->definition->weight);
        $percentage = $denominator > 0 ? round(($completedWeight / $denominator) * 100, 2) : 0.0;
        $current = $applicable->first(fn ($milestone): bool => $milestone->status !== ResearchMilestoneStatus::Completed);

        return [
            'group' => $group->loadMissing(['researchClass:id,name,facilitator_id', 'adviser:id,name,email']),
            'milestones' => $milestones,
            'progress_percentage' => $percentage,
            'completed_count' => $applicable->where('status', ResearchMilestoneStatus::Completed)->count(),
            'applicable_count' => $applicable->count(),
            'current_milestone' => $current,
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
