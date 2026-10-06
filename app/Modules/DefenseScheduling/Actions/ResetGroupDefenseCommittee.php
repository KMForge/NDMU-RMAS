<?php

namespace App\Modules\DefenseScheduling\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassPanelCommittee;
use App\Models\ResearchGroupPanelCommittee;
use App\Models\User;
use App\Modules\DefenseScheduling\Services\EffectiveDefenseCommitteeResolver;
use App\Modules\DefenseScheduling\Services\ManageDefenseCommitteeAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResetGroupDefenseCommittee
{
    public function __construct(
        private readonly ManageDefenseCommitteeAccess $access,
        private readonly EffectiveDefenseCommitteeResolver $committeeResolver,
    ) {}

    /**
     * Handle invocation with model instances.
     */
    public function handle(
        User $actor,
        ResearchClassGroup $group,
        string $defenseType
    ): ResearchGroupPanelCommittee|ResearchClassPanelCommittee {
        return $this->execute(
            researchClassGroupId: $group->id,
            defenseType: $defenseType,
            assignedByUserId: $actor->id
        );
    }

    /**
     * Remove the stage-specific group override and return the effective fallback committee.
     */
    public function execute(
        int $researchClassGroupId,
        string $defenseType,
        int $assignedByUserId
    ): ResearchGroupPanelCommittee|ResearchClassPanelCommittee {
        $actor = User::findOrFail($assignedByUserId);
        $group = ResearchClassGroup::with('researchClass')->findOrFail($researchClassGroupId);

        if ($actor->user_type !== UserType::Faculty || $actor->status !== AccountStatus::Active) {
            throw new AuthorizationException('Unauthorized to manage defense committees.');
        }

        if (! $this->access->forGroup($actor, $group)) {
            throw new AuthorizationException('You are not authorized to manage committees for this class group.');
        }

        return DB::transaction(function () use ($group, $defenseType) {
            ResearchGroupPanelCommittee::query()
                ->where('research_class_group_id', $group->id)
                ->where('defense_type', $defenseType)
                ->delete();

            $resolution = $this->committeeResolver->forGroup($group, $defenseType);
            $committee = $resolution['committee'];

            if ($committee === null) {
                throw ValidationException::withMessages([
                    'committee' => 'No earlier-stage or class default committee is available for this defense stage.',
                ]);
            }

            return $committee;
        });
    }
}
