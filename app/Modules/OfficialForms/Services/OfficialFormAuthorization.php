<?php

namespace App\Modules\OfficialForms\Services;

use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;

class OfficialFormAuthorization
{
    /** @var array<string, list<string>> */
    public const FORM_ACTION_PERMISSIONS = [
        'res-026' => ['fill' => ['forms.res-026.fill', 'forms.res-026.submit'], 'approve' => ['forms.res-026.approve']],
        'res-027' => ['fill' => ['forms.res-027.respond'], 'approve' => ['forms.res-027.respond']],
        'res-028' => ['fill' => ['forms.res-028.respond'], 'approve' => ['forms.res-028.respond']],
        'res-029' => ['fill' => ['forms.res-029.respond'], 'approve' => ['forms.res-029.respond']],
        'res-030' => ['fill' => ['forms.res-030.submit'], 'approve' => ['forms.res-030.approve']],
        'res-031' => ['fill' => ['forms.res-031.fill'], 'approve' => ['forms.res-031.sign']],
        'res-032' => ['fill' => ['forms.res-032.fill'], 'approve' => ['forms.res-032.sign']],
        'res-033' => ['fill' => ['forms.res-033.endorse'], 'approve' => ['forms.res-033.endorse']],
        'res-034' => ['fill' => ['forms.res-034.fill'], 'approve' => ['forms.res-034.fill']],
        'res-035' => ['fill' => ['forms.res-035.record'], 'approve' => ['forms.res-035.record']],
        'res-036' => ['fill' => ['forms.res-036.evaluate'], 'approve' => ['forms.res-036.evaluate']],
        'res-037' => ['fill' => ['forms.res-037.sign'], 'approve' => ['forms.res-037.sign']],
        'res-038' => ['fill' => ['forms.res-038.endorse'], 'approve' => ['forms.res-038.endorse']],
        'res-039' => ['fill' => ['forms.res-039.fill'], 'approve' => ['forms.res-039.approve']],
        'res-040' => ['fill' => ['forms.res-040.endorse'], 'approve' => ['forms.res-040.receive', 'forms.res-040.endorse']],
        'res-041' => ['fill' => ['forms.res-041.fill', 'forms.res-041.endorse'], 'approve' => ['forms.res-041.receive', 'forms.res-041.endorse']],
        'res-042' => ['fill' => ['forms.res-042.submit'], 'approve' => ['forms.res-042.submit']],
        'res-043a' => ['fill' => ['forms.res-043a.validate'], 'approve' => ['forms.res-043a.validate']],
        'res-043b' => ['fill' => ['forms.res-043b.validate'], 'approve' => ['forms.res-043b.validate']],
        'res-044' => ['fill' => ['forms.res-044.endorse'], 'approve' => ['forms.res-044.endorse']],
        'res-045' => ['fill' => ['forms.res-045.certify'], 'approve' => ['forms.res-045.certify']],
        'res-046' => ['fill' => ['forms.res-046.certify'], 'approve' => ['forms.res-046.certify']],
        'res-047' => ['fill' => ['forms.res-047.endorse'], 'approve' => ['forms.res-047.endorse']],
        'res-048' => ['fill' => ['forms.res-048.fill'], 'approve' => ['forms.res-048.fill']],
        'res-049' => ['fill' => ['forms.res-049.sign'], 'approve' => ['forms.res-049.sign']],
    ];

    public function canInitiate(User $user, OfficialFormDefinition $definition, ?ResearchClassGroup $group = null, ?ResearchClass $class = null): bool
    {
        $code = strtolower($definition->code);
        $allowedPermissions = self::FORM_ACTION_PERMISSIONS[$code]['fill'] ?? ["forms.{$code}.fill", "forms.{$code}.submit"];

        $hasPerm = false;
        foreach ($allowedPermissions as $perm) {
            if ($user->hasPermissionTo($perm)) {
                $hasPerm = true;
                break;
            }
        }

        if (! $hasPerm && ! $this->isSystemAdmin($user)) {
            return false;
        }

        if ($definition->ownership_scope === 'research_group' && $group !== null) {
            $isGroupContext = (int) $user->research_class_group_id === (int) $group->id
                || (int) $group->leader_student_id === (int) $user->id
                || (int) $group->adviser_id === (int) $user->id
                || $definition->cardinality === 'per_actor'
                || $this->isSystemAdmin($user);

            return $hasPerm && $isGroupContext;
        }

        if ($definition->ownership_scope === 'research_class' && $class !== null) {
            return $class->facilitator_id === $user->id
                || $this->isSystemAdmin($user);
        }

        return true;
    }

    public function canSubmit(User $user, OfficialFormInstance $instance): bool
    {
        $code = strtolower($instance->definition->code);
        $allowedPermissions = self::FORM_ACTION_PERMISSIONS[$code]['fill'] ?? ["forms.{$code}.submit", "forms.{$code}.fill"];

        $hasSubmitPerm = false;
        foreach ($allowedPermissions as $perm) {
            if ($user->hasPermissionTo($perm)) {
                $hasSubmitPerm = true;
                break;
            }
        }

        if (! $hasSubmitPerm && ! $this->isSystemAdmin($user)) {
            return false;
        }

        return $this->checkContextualAccess($user, $instance);
    }

    public function canAssignActor(User $assigner, OfficialFormInstance $instance): bool
    {
        if ($this->isSystemAdmin($assigner)) {
            return true;
        }

        if ($instance->research_class_group_id !== null) {
            $group = $instance->group;
            if ($group !== null && ($group->adviser_id === $assigner->id || $group->created_by === $assigner->id)) {
                return true;
            }
        }

        if ($instance->research_class_id !== null) {
            $class = $instance->researchClass;
            if ($class !== null && $class->facilitator_id === $assigner->id) {
                return true;
            }
        }

        return $instance->initiated_by === $assigner->id
            || $instance->actorAssignments()->where('user_id', $assigner->id)->where('status', 'active')->exists();
    }

    public function canApprove(User $user, OfficialFormInstance $instance): bool
    {
        $code = strtolower($instance->definition->code);
        $allowedPermissions = self::FORM_ACTION_PERMISSIONS[$code]['approve'] ?? ["forms.{$code}.approve", "forms.{$code}.endorse"];

        $hasPerm = false;
        foreach ($allowedPermissions as $perm) {
            if ($user->hasPermissionTo($perm)) {
                $hasPerm = true;
                break;
            }
        }

        if (! $hasPerm && ! $this->isSystemAdmin($user)) {
            return false;
        }

        return $this->checkAcademicContextualAccess($user, $instance);
    }

    public function checkContextualAccess(User $user, OfficialFormInstance $instance): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($instance->research_class_group_id !== null) {
            $group = $instance->group;
            if ($group !== null) {
                if ((int) $user->research_class_group_id === (int) $group->id || (int) $group->leader_student_id === (int) $user->id) {
                    return true;
                }
                if ((int) $group->adviser_id === (int) $user->id) {
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

    private function checkAcademicContextualAccess(User $user, OfficialFormInstance $instance): bool
    {
        if ($instance->research_class_group_id !== null) {
            $group = $instance->group;
            if ($group !== null && $group->adviser_id === $user->id) {
                return true;
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

    private function isSystemAdmin(User $user): bool
    {
        return $user->can('users.manage');
    }
}
