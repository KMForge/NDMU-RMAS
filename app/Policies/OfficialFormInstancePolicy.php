<?php

namespace App\Policies;

use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;

class OfficialFormInstancePolicy
{
    public function __construct(
        private readonly OfficialFormAuthorization $authorization = new OfficialFormAuthorization
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('users.manage')
            || $user->getAllPermissions()->contains(fn ($p) => str_starts_with($p->name, 'forms.'));
    }

    public function view(User $user, OfficialFormInstance $instance): bool
    {
        $code = strtolower($instance->definition->code);

        // RES-048 contains confidential peer ratings. Until an institutional
        // release policy is approved, only the student evaluator may view it.
        if ($code === 'res-048' && (int) $instance->initiated_by !== (int) $user->id) {
            return false;
        }

        // RES-036 is an individual panelist defense evaluation. Each panelist's scoring
        // and comments are strictly private to the evaluating panelist. Only the authoring
        // panelist or authorized academic supervisors (dean, facilitator, admin) may view it.
        // Other panelists must never access another panelist's RES-036 evaluation.
        if ($code === 'res-036') {
            $isOwner = (int) $instance->initiated_by === (int) $user->id
                || ($instance->defense_evaluation_id !== null && (int) $instance->defenseEvaluation?->panelist_user_id === (int) $user->id);
            $canSupervise = $user->can('users.manage')
                || $user->hasRole('college-dean')
                || $user->hasRole('dean')
                || $user->can('dashboards.dean.view')
                || ($instance->researchClass !== null && (int) $instance->researchClass->facilitator_id === (int) $user->id)
                || ($instance->group?->researchClass !== null && (int) $instance->group->researchClass->facilitator_id === (int) $user->id);

            if (! $isOwner && ! $canSupervise) {
                return false;
            }
        }

        $isAssignedRes026Panelist = $code === 'res-026'
            && $user->can('evaluations.create')
            && $instance->titlePresentation !== null
            && $instance->titlePresentation->defense->activePanelAssignments()
                ->where('user_id', $user->id)
                ->exists();
        $isClassFacilitator = ($instance->researchClass !== null && (int) $instance->researchClass->facilitator_id === (int) $user->id)
            || ($instance->group?->researchClass !== null && (int) $instance->group->researchClass->facilitator_id === (int) $user->id);

        $hasViewPermission = $user->can("forms.{$code}.view")
            || $user->getAllPermissions()->contains(fn ($p) => str_starts_with($p->name, "forms.{$code}."))
            || $isAssignedRes026Panelist
            || $isClassFacilitator
            || $user->can('dashboards.dean.view')
            || $user->hasRole('college-dean')
            || $user->hasRole('dean');

        if (! $hasViewPermission && ! $user->can('users.manage')) {
            return false;
        }

        return $this->authorization->checkContextualAccess($user, $instance);
    }

    public function initiate(User $user, OfficialFormDefinition $definition, ?ResearchClassGroup $group = null, ?ResearchClass $class = null): bool
    {
        return $this->authorization->canInitiate($user, $definition, $group, $class);
    }

    public function updateDraft(User $user, OfficialFormInstance $instance): bool
    {
        $code = strtolower($instance->definition->code);
        if ($code === 'res-036' && (int) $instance->initiated_by !== (int) $user->id) {
            return false;
        }

        if (! in_array($instance->status, ['draft', 'returned_for_correction'], true)) {
            return false;
        }

        return $this->authorization->canSubmit($user, $instance);
    }

    public function submit(User $user, OfficialFormInstance $instance): bool
    {
        $code = strtolower($instance->definition->code);
        if ($code === 'res-036' && (int) $instance->initiated_by !== (int) $user->id) {
            return false;
        }

        return $this->authorization->canSubmit($user, $instance);
    }

    public function endorse(User $user, OfficialFormInstance $instance): bool
    {
        return $this->authorization->canPerformAction($user, $instance, 'endorse');
    }

    public function certify(User $user, OfficialFormInstance $instance): bool
    {
        return $this->authorization->canCertify($user, $instance);
    }

    public function sign(User $user, OfficialFormInstance $instance): bool
    {
        $code = strtolower($instance->definition->code);
        if ($code === 'res-036' && (int) $instance->initiated_by !== (int) $user->id) {
            return false;
        }

        return $this->authorization->canPerformAction($user, $instance, 'sign');
    }

    public function sign_authorship(User $user, OfficialFormInstance $instance): bool
    {
        return $this->authorization->canPerformAction($user, $instance, 'sign_authorship');
    }

    public function note(User $user, OfficialFormInstance $instance): bool
    {
        return $this->authorization->canPerformAction($user, $instance, 'note');
    }

    public function record(User $user, OfficialFormInstance $instance): bool
    {
        return $this->authorization->canPerformAction($user, $instance, 'record');
    }

    public function conforme(User $user, OfficialFormInstance $instance): bool
    {
        return $this->authorization->canPerformAction($user, $instance, 'conforme');
    }

    public function respond(User $user, OfficialFormInstance $instance): bool
    {
        return $this->authorization->canPerformAction($user, $instance, 'respond');
    }

    public function sign_chairperson(User $user, OfficialFormInstance $instance): bool
    {
        return $this->authorization->canPerformAction($user, $instance, 'sign_chairperson');
    }

    public function sign_member_1(User $user, OfficialFormInstance $instance): bool
    {
        return $this->authorization->canPerformAction($user, $instance, 'sign_member_1');
    }

    public function sign_member_2(User $user, OfficialFormInstance $instance): bool
    {
        return $this->authorization->canPerformAction($user, $instance, 'sign_member_2');
    }

    public function approve(User $user, OfficialFormInstance $instance): bool
    {
        return $this->authorization->canPerformAction($user, $instance, 'approve');
    }

    public function receive(User $user, OfficialFormInstance $instance): bool
    {
        return $this->authorization->canPerformAction($user, $instance, 'receive');
    }

    public function validate(User $user, OfficialFormInstance $instance): bool
    {
        return $this->authorization->canPerformAction($user, $instance, 'validate');
    }

    public function evaluate(User $user, OfficialFormInstance $instance): bool
    {
        $code = strtolower($instance->definition->code);
        if ($code === 'res-036' && (int) $instance->initiated_by !== (int) $user->id) {
            return false;
        }

        return $this->authorization->canPerformAction($user, $instance, 'evaluate');
    }

    public function assignActor(User $user, OfficialFormInstance $instance): bool
    {
        return $this->authorization->canAssignActor($user, $instance);
    }
}
