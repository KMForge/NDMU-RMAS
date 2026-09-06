<?php

namespace App\Modules\DefenseScheduling\Queries;

use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassPanelCommittee;
use App\Models\ResearchGroupPanelCommittee;
use Illuminate\Support\Collection;

class GetClassCommitteeAssignments
{
    /**
     * @return array{
     *     class: array{id: int, name: string},
     *     defense_type: string,
     *     class_committee: array{chairperson: ?array, members: array<int, array>}|null,
     *     groups: Collection<int, array>
     * }
     */
    public function forClass(ResearchClass $researchClass, string $defenseType): array
    {
        $classCommittee = ResearchClassPanelCommittee::query()
            ->where('research_class_id', $researchClass->id)
            ->where('defense_type', $defenseType)
            ->with(['chairperson:id,name,email,department', 'members.user:id,name,email,department'])
            ->first();

        $groupCommittees = ResearchGroupPanelCommittee::query()
            ->whereHas('group', fn ($q) => $q->where('research_class_id', $researchClass->id)->where('status', 'active'))
            ->where('defense_type', $defenseType)
            ->with(['chairperson:id,name,email,department', 'members.user:id,name,email,department'])
            ->get()
            ->keyBy('research_class_group_id');

        $groups = ResearchClassGroup::query()
            ->where('research_class_id', $researchClass->id)
            ->where('status', 'active')
            ->with(['adviser:id,name,email,department', 'leader:id,name'])
            ->orderBy('name')
            ->get()
            ->map(function (ResearchClassGroup $group) use ($groupCommittees, $classCommittee): array {
                $groupCommittee = $groupCommittees->get($group->id);

                if ($groupCommittee !== null) {
                    $chairperson = $groupCommittee->chairperson;
                    $members = $groupCommittee->members->sortBy('panel_position')->values();
                    $isCustom = (bool) $groupCommittee->is_custom;
                    $statusLabel = $isCustom ? 'Customized assignment' : 'Uses class assignment';
                } elseif ($classCommittee !== null) {
                    // Falls back to class default if not explicitly written to group yet
                    $chairperson = $classCommittee->chairperson;
                    $members = $classCommittee->members->sortBy('panel_position')->values();
                    $isCustom = false;
                    $statusLabel = 'Uses class assignment';
                } else {
                    $chairperson = null;
                    $members = collect();
                    $isCustom = false;
                    $statusLabel = 'Missing assignment';
                }

                $member1 = $members->firstWhere('panel_position', 'member_1')?->user ?? $members->get(0)?->user ?? null;
                $member2 = $members->firstWhere('panel_position', 'member_2')?->user ?? $members->get(1)?->user ?? null;

                $isComplete = $chairperson !== null && $member1 !== null && $member2 !== null;

                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'group_name' => $group->name,
                    'title' => $group->title ?? 'No research title registered yet',
                    'leader_name' => $group->leader?->name ?? 'Not assigned',
                    'adviser_id' => $group->adviser_id,
                    'adviser_name' => $group->adviser?->name ?? 'Not assigned',
                    'assignment_status' => $statusLabel,
                    'committee_status' => $groupCommittee !== null ? ($isCustom ? 'custom' : 'class') : ($classCommittee !== null ? 'class' : 'missing'),
                    'committee_status_label' => $statusLabel,
                    'is_custom' => $isCustom,
                    'is_complete' => $isComplete,
                    'chairperson_id' => $chairperson?->id,
                    'chairperson_name' => $chairperson?->name,
                    'member_1_id' => $member1?->id,
                    'member_1_name' => $member1?->name,
                    'member_2_id' => $member2?->id,
                    'member_2_name' => $member2?->name,
                    'committee' => [
                        'chairperson_id' => $chairperson?->id,
                        'chairperson_name' => $chairperson?->name,
                        'panel_member_1_id' => $member1?->id,
                        'panel_member_1_name' => $member1?->name,
                        'panel_member_2_id' => $member2?->id,
                        'panel_member_2_name' => $member2?->name,
                    ],
                ];
            });

        return [
            'class' => [
                'id' => $researchClass->id,
                'name' => $researchClass->name,
            ],
            'defense_type' => $defenseType,
            'class_committee' => $classCommittee ? [
                'chairperson' => $classCommittee->chairperson ? [
                    'id' => $classCommittee->chairperson->id,
                    'name' => $classCommittee->chairperson->name,
                    'department' => $classCommittee->chairperson->department,
                ] : null,
                'members' => $classCommittee->members->map(fn ($m) => [
                    'id' => $m->user?->id,
                    'name' => $m->user?->name,
                    'position' => $m->panel_position,
                    'department' => $m->user?->department,
                ])->all(),
            ] : null,
            'groups' => $groups,
        ];
    }
}
