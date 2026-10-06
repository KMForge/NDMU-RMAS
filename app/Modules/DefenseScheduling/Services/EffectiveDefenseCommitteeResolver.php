<?php

namespace App\Modules\DefenseScheduling\Services;

use App\Models\ResearchClassGroup;
use App\Models\ResearchClassPanelCommittee;
use App\Models\ResearchGroupPanelCommittee;
use Illuminate\Support\Collection;

class EffectiveDefenseCommitteeResolver
{
    public const TITLE_PRESENTATION = 'title_presentation';

    /** @var list<string> */
    private const DEFENSE_SEQUENCE = [
        self::TITLE_PRESENTATION,
        'proposal_defense',
        'pre_final_defense',
        'final_defense',
    ];

    /** @return list<string> */
    public function sourceTypesFor(string $defenseType): array
    {
        $targetIndex = array_search($defenseType, self::DEFENSE_SEQUENCE, true);

        return $targetIndex === false
            ? [$defenseType]
            : array_slice(self::DEFENSE_SEQUENCE, 0, $targetIndex + 1);
    }

    /**
     * @param  Collection<int, ResearchGroupPanelCommittee>  $groupCommittees
     * @param  Collection<int, ResearchClassPanelCommittee>  $classCommittees
     * @return array{
     *     committee: ResearchGroupPanelCommittee|ResearchClassPanelCommittee|null,
     *     scope: 'group'|'class'|null,
     *     source_defense_type: string|null,
     *     inherited: bool,
     *     inherited_from_title: bool
     * }
     */
    public function resolve(Collection $groupCommittees, Collection $classCommittees, string $defenseType): array
    {
        $eligibleTypes = array_reverse($this->sourceTypesFor($defenseType));
        $candidates = [];

        foreach ($eligibleTypes as $sourceDefenseType) {
            $candidates[] = [$groupCommittees->firstWhere('defense_type', $sourceDefenseType), 'group', $sourceDefenseType];
            $candidates[] = [$classCommittees->firstWhere('defense_type', $sourceDefenseType), 'class', $sourceDefenseType];
        }

        foreach ($candidates as [$committee, $scope, $sourceDefenseType]) {
            if ($committee !== null) {
                return [
                    'committee' => $committee,
                    'scope' => $scope,
                    'source_defense_type' => $sourceDefenseType,
                    'inherited' => $sourceDefenseType !== $defenseType,
                    'inherited_from_title' => $sourceDefenseType === self::TITLE_PRESENTATION
                        && $defenseType !== self::TITLE_PRESENTATION,
                ];
            }
        }

        return [
            'committee' => null,
            'scope' => null,
            'source_defense_type' => null,
            'inherited' => false,
            'inherited_from_title' => false,
        ];
    }

    /**
     * @return array{
     *     committee: ResearchGroupPanelCommittee|ResearchClassPanelCommittee|null,
     *     scope: 'group'|'class'|null,
     *     source_defense_type: string|null,
     *     inherited: bool,
     *     inherited_from_title: bool
     * }
     */
    public function forGroup(ResearchClassGroup $group, string $defenseType): array
    {
        $types = $this->sourceTypesFor($defenseType);

        $groupCommittees = ResearchGroupPanelCommittee::query()
            ->where('research_class_group_id', $group->id)
            ->whereIn('defense_type', $types)
            ->with(['chairperson', 'members.user'])
            ->get();

        $classCommittees = ResearchClassPanelCommittee::query()
            ->where('research_class_id', $group->research_class_id)
            ->whereIn('defense_type', $types)
            ->with(['chairperson', 'members.user'])
            ->get();

        return $this->resolve($groupCommittees, $classCommittees, $defenseType);
    }
}
