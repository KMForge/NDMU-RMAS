<?php

namespace App\Modules\DefenseScheduling\Queries;

use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassPanelCommittee;
use App\Models\ResearchGroupPanelCommittee;
use App\Models\User;
use App\Modules\DefenseScheduling\Services\DefenseEndorsementEligibility;
use App\Modules\DefenseScheduling\Services\EffectiveDefenseCommitteeResolver;
use Illuminate\Support\Collection;

class GetClassCommitteeAssignments
{
    public function __construct(
        private readonly DefenseEndorsementEligibility $endorsementEligibility,
        private readonly EffectiveDefenseCommitteeResolver $committeeResolver,
    ) {}

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
        $committeeTypes = [
            EffectiveDefenseCommitteeResolver::TITLE_PRESENTATION,
            'proposal_defense',
            'pre_final_defense',
            'final_defense',
        ];

        $classCommittees = ResearchClassPanelCommittee::query()
            ->where('research_class_id', $researchClass->id)
            ->whereIn('defense_type', $committeeTypes)
            ->with(['chairperson:id,name,email,department', 'members.user:id,name,email,department'])
            ->get();

        $groupCommittees = ResearchGroupPanelCommittee::query()
            ->whereHas('group', fn ($q) => $q->where('research_class_id', $researchClass->id)->where('status', 'active'))
            ->whereIn('defense_type', $committeeTypes)
            ->with(['chairperson:id,name,email,department', 'members.user:id,name,email,department'])
            ->get();

        $panelInvitations = OfficialFormInstance::query()
            ->whereHas('group', fn ($query) => $query->where('research_class_id', $researchClass->id))
            ->whereHas('definition', fn ($query) => $query->where('code', 'RES-028'))
            ->with('actorAssignments')
            ->latest('id')
            ->get();

        $groups = ResearchClassGroup::query()
            ->where('research_class_id', $researchClass->id)
            ->where('status', 'active')
            ->with(['adviser:id,name,email,department', 'leader:id,name'])
            ->orderBy('name')
            ->get()
            ->map(function (ResearchClassGroup $group) use ($groupCommittees, $classCommittees, $defenseType, $panelInvitations): array {
                $resolution = $this->committeeResolver->resolve(
                    $groupCommittees->where('research_class_group_id', $group->id)->values(),
                    $classCommittees,
                    $defenseType,
                );
                $committee = $resolution['committee'];
                $chairperson = $committee?->chairperson;
                $members = $committee?->members?->sortBy('panel_position')->values() ?? collect();
                $isCustom = $committee instanceof ResearchGroupPanelCommittee && (bool) $committee->is_custom;
                $sourceLabel = str($resolution['source_defense_type'] ?? '')->replace('_', ' ')->title()->toString();
                $statusLabel = match (true) {
                    $resolution['inherited'] => "Inherited from {$sourceLabel}",
                    $isCustom => 'Customized assignment',
                    $resolution['scope'] === 'class' => 'Uses class assignment',
                    $resolution['scope'] === 'group' => 'Uses class assignment',
                    default => 'Missing assignment',
                };

                $member1 = $members->firstWhere('panel_position', 'member_1')?->user ?? $members->get(0)?->user ?? null;
                $member2 = $members->firstWhere('panel_position', 'member_2')?->user ?? $members->get(1)?->user ?? null;

                $isComplete = $chairperson !== null && $member1 !== null && $member2 !== null;
                $invitationStatus = function (string $position, ?User $invitee) use ($group, $resolution, $panelInvitations): ?string {
                    if ($invitee === null) {
                        return null;
                    }

                    $invitationDefenseType = $resolution['source_defense_type'];
                    $contextKey = "panel-invitation:{$invitationDefenseType}:{$position}";
                    $instance = $panelInvitations->first(fn (OfficialFormInstance $form): bool => (int) $form->research_class_group_id === (int) $group->id
                        && $form->context_key === $contextKey
                        && $form->actorAssignments->contains(fn ($assignment): bool => (int) $assignment->user_id === (int) $invitee->id));

                    return match ($instance?->status) {
                        'approved', 'completed', 'signed', 'conformed' => 'accepted',
                        'rejected', 'cancelled' => 'rejected',
                        default => 'pending',
                    };
                };

                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'group_name' => $group->name,
                    'title' => $group->title ?? 'No research title registered yet',
                    'leader_name' => $group->leader?->name ?? 'Not assigned',
                    'adviser_id' => $group->adviser_id,
                    'adviser_name' => $group->adviser?->name ?? 'Not assigned',
                    'assignment_status' => $statusLabel,
                    'committee_status' => $committee !== null
                        ? ($resolution['inherited'] ? 'inherited' : ($isCustom ? 'custom' : 'class'))
                        : 'missing',
                    'committee_status_label' => $statusLabel,
                    'is_custom' => $isCustom,
                    'committee_inherited' => $resolution['inherited'],
                    'inherited_from_title' => $resolution['inherited_from_title'],
                    'committee_source_defense_type' => $resolution['source_defense_type'],
                    'is_complete' => $isComplete,
                    'res033_complete' => $this->endorsementEligibility->isComplete($group, $defenseType),
                    'chairperson_id' => $chairperson?->id,
                    'chairperson_name' => $chairperson?->name,
                    'chairperson_invitation_status' => $invitationStatus('chairperson', $chairperson),
                    'member_1_id' => $member1?->id,
                    'member_1_name' => $member1?->name,
                    'member_1_invitation_status' => $invitationStatus('member_1', $member1),
                    'member_2_id' => $member2?->id,
                    'member_2_name' => $member2?->name,
                    'member_2_invitation_status' => $invitationStatus('member_2', $member2),
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
            'class_committee' => ($classResolution = $this->committeeResolver->resolve(collect(), $classCommittees, $defenseType))['committee'] ? [
                'chairperson' => $classResolution['committee']->chairperson ? [
                    'id' => $classResolution['committee']->chairperson->id,
                    'name' => $classResolution['committee']->chairperson->name,
                    'department' => $classResolution['committee']->chairperson->department,
                ] : null,
                'members' => $classResolution['committee']->members->map(fn ($m) => [
                    'id' => $m->user?->id,
                    'name' => $m->user?->name,
                    'position' => $m->panel_position,
                    'department' => $m->user?->department,
                ])->all(),
                'inherited' => $classResolution['inherited'],
                'source_defense_type' => $classResolution['source_defense_type'],
            ] : null,
            'groups' => $groups,
        ];
    }
}
