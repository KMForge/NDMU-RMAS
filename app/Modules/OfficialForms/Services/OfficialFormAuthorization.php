<?php

namespace App\Modules\OfficialForms\Services;

use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassActorAssignment;
use App\Models\ResearchClassGroup;
use App\Models\User;

class OfficialFormAuthorization
{
    /** @var array<string, array<string, list<string>>> */
    public const FORM_ACTION_PERMISSIONS = [
        'res-026' => ['fill' => ['forms.res-026.fill', 'forms.res-026.submit']],
        'res-027' => ['fill' => ['forms.res-027.respond']],
        'res-028' => ['fill' => ['forms.res-028.respond']],
        'res-029' => ['fill' => ['forms.res-029.respond']],
        'res-030' => ['fill' => ['forms.res-030.submit']],
        'res-031' => ['fill' => ['forms.res-031.fill']],
        'res-032' => ['fill' => ['forms.res-032.fill']],
        'res-033' => ['fill' => ['forms.res-033.endorse']],
        'res-034' => ['fill' => ['forms.res-034.fill']],
        'res-035' => ['fill' => ['forms.res-035.record']],
        'res-036' => ['fill' => ['forms.res-036.evaluate']],
        'res-037' => ['fill' => ['forms.res-037.sign']],
        'res-038' => ['fill' => ['forms.res-038.endorse']],
        'res-039' => ['fill' => ['forms.res-039.fill']],
        'res-040' => ['view' => ['forms.res-040.view'], 'fill' => ['forms.res-040.endorse'], 'endorse' => ['forms.res-040.endorse'], 'receive' => ['forms.res-040.receive']],
        'res-041' => ['view' => ['forms.res-041.view'], 'fill' => ['forms.res-041.fill'], 'endorse' => ['forms.res-041.endorse'], 'receive' => ['forms.res-041.receive']],
        'res-042' => ['fill' => ['forms.res-042.submit']],
        'res-043a' => ['view' => ['forms.res-043a.view'], 'fill' => ['forms.res-043a.validate'], 'validate' => ['forms.res-043a.validate']],
        'res-043b' => ['view' => ['forms.res-043b.view'], 'fill' => ['forms.res-043b.validate'], 'validate' => ['forms.res-043b.validate']],
        'res-044' => ['fill' => ['forms.res-044.endorse']],
        'res-045' => ['view' => ['forms.res-045.view'], 'fill' => ['forms.res-045.certify'], 'certify' => ['forms.res-045.certify']],
        'res-046' => ['view' => ['forms.res-046.view'], 'fill' => ['forms.res-046.certify'], 'certify' => ['forms.res-046.certify']],
        'res-047' => ['fill' => ['forms.res-047.endorse'], 'approve' => ['forms.res-047.approve'], 'endorse' => ['forms.res-047.endorse']],
        'res-048' => ['fill' => ['forms.res-048.fill']],
        'res-049' => ['fill' => ['forms.res-049.sign']],
    ];

    /** @var array<string, array<string, string>> */
    public const FORM_ACTION_ACTOR_TYPES = [
        'res-036' => ['evaluate' => 'panelist'],
        'res-037' => ['sign' => 'panelist'],
        'res-040' => ['fill' => 'adviser', 'endorse' => 'adviser', 'receive' => 'research_instructor'],
        'res-041' => ['fill' => 'research_instructor', 'endorse' => 'research_instructor', 'receive' => 'program_coordinator'],
        'res-043a' => ['validate' => 'instrument_validator'],
        'res-043b' => ['validate' => 'instrument_validator'],
        'res-045' => ['certify' => 'language_editor'],
        'res-046' => ['certify' => 'technical_editor'],
        'res-047' => ['fill' => 'adviser', 'endorse' => 'adviser', 'approve' => 'dean'],
    ];

