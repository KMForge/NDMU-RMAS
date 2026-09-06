<?php

namespace App\Modules\DefenseScheduling\Services;

use App\Models\OfficialFormInstance;
use App\Models\ResearchClassGroup;
use InvalidArgumentException;

class DefenseEndorsementEligibility
{
    /** @var array<string, list<string>> */
    private const CONTEXT_ALIASES = [
        'title_presentation' => ['title_presentation', 'title_proposal'],
        'proposal_defense' => ['proposal_defense', 'proposal'],
        'pre_final_defense' => ['pre_final_defense', 'pre_final'],
        'final_defense' => ['final_defense', 'final', 'final_oral_defense'],
    ];

    public function isComplete(ResearchClassGroup $group, string $defenseType): bool
    {
        $aliases = self::CONTEXT_ALIASES[$defenseType] ?? [];

        if ($aliases === []) {
            return false;
        }

        return OfficialFormInstance::query()
            ->where('research_class_group_id', $group->id)
            ->where('status', 'received')
            ->whereHas('definition', fn ($query) => $query->where('code', 'RES-033'))
            ->where(function ($query) use ($aliases) {
                $query->whereIn('context_key', $aliases)
                    ->orWhere(function ($legacyQuery) use ($aliases) {
                        $legacyQuery->where('context_key', 'general')
                            ->whereHas('currentVersion', function ($versionQuery) use ($aliases) {
                                $versionQuery->where(function ($payloadQuery) use ($aliases) {
                                    foreach ($aliases as $alias) {
                                        $payloadQuery->orWhere('payload->defense_type', $alias);
                                    }
                                });
                            });
                    });
            })
            ->whereHas('currentVersion', function ($query) use ($aliases) {
                $query->where(function ($payloadQuery) use ($aliases) {
                    foreach ($aliases as $alias) {
                        $payloadQuery->orWhere('payload->defense_type', $alias);
                    }
                });
            })
            ->exists();
    }

    public function ensureComplete(ResearchClassGroup $group, string $defenseType): void
    {
        if ($this->isComplete($group, $defenseType)) {
            return;
        }

        $label = match ($defenseType) {
            'title_presentation' => 'Title Proposal',
            'proposal_defense' => 'Proposal Defense',
            'pre_final_defense' => 'Pre-Final Defense',
            'final_defense' => 'Final Defense',
            default => str($defenseType)->headline()->toString(),
        };

        throw new InvalidArgumentException(
            "{$group->name} cannot be scheduled for {$label} until its matching RES-033 Defense Endorsement is endorsed by the Adviser and received by the Program Coordinator."
        );
    }
}
