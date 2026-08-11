<?php

namespace App\Modules\ResearchProgress\Queries;

use App\Models\ResearchClassGroup;
use App\Models\User;
use Illuminate\Support\Str;

class GetFacilitatorProgressData
{
    public function __construct(private readonly GetResearchGroupProgress $progress) {}

    /** @return array<string, mixed> */
    public function for(User $facilitator, mixed $search = null, mixed $status = null, mixed $page = null): array
    {
        $search = Str::limit(strip_tags(trim(is_string($search) ? $search : '')), 100, '');
        $status = in_array($status, ['active', 'disbanded'], true) ? $status : 'active';

        $groups = ResearchClassGroup::query()
            ->whereHas('researchClass', fn ($query) => $query->where('facilitator_id', $facilitator->getKey()))
            ->when($status === 'active', fn ($query) => $query->where('status', 'active')->whereNull('disbanded_at'))
            ->when($status === 'disbanded', fn ($query) => $query->where('status', 'disbanded'))
            ->when($search !== '', fn ($query) => $query->where(function ($nested) use ($search): void {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('research_title', 'like', "%{$search}%");
            }))
            ->with([
                'researchClass:id,name,facilitator_id',
                'adviser:id,name,email',
                'members.student:id,name,email',
                'milestones.definition',
                'milestones.evidences',
                'milestones.events.actor:id,name,email',
            ])
            ->latest()
            ->paginate(10, ['*'], 'progress_page', is_numeric($page) ? (int) $page : null)
            ->withQueryString();

        $groups->setCollection($groups->getCollection()->map(function (ResearchClassGroup $group): ResearchClassGroup {
            $group->setAttribute('progress_summary', $this->progress->fromLoaded($group, $group->milestones));

            return $group;
        }));

        return ['progressGroups' => $groups, 'progressSearch' => $search, 'progressGroupStatus' => $status];
    }
}
