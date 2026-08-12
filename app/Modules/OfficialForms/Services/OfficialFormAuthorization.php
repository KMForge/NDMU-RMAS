<?php

namespace App\Modules\OfficialForms\Services;

use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;

class OfficialFormAuthorization
{
    /** @var array<string, array<string, list<string>>> */
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
        'res-040' => ['fill' => ['forms.res-040.endorse'], 'endorse' => ['forms.res-040.endorse'], 'receive' => ['forms.res-040.receive'], 'approve' => ['forms.res-040.receive', 'forms.res-040.endorse']],
        'res-041' => ['fill' => ['forms.res-041.fill'], 'endorse' => ['forms.res-041.endorse'], 'receive' => ['forms.res-041.receive'], 'approve' => ['forms.res-041.receive', 'forms.res-041.endorse']],
        'res-042' => ['fill' => ['forms.res-042.submit'], 'approve' => ['forms.res-042.submit']],
        'res-043a' => ['fill' => ['forms.res-043a.validate'], 'validate' => ['forms.res-043a.validate'], 'approve' => ['forms.res-043a.validate']],
        'res-043b' => ['fill' => ['forms.res-043b.validate'], 'validate' => ['forms.res-043b.validate'], 'approve' => ['forms.res-043b.validate']],
        'res-044' => ['fill' => ['forms.res-044.endorse'], 'approve' => ['forms.res-044.endorse']],
        'res-045' => ['fill' => ['forms.res-045.certify'], 'certify' => ['forms.res-045.certify'], 'approve' => ['forms.res-045.certify']],
        'res-046' => ['fill' => ['forms.res-046.certify'], 'certify' => ['forms.res-046.certify'], 'approve' => ['forms.res-046.certify']],
        'res-047' => ['fill' => ['forms.res-047.endorse'], 'approve' => ['forms.res-047.endorse']],
        'res-048' => ['fill' => ['forms.res-048.fill'], 'approve' => ['forms.res-048.fill']],
        'res-049' => ['fill' => ['forms.res-049.sign'], 'approve' => ['forms.res-049.sign']],
    ];

    /** @var array<string, array<string, string>> */
    public const FORM_ACTION_ACTOR_TYPES = [
        'res-036' => ['evaluate' => 'panelist'],
        'res-037' => ['sign' => 'panelist'],
        'res-040' => ['endorse' => 'adviser', 'receive' => 'research_instructor'],
        'res-041' => ['fill' => 'research_instructor', 'endorse' => 'research_instructor', 'receive' => 'program_coordinator'],
        'res-043a' => ['validate' => 'instrument_validator'],
        'res-043b' => ['validate' => 'instrument_validator'],
        'res-045' => ['certify' => 'language_editor'],
        'res-046' => ['certify' => 'technical_editor'],
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

        if (! $hasPerm) {
            return false;
        }

        if ($definition->ownership_scope === 'research_group' && $group !== null) {
            $requiredActorType = self::FORM_ACTION_ACTOR_TYPES[$code]['fill'] ?? null;

            $hasGroupActorAssignment = $requiredActorType !== null && OfficialFormInstance::query()
                ->where('research_class_group_id', $group->id)
                ->whereHas('actorAssignments', fn ($q) => $q->where('user_id', $user->id)->where('actor_type', $requiredActorType)->where('status', 'active'))
                ->exists();

            $isGroupContext = (int) $user->research_class_group_id === (int) $group->id
                || (int) $group->leader_student_id === (int) $user->id
                || (int) $group->adviser_id === (int) $user->id
                || (int) $group->created_by === (int) $user->id
                || $hasGroupActorAssignment;

            return $hasPerm && $isGroupContext;
        }

        if ($definition->ownership_scope === 'research_class' && $class !== null) {
            $hasClassActorAssignment = OfficialFormInstance::query()
                ->where('research_class_id', $class->id)
                ->whereHas('actorAssignments', fn ($q) => $q->where('user_id', $user->id)->where('status', 'active'))
                ->exists();

            return (int) $class->facilitator_id === (int) $user->id || $hasClassActorAssignment;
        }

        return true;
    }

    public function canSubmit(User $user, OfficialFormInstance $instance): bool
    {
        return $this->canPerformAction($user, $instance, 'fill');
    }

    public function canCertify(User $user, OfficialFormInstance $instance): bool
    {
        return $this->canPerformAction($user, $instance, 'certify');
    }

    public function canApprove(User $user, OfficialFormInstance $instance, string $action = 'approve'): bool
    {
        return $this->canPerformAction($user, $instance, $action);
    }

    public function canAssignActor(User $assigner, OfficialFormInstance $instance): bool
    {
        if ($this->isSystemAdmin($assigner)) {
            return true;
        }

        if ($instance->research_class_group_id !== null) {
            $group = $instance->group;
            if ($group !== null && ((int) $group->adviser_id === (int) $assigner->id || (int) $group->created_by === (int) $assigner->id)) {
                return true;
            }
        }

        if ($instance->research_class_id !== null) {
            $class = $instance->researchClass;
            if ($class !== null && (int) $class->facilitator_id === (int) $assigner->id) {
                return true;
            }
        }

        return false;
    }

    public function canPerformAction(User $user, OfficialFormInstance $instance, string $action): bool
    {
        $code = strtolower($instance->definition->code);
        $allowedPermissions = self::FORM_ACTION_PERMISSIONS[$code][$action] ?? self::FORM_ACTION_PERMISSIONS[$code]['approve'] ?? ["forms.{$code}.{$action}"];

        $hasPerm = false;
        foreach ($allowedPermissions as $perm) {
            if ($user->hasPermissionTo($perm)) {
                $hasPerm = true;
                break;
            }
        }

        if (! $hasPerm) {
            return false;
        }

        $requiredActorType = self::FORM_ACTION_ACTOR_TYPES[$code][$action] ?? null;

        if ($requiredActorType !== null) {
            return $this->checkSpecificActorTypeContext($user, $instance, $requiredActorType);
        }

        return $this->checkAcademicContextualAccess($user, $instance);
    }

    public function checkContextualAccess(User $user, OfficialFormInstance $instance): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $this->checkAcademicContextualAccess($user, $instance);
    }

    private function checkSpecificActorTypeContext(User $user, OfficialFormInstance $instance, string $requiredActorType): bool
    {
        if ($requiredActorType === 'adviser') {
            return $instance->group !== null && (int) $instance->group->adviser_id === (int) $user->id;
        }

        if ($requiredActorType === 'facilitator') {
            return $instance->researchClass !== null && (int) $instance->researchClass->facilitator_id === (int) $user->id;
        }

        return $instance->actorAssignments()
            ->where('user_id', $user->id)
            ->where('actor_type', $requiredActorType)
            ->where('status', 'active')
            ->exists()
            || ($instance->group !== null && OfficialFormInstance::query()
                ->where('research_class_group_id', $instance->group->id)
                ->whereHas('actorAssignments', fn ($q) => $q->where('user_id', $user->id)->where('actor_type', $requiredActorType)->where('status', 'active'))
                ->exists());
    }

    private function checkAcademicContextualAccess(User $user, OfficialFormInstance $instance): bool
    {
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
            if ($class !== null && (int) $class->facilitator_id === (int) $user->id) {
                return true;
            }
        }

        return $instance->actorAssignments()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists()
            || (int) $instance->initiated_by === (int) $user->id;
    }

    private function isSystemAdmin(User $user): bool
    {
        return $user->can('users.manage');
    }
}
