<?php

namespace App\Policies;

use App\Models\DefenseSchedule;
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
        $isAssignedRes026Panelist = $code === 'res-026'
            && $user->can('evaluations.create')
            && $instance->titlePresentation !== null
            && $instance->titlePresentation->defense->activePanelAssignments()
                ->where('user_id', $user->id)
                ->exists();
        $hasViewPermission = $user->hasPermissionTo("forms.{$code}.view")
            || $user->getAllPermissions()->contains(fn ($p) => str_starts_with($p->name, "forms.{$code}."))
            || $isAssignedRes026Panelist
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
        if ($this->isDefenseBackedForm($instance)) {
            return false;
        }

        if (! in_array($instance->status, ['draft', 'returned_for_correction'], true)) {
            return false;
        }

        return $this->authorization->canSubmit($user, $instance);
    }

    public function submit(User $user, OfficialFormInstance $instance): bool
    {
        if ($this->isDefenseBackedForm($instance)) {
            return false;
        }

        return $this->authorization->canSubmit($user, $instance);
    }

    public function endorse(User $user, OfficialFormInstance $instance): bool
    {
        if ($this->isDefenseBackedForm($instance)) {
            return false;
        }

        return $this->authorization->canPerformAction($user, $instance, 'endorse');
    }

    public function certify(User $user, OfficialFormInstance $instance): bool
    {
        if ($this->isDefenseBackedForm($instance)) {
            return false;
        }

        return $this->authorization->canCertify($user, $instance);
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
        if ($this->isDefenseBackedForm($instance)) {
            return false;
        }

        return $this->authorization->canPerformAction($user, $instance, 'approve');
    }

    public function receive(User $user, OfficialFormInstance $instance): bool
    {
        if ($this->isDefenseBackedForm($instance)) {
            return false;
        }

        return $this->authorization->canPerformAction($user, $instance, 'receive');
    }

    public function validate(User $user, OfficialFormInstance $instance): bool
    {
        if ($this->isDefenseBackedForm($instance)) {
            return false;
        }

        return $this->authorization->canPerformAction($user, $instance, 'validate');
    }

    public function evaluate(User $user, OfficialFormInstance $instance): bool
    {
        if ($this->isDefenseBackedForm($instance)) {
            return false;
        }

        return $this->authorization->canPerformAction($user, $instance, 'evaluate');
    }

    public function assignActor(User $user, OfficialFormInstance $instance): bool
    {
        return $this->authorization->canAssignActor($user, $instance);
    }

    private function isDefenseBackedForm(OfficialFormInstance $instance): bool
    {
        return strtoupper($instance->definition->code) === 'RES-036'
            && $instance->source_type === DefenseSchedule::class;
    }
}
