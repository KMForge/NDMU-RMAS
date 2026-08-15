<?php

namespace App\Modules\Evaluations\Services;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\Defense;
use App\Models\DefenseEvaluationRound;
use App\Models\DefenseEvaluationRoundPanelist;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class EvaluationAuthorization
{
    /**
     * Assert actor is active, verified, approved faculty user.
     */
    public function assertFacultyActor(User $actor): void
    {
        if ($actor->user_type !== UserType::Faculty
            || $actor->status !== AccountStatus::Active
            || $actor->approved_at === null
            || $actor->email_verified_at === null) {
            throw new AuthorizationException('Unauthorized: You lack active, verified faculty credentials.');
        }
    }

    /**
     * Assert a user candidate is eligible to be frozen as an evaluation panelist BEFORE round creation.
     */
    public function assertEligiblePanelCandidate(User $candidate): void
    {
        $this->assertFacultyActor($candidate);

        if (! $candidate->can('evaluations.create') || ! $candidate->can('forms.res-036.evaluate')) {
            throw new AuthorizationException("Assigned panelist user #{$candidate->id} lacks required evaluation permissions (evaluations.create, forms.res-036.evaluate).");
        }
    }

    /**
     * Assert a user candidate is eligible to be designated as the summary signer BEFORE round creation.
     */
    public function assertSummarySignerCandidate(User $candidate): void
    {
        $this->assertEligiblePanelCandidate($candidate);

        if (! $candidate->can('forms.res-037.sign')) {
            throw new AuthorizationException("Summary signer candidate user #{$candidate->id} lacks forms.res-037.sign permission.");
        }
    }

    /**
     * Assert actor is facilitator owning the research class for the defense.
     */
    public function assertFacilitatorOwnsDefense(User $actor, Defense $defense, string $permission = 'defenses.manage'): void
    {
        $this->assertFacultyActor($actor);

        if (! $actor->can($permission)) {
            throw new AuthorizationException("Unauthorized: You lack the '{$permission}' permission.");
        }

        $group = $defense->group;
        if (! $group || ! $group->researchClass || (int) $group->researchClass->facilitator_id !== (int) $actor->id) {
            throw new AuthorizationException('Unauthorized: You do not own the research class for this defense.');
        }
    }

    /**
     * Assert actor is facilitator owning the research class for the evaluation round.
     */
    public function assertFacilitatorOwnsRound(User $actor, DefenseEvaluationRound $round, string $permission): void
    {
        $this->assertFacultyActor($actor);

        if (! $actor->can($permission)) {
            throw new AuthorizationException("Unauthorized: You lack the '{$permission}' permission.");
        }

        $defense = $round->defense;
        if (! $defense) {
            throw new AuthorizationException('Unauthorized: Evaluation round has no associated defense.');
        }

        $this->assertFacilitatorOwnsDefense($actor, $defense, $permission);
    }

    /**
     * Assert actor is frozen panelist on evaluation round with required permission.
     */
    public function assertEligiblePanelist(User $actor, DefenseEvaluationRound $round, string $permission = 'evaluations.create'): DefenseEvaluationRoundPanelist
    {
        $this->assertFacultyActor($actor);

        if (! $actor->can($permission) || ! $actor->can('forms.res-036.evaluate')) {
            throw new AuthorizationException("Unauthorized: You lack permission to evaluate defenses ({$permission}).");
        }

        $roundPanelist = $round->roundPanelists->firstWhere('panelist_user_id', $actor->id);
        if (! $roundPanelist) {
            throw new AuthorizationException('Unauthorized: You are not a frozen panelist for this evaluation round.');
        }

        return $roundPanelist;
    }

    /**
     * Assert actor is designated summary signer for the evaluation round.
     */
    public function assertSummarySigner(User $actor, DefenseEvaluationRound $round): void
    {
        $this->assertFacultyActor($actor);

        if (! $actor->can('forms.res-037.sign')) {
            throw new AuthorizationException('Unauthorized: You lack permission to sign RES-037 forms.');
        }

        if ((int) $round->summary_signer_user_id !== (int) $actor->id) {
            throw new AuthorizationException('Unauthorized: You are not the designated summary signer for this round.');
        }
    }
}