    /**
     * Verified academic state transitions. A target status never implies an action;
     * callers must name the action and it must exist here.
     *
     * @var array<string, array<string, array{from: list<string>, to: string}>>
     */
    public const FORM_WORKFLOWS = [
        'res-040' => [
            'endorse' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action'], 'to' => 'endorsed'],
            'receive' => ['from' => ['endorsed'], 'to' => 'approved'],
        ],
        'res-041' => [
            'endorse' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action'], 'to' => 'endorsed'],
            'receive' => ['from' => ['endorsed'], 'to' => 'approved'],
        ],
        'res-043a' => ['validate' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'completed']],
        'res-043b' => ['validate' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'completed']],
        'res-045' => ['certify' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action'], 'to' => 'completed']],
        'res-046' => ['certify' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action'], 'to' => 'completed']],
        'res-047' => [
            'endorse' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'endorsed'],
            'approve' => ['from' => ['endorsed'], 'to' => 'approved'],
        ],
    ];

    public function canInitiate(User $user, OfficialFormDefinition $definition, ?ResearchClassGroup $group = null, ?ResearchClass $class = null): bool
    {
        $code = strtolower($definition->code);
        $allowedPermissions = self::FORM_ACTION_PERMISSIONS[$code]['fill'] ?? null;

        if ($allowedPermissions === null) {
            return false;
        }

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

            if ($requiredActorType === 'adviser') {
                return (int) $group->adviser_id === (int) $user->id;
            }

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
            $requiredActorType = self::FORM_ACTION_ACTOR_TYPES[$code]['fill'] ?? null;

            return $requiredActorType !== null
                && $this->hasClassActorAssignment($user, $class, $requiredActorType);
        }

        return true;
    }

    public function canSubmit(User $user, OfficialFormInstance $instance): bool
    {
        $code = strtolower($instance->definition->code);
        $allowedPermissions = self::FORM_ACTION_PERMISSIONS[$code]['fill'] ?? null;

        if ($allowedPermissions === null || ! $this->hasAnyPermission($user, $allowedPermissions)) {
            return false;
        }

        $requiredActorType = self::FORM_ACTION_ACTOR_TYPES[$code]['fill'] ?? null;

        if ($requiredActorType !== null) {
            return $this->checkSpecificActorTypeContext($user, $instance, $requiredActorType);
        }

        return $this->checkDraftContextualAccess($user, $instance);
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
        $allowedPermissions = self::FORM_ACTION_PERMISSIONS[$code][$action] ?? null;

        if ($allowedPermissions === null || ! $this->hasAnyPermission($user, $allowedPermissions)) {
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

        return $this->checkDraftContextualAccess($user, $instance);
    }

    /** @return array{from: list<string>, to: string}|null */
    public function transitionFor(OfficialFormInstance $instance, string $action): ?array
    {
        return self::FORM_WORKFLOWS[strtolower($instance->definition->code)][$action] ?? null;
    }

    private function checkSpecificActorTypeContext(User $user, OfficialFormInstance $instance, string $requiredActorType): bool
    {
        if ($requiredActorType === 'adviser') {
            return $instance->group !== null && (int) $instance->group->adviser_id === (int) $user->id;
        }

        if ($requiredActorType === 'facilitator') {
            return $instance->researchClass !== null && (int) $instance->researchClass->facilitator_id === (int) $user->id;
        }

        if ($requiredActorType === 'dean') {
            return $instance->group !== null
                && $this->hasClassActorAssignment($user, $instance->group->researchClass, 'dean');
        }

        return $instance->actorAssignments()
            ->where('user_id', $user->id)
            ->where('actor_type', $requiredActorType)
            ->where('status', 'active')
            ->exists()
            || ($instance->group !== null && OfficialFormInstance::query()
                ->where('research_class_group_id', $instance->group->id)
                ->whereHas('actorAssignments', fn ($q) => $q->where('user_id', $user->id)->where('actor_type', $requiredActorType)->where('status', 'active'))
                ->exists())
            || ($instance->researchClass !== null && $this->hasClassActorAssignment($user, $instance->researchClass, $requiredActorType));
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

        $classId = $instance->research_class_id ?? $instance->group?->research_class_id;
        if ($classId !== null && ResearchClassActorAssignment::query()
            ->where('research_class_id', $classId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists()) {
            return true;
        }

        return $instance->actorAssignments()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    private function checkDraftContextualAccess(User $user, OfficialFormInstance $instance): bool
    {
        if ($this->isSystemAdmin($user) || (int) $instance->initiated_by === (int) $user->id) {
            return true;
        }

        return $this->checkAcademicContextualAccess($user, $instance);
    }

    private function hasClassActorAssignment(User $user, ResearchClass $class, string $actorType): bool
    {
        return ResearchClassActorAssignment::query()
            ->where('research_class_id', $class->id)
            ->where('user_id', $user->id)
            ->where('actor_type', $actorType)
            ->where('status', 'active')
            ->exists();
    }

    /** @param list<string> $permissions */
    private function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    private function isSystemAdmin(User $user): bool
    {
        return $user->can('users.manage');
    }
}
