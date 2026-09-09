<?php

namespace App\Modules\ResearchProgress\Queries;

use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\ResearchProgress\Services\ResearchJourneyService;
use Illuminate\Support\Str;

class GetAdviserProgressData
{
    public function __construct(
        private readonly GetResearchGroupProgress $progress,
        private readonly ResearchJourneyService $journey,
    ) {}

    /** @return array<string, mixed> */
    public function for(
        User $adviser,
        mixed $search = null,
        mixed $status = null,
        mixed $page = null,
        mixed $groupId = null,
    ): array {
        $search = Str::limit(strip_tags(trim(is_string($search) ? $search : '')), 100, '');
        $status = in_array($status, ['active', 'disbanded'], true) ? $status : 'active';
        $groupId = is_numeric($groupId) && (int) $groupId > 0 ? (int) $groupId : null;

        $allFilterGroups = ResearchClassGroup::query()
            ->where('adviser_id', $adviser->getKey())
            ->orderBy('name')
            ->get(['id', 'name', 'research_class_id', 'research_group_id']);

        $groups = ResearchClassGroup::query()
            ->where('adviser_id', $adviser->getKey())
            ->when($status === 'active', fn ($query) => $query->where('status', 'active')->whereNull('disbanded_at'))
            ->when($status === 'disbanded', fn ($query) => $query->where('status', 'disbanded'))
            ->when($groupId !== null, fn ($query) => $query->where('id', $groupId))
            ->when($search !== '', fn ($query) => $query->where(function ($nested) use ($search): void {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhereIn('research_group_id', fn ($sub) => $sub->select('research_group_id')->from('research_projects')->where('title', 'like', "%{$search}%"));
            }))
            ->with([
                'researchClass:id,name,facilitator_id',
                'adviser:id,name,email',
                'leader:id,name,email',
                'members.student:id,name,email',
                'milestones.definition',
                'milestones.evidences',
                'milestones.events.actor:id,name,email',
            ])
            ->latest()
            ->paginate(10, ['*'], 'progress_page', is_numeric($page) ? (int) $page : null)
            ->withQueryString();

        $groups->setCollection($groups->getCollection()->map(function (ResearchClassGroup $group) use ($adviser): ResearchClassGroup {
            $summary = $this->progress->fromLoaded($group, $group->milestones);
            $journey = $this->journey->getJourneyForGroup($group, $adviser);

            $summary['journey'] = $journey;
            $summary['progress_percentage'] = $journey['percentage'];
            $summary['completed_count'] = collect($journey['stages'])
                ->filter(fn (array $stage): bool => $stage['is_completed'] && ! ($stage['is_optional'] ?? false))
                ->count();
            $summary['applicable_count'] = collect($journey['stages'])
                ->reject(fn (array $stage): bool => $stage['is_optional'] ?? false)
                ->count();

            $group->setAttribute('progress_summary', $summary);

            return $group;
        }));

        return [
            'progressGroups' => $groups,
            'progressSearch' => $search,
            'progressGroupStatus' => $status,
            'progressGroupId' => $groupId,
            'allFilterGroups' => $allFilterGroups,
        ];
    }
}
