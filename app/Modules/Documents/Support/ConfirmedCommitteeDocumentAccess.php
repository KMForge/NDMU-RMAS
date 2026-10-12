<?php

namespace App\Modules\Documents\Support;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClassPanelCommittee;
use App\Models\ResearchGroupPanelCommittee;
use App\Models\User;
use App\Modules\DefenseScheduling\Services\EffectiveDefenseCommitteeResolver;

class ConfirmedCommitteeDocumentAccess
{
    /** @return list<int> */
    public function documentIdsFor(User $panelist): array
    {
        if (! $panelist->can('documents.download') || ! $panelist->can('evaluations.create')) {
            return [];
        }

        $invitations = OfficialFormInstance::query()
            ->whereHas('definition', fn ($query) => $query->where('code', 'RES-028'))
            ->where('status', 'approved')
            ->whereHas('actorAssignments', fn ($query) => $query->where('user_id', $panelist->id)->where('status', 'active'))
            ->get(['research_class_group_id', 'context_key']);
        $documents = Document::query()->whereIn('research_class_group_id', $invitations->pluck('research_class_group_id'))
            ->where('is_current', true)->where('status', DocumentStatus::Accepted->value)
            ->whereHas('researchClassGroup', fn ($query) => $query->where('status', 'active')->whereNull('disbanded_at'))
            ->with('researchClassGroup.defenses')->get();
        $groups = ResearchGroupPanelCommittee::query()->whereIn('research_class_group_id', $documents->pluck('research_class_group_id'))->with('members')->get()->groupBy('research_class_group_id');
        $classes = ResearchClassPanelCommittee::query()->whereIn('research_class_id', $documents->pluck('researchClassGroup.research_class_id'))->with('members')->get()->groupBy('research_class_id');
        $resolver = app(EffectiveDefenseCommitteeResolver::class);

        return $documents->filter(function (Document $document) use ($panelist, $invitations, $groups, $classes, $resolver): bool {
            $type = $document->document_stage?->defenseType();
            if ($type === null) {
                return false;
            }
            // Once this stage has a defense, its explicit assignments are authoritative.
            if ($document->researchClassGroup->defenses->contains('defense_type', $type)) {
                return false;
            }
            $resolved = $resolver->resolve($groups->get($document->research_class_group_id, collect()), $classes->get($document->researchClassGroup->research_class_id, collect()), $type);
            $committee = $resolved['committee'];
            if ($committee === null) {
                return false;
            }
            $position = (int) $committee->chairperson_id === (int) $panelist->id
                ? 'chairperson' : $committee->members->firstWhere('user_id', $panelist->id)?->panel_position;

            return $position !== null && $invitations->contains(fn ($invitation) => (int) $invitation->research_class_group_id === (int) $document->research_class_group_id
                && $invitation->context_key === 'panel-invitation:'.$resolved['source_defense_type'].':'.$position);
        })->pluck('id')->all();
    }
}
