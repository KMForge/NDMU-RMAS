<?php

namespace App\Policies;

use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;

class OfficialFormInstancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->getAllPermissions()->contains(fn ($p) => str_starts_with($p->name, 'forms.'));
    }

    public function view(User $user, OfficialFormInstance $instance): bool
    {
        $code = strtolower($instance->definition->code);
        $hasViewPermission = $user->hasPermissionTo("forms.{$code}.view")
            || $user->getAllPermissions()->contains(fn ($p) => str_starts_with($p->name, "forms.{$code}."));

        if (! $hasViewPermission && ! $user->can('users.manage')) {
            return false;
        }

        return $this->checkContextualAccess($user, $instance);
    }

    public function initiate(User $user, OfficialFormDefinition $definition, ?ResearchClassGroup $group = null, ?ResearchClass $class = null): bool
    {
        $code = strtolower($definition->code);
        $hasPermission = $user->getAllPermissions()->contains(fn ($p) => str_starts_with($p->name, "forms.{$code}."));

        if (! $hasPermission) {
            return false;
        }

        if ($definition->ownership_scope === 'research_group' && $group !== null) {
            return $user->research_class_group_id === $group->id
                || $group->adviser_id === $user->id
                || $user->can('classes.serve-as-adviser')
                || $user->can('users.manage');
        }

        if ($definition->ownership_scope === 'research_class' && $class !== null) {
            return $class->facilitator_id === $user->id
                || $user->hasRole('program-coordinator')
                || $user->can('users.manage');
        }

        return true;
    }

    public function updateDraft(User $user, OfficialFormInstance $instance): bool
    {
        if (! in_array($instance->status, ['draft', 'returned_for_correction'], true)) {
            return false;
        }

        return $instance->initiated_by === $user->id || $this->view($user, $instance);
    }

    public function submit(User $user, OfficialFormInstance $instance): bool
    {
        $code = strtolower($instance->definition->code);
        $hasSubmit = $user->hasPermissionTo("forms.{$code}.submit")
            || $user->hasPermissionTo("forms.{$code}.fill")
            || $user->hasPermissionTo("forms.{$code}.respond");

        if (! $hasSubmit) {
            return false;
        }

        return $this->view($user, $instance);
    }

    public function endorse(User $user, OfficialFormInstance $instance): bool
    {
        $code = strtolower($instance->definition->code);
        $hasEndorse = $user->hasPermissionTo("forms.{$code}.endorse")
            || $user->hasPermissionTo("forms.{$code}.approve");

        if (! $hasEndorse) {
            return false;
        }

        return $this->view($user, $instance);
    }

    public function certify(User $user, OfficialFormInstance $instance): bool
    {
        $code = strtolower($instance->definition->code);
        $hasCertify = $user->hasPermissionTo("forms.{$code}.certify")
            || $user->hasPermissionTo("forms.{$code}.validate");

        if (! $hasCertify) {
            return false;
        }

        return $this->view($user, $instance);
    }

    private function checkContextualAccess(User $user, OfficialFormInstance $instance): bool
    {
        if ($user->can('users.manage')) {
            return true;
        }

        if ($instance->research_class_group_id !== null) {
            $group = $instance->group;
            if ($group !== null) {
                if ($user->research_class_group_id === $group->id) {
                    return true;
                }
                if ($group->adviser_id === $user->id) {
                    return true;
                }
            }
        }

        if ($instance->research_class_id !== null) {
            $class = $instance->researchClass;
            if ($class !== null && $class->facilitator_id === $user->id) {
                return true;
            }
        }

        return $instance->actorAssignments()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists()
            || $instance->initiated_by === $user->id;
    }
}
