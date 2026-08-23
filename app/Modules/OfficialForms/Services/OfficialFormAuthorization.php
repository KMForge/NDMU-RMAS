<?php

namespace App\Modules\OfficialForms\Services;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\DefenseEvaluationRound;
use App\Models\DefensePanelAssignment;
use App\Models\DefenseSchedule;
use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassActorAssignment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;

class OfficialFormAuthorization
{
    /** @var array<string, array<string, list<string>>> */
    public const FORM_ACTION_PERMISSIONS = [
        'res-026' => [
            'fill' => ['forms.res-026.fill', 'forms.res-026.submit'],
            'sign_chairperson' => ['evaluations.create'],
            'sign_member_1' => ['evaluations.create'],
            'sign_member_2' => ['evaluations.create'],
            'endorse' => ['forms.res-026.approve', 'forms.res-041.receive'],
            'approve' => ['dashboards.dean.view', 'forms.res-047.approve'],
        ],
        'res-027' => ['fill' => ['forms.res-027.respond'], 'respond' => ['forms.res-027.respond']],
        'res-028' => ['fill' => ['forms.res-028.respond'], 'respond' => ['forms.res-028.respond']],
        'res-029' => ['fill' => ['forms.res-029.respond'], 'respond' => ['forms.res-029.respond']],
        'res-030' => ['fill' => ['forms.res-030.submit'], 'approve' => ['forms.res-030.approve', 'dashboards.facilitator.view']],
        'res-031' => ['fill' => ['forms.res-031.fill'], 'sign' => ['forms.res-031.fill']],
        'res-032' => ['fill' => ['forms.res-032.fill'], 'sign' => ['forms.res-032.fill']],
        'res-033' => ['fill' => ['forms.res-033.endorse'], 'endorse' => ['forms.res-033.endorse'], 'approve' => ['dashboards.facilitator.view']],
        'res-034' => ['fill' => ['forms.res-034.fill']],
        'res-035' => ['fill' => ['forms.res-035.record'], 'record' => ['forms.res-035.record']],
        'res-036' => ['fill' => ['forms.res-036.evaluate'], 'evaluate' => ['forms.res-036.evaluate']],
        'res-037' => ['fill' => ['forms.res-037.sign'], 'sign' => ['forms.res-037.sign']],
        'res-038' => ['fill' => ['forms.res-038.endorse'], 'endorse' => ['forms.res-038.endorse'], 'approve' => ['dashboards.facilitator.view']],
        'res-039' => ['fill' => ['forms.res-039.fill'], 'sign' => ['forms.res-039.fill']],
        'res-040' => ['view' => ['forms.res-040.view'], 'fill' => ['forms.res-040.endorse'], 'endorse' => ['forms.res-040.endorse'], 'receive' => ['forms.res-040.receive', 'dashboards.facilitator.view']],
        'res-041' => ['view' => ['forms.res-041.view'], 'fill' => ['forms.res-041.fill'], 'endorse' => ['forms.res-041.endorse'], 'receive' => ['forms.res-041.receive']],
        'res-042' => ['fill' => ['forms.res-042.submit'], 'approve' => ['forms.res-042.submit']],
        'res-043a' => ['view' => ['forms.res-043a.view'], 'fill' => ['forms.res-043a.validate'], 'validate' => ['forms.res-043a.validate']],
        'res-043b' => ['view' => ['forms.res-043b.view'], 'fill' => ['forms.res-043b.validate'], 'validate' => ['forms.res-043b.validate']],
        'res-044' => ['fill' => ['forms.res-044.endorse'], 'endorse' => ['forms.res-044.endorse'], 'approve' => ['dashboards.facilitator.view']],
        'res-045' => ['view' => ['forms.res-045.view'], 'fill' => ['forms.res-045.certify'], 'certify' => ['forms.res-045.certify']],
        'res-046' => ['view' => ['forms.res-046.view'], 'fill' => ['forms.res-046.certify'], 'certify' => ['forms.res-046.certify']],
        'res-047' => ['fill' => ['forms.res-047.endorse'], 'approve' => ['forms.res-047.approve'], 'endorse' => ['forms.res-047.endorse']],
        'res-048' => ['fill' => ['forms.res-048.fill']],
        'res-049' => ['fill' => ['forms.res-049.sign'], 'sign_authorship' => ['forms.res-049.sign']],
    ];

    /** @var array<string, array<string, string>> */
    public const FORM_ACTION_ACTOR_TYPES = [
        'res-026' => [
            'sign_chairperson' => 'title_panel_chairperson',
            'sign_member_1' => 'title_panel_member_1',
            'sign_member_2' => 'title_panel_member_2',
            'endorse' => 'program_coordinator',
            'approve' => 'dean',
        ],
        'res-027' => ['respond' => 'adviser'],
        'res-028' => ['respond' => 'panelist'],
        'res-029' => ['respond' => 'language_editor'],
        'res-030' => ['approve' => 'facilitator'],
        'res-031' => ['sign' => 'adviser'],
        'res-032' => ['sign' => 'specialist'],
        'res-033' => ['endorse' => 'adviser', 'approve' => 'facilitator'],
        'res-034' => ['fill' => 'panel_chair'],
        'res-035' => ['record' => 'panel_chair'],
        'res-036' => ['evaluate' => 'panelist'],
        'res-037' => ['sign' => 'panel_chair'],
        'res-038' => ['endorse' => 'facilitator', 'approve' => 'adviser'],
        'res-039' => ['sign' => 'adviser'],
        'res-040' => ['fill' => 'adviser', 'endorse' => 'adviser', 'receive' => 'research_instructor'],
        'res-041' => ['fill' => 'research_instructor', 'endorse' => 'research_instructor', 'receive' => 'program_coordinator'],
        'res-042' => ['approve' => 'facilitator'],
        'res-043a' => ['fill' => 'instrument_validator', 'validate' => 'instrument_validator'],
        'res-043b' => ['fill' => 'instrument_validator', 'validate' => 'instrument_validator'],
        'res-044' => ['endorse' => 'adviser', 'approve' => 'facilitator'],
        'res-045' => ['fill' => 'language_editor', 'certify' => 'language_editor'],
        'res-046' => ['fill' => 'technical_editor', 'certify' => 'technical_editor'],
        'res-047' => ['fill' => 'adviser', 'endorse' => 'adviser', 'approve' => 'dean'],
        'res-049' => ['sign_authorship' => 'student_researcher'],
    ];

    /**
     * Verified academic state transitions. A target status never implies an action;
     * callers must name the action and it must exist here.
     *
     * @var array<string, array<string, array{from: list<string>, to: string}>>
     */
    public const FORM_WORKFLOWS = [
        'res-026' => [
            'sign_chairperson' => ['from' => ['submitted'], 'to' => 'submitted'],
            'sign_member_1' => ['from' => ['submitted'], 'to' => 'submitted'],
            'sign_member_2' => ['from' => ['submitted'], 'to' => 'submitted'],
            'endorse' => ['from' => ['submitted'], 'to' => 'endorsed'],
            'approve' => ['from' => ['endorsed'], 'to' => 'approved'],
        ],
        'res-027' => [
            'respond' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action'], 'to' => 'approved'],
        ],
        'res-028' => [
            'respond' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action'], 'to' => 'approved'],
        ],
        'res-029' => [
            'respond' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action'], 'to' => 'approved'],
        ],
        'res-030' => [
            'approve' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'approved'],
        ],
        'res-031' => [
            'sign' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'signed'],
        ],
        'res-032' => [
            'sign' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'completed'],
        ],
        'res-033' => [
            'endorse' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'endorsed'],
            'approve' => ['from' => ['draft', 'submitted', 'endorsed', 'in_progress'], 'to' => 'approved'],
        ],
        'res-034' => [
            'fill' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'completed'],
        ],
        'res-035' => [
            'record' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'completed'],
        ],
        'res-037' => ['sign' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'signed']],
        'res-038' => [
            'endorse' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'endorsed'],
            'approve' => ['from' => ['draft', 'submitted', 'endorsed', 'in_progress'], 'to' => 'approved'],
        ],
        'res-039' => [
            'sign' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'approved'],
        ],
        'res-040' => [
            'endorse' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action'], 'to' => 'endorsed'],
            'receive' => ['from' => ['draft', 'submitted', 'endorsed', 'in_progress'], 'to' => 'approved'],
        ],
        'res-041' => [
            'endorse' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action'], 'to' => 'endorsed'],
            'receive' => ['from' => ['draft', 'submitted', 'endorsed', 'in_progress'], 'to' => 'approved'],
        ],
        'res-042' => [
            'approve' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'approved'],
        ],
        'res-043a' => ['validate' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'completed']],
        'res-043b' => ['validate' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'completed']],
        'res-044' => [
            'endorse' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'endorsed'],
            'approve' => ['from' => ['draft', 'submitted', 'endorsed', 'in_progress'], 'to' => 'approved'],
        ],
        'res-045' => ['certify' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action'], 'to' => 'completed']],
        'res-046' => ['certify' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action'], 'to' => 'completed']],
        'res-047' => [
            'endorse' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'endorsed'],
            'approve' => ['from' => ['draft', 'submitted', 'endorsed', 'in_progress'], 'to' => 'approved'],
        ],
        'res-049' => [
            'sign_authorship' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'completed'],
        ],
    ];

    public function canInitiateDefenseEvaluation(User $user, DefenseSchedule $schedule): bool
    {
        if ($user->user_type !== UserType::Faculty) {
            return false;
        }

        if ($user->status !== AccountStatus::Active || $user->approved_at === null || $user->email_verified_at === null) {
            return false;
        }

        if (! $user->hasPermissionTo('forms.res-036.evaluate')) {
            return false;
        }

        if ($schedule->status !== 'current') {
            return false;
        }

        $defense = $schedule->defense;
        if (! $defense || $defense->status !== 'scheduled' || (int) $defense->current_schedule_id !== (int) $schedule->id) {
            return false;
        }

        return DefensePanelAssignment::where('defense_id', $defense->id)
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->exists();
    }

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

            $isGroupContext = $this->isCurrentGroupMember($user, $group)
                || (int) $group->leader_student_id === (int) $user->id
                || (int) $group->adviser_id === (int) $user->id
                || (int) $group->created_by === (int) $user->id
                || $this->isSystemAdmin($user)
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

        $hasRequiredPermission = $code === 'res-026'
            ? $this->hasExplicitPermission($user, $allowedPermissions ?? [])
            : $this->hasAnyPermission($user, $allowedPermissions ?? []);

        if ($allowedPermissions === null || ! $hasRequiredPermission) {
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

    public function canSignAuthorship(User $user, OfficialFormInstance $instance): bool
    {
        return $this->canPerformAction($user, $instance, 'sign_authorship');
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
        if ($user->status !== AccountStatus::Active || $user->approved_at === null || $user->email_verified_at === null) {
            return false;
        }

        $code = strtolower($instance->definition->code);
        if ($code === 'res-026' && $user->user_type !== UserType::Faculty) {
            return false;
        }
        $allowedPermissions = self::FORM_ACTION_PERMISSIONS[$code][$action] ?? null;

        $hasRequiredPermission = $code === 'res-026'
            ? $this->hasExplicitPermission($user, $allowedPermissions ?? [])
            : $this->hasAnyPermission($user, $allowedPermissions ?? []);

        if ($allowedPermissions === null || ! $hasRequiredPermission) {
            return false;
        }

        $requiredActorType = self::FORM_ACTION_ACTOR_TYPES[$code][$action] ?? null;

        if ($code === 'res-026') {
            $presentation = $instance->titlePresentation;
            if ($presentation === null) {
                return false;
            }
            if (str_starts_with($action, 'sign_') && $presentation->status !== 'awaiting_panel_signatures') {
                return false;
            }
            if ($action === 'endorse' && $presentation->status !== 'awaiting_program_coordinator') {
                return false;
            }
            if ($action === 'approve' && $presentation->status !== 'awaiting_dean') {
                return false;
            }
        }

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
        $code = strtolower($instance->definition->code);
        if (isset(self::FORM_WORKFLOWS[$code][$action])) {
            return self::FORM_WORKFLOWS[$code][$action];
        }

        return match ($action) {
            'endorse' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action'], 'to' => 'endorsed'],
            'receive', 'approve' => ['from' => ['draft', 'submitted', 'endorsed', 'in_progress', 'pending_action'], 'to' => 'approved'],
            'certify', 'validate' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action'], 'to' => 'completed'],
            default => null,
        };
    }

    public function requiredActorType(OfficialFormInstance $instance, string $action): ?string
    {
        return self::FORM_ACTION_ACTOR_TYPES[strtolower($instance->definition->code)][$action] ?? null;
    }

    private function checkSpecificActorTypeContext(User $user, OfficialFormInstance $instance, string $requiredActorType): bool
    {
        $titlePanelPosition = match ($requiredActorType) {
            'title_panel_chairperson' => 'chairperson',
            'title_panel_member_1' => 'member_1',
            'title_panel_member_2' => 'member_2',
            default => null,
        };
        if ($titlePanelPosition !== null) {
            return $instance->titlePresentation !== null
                && $instance->titlePresentation->defense->activePanelAssignments()
                    ->where('user_id', $user->id)
                    ->where('panel_position', $titlePanelPosition)
                    ->exists();
        }

        if ($requiredActorType === 'adviser') {
            return $instance->group !== null && (int) $instance->group->adviser_id === (int) $user->id;
        }

        if ($requiredActorType === 'facilitator') {
            return ($instance->researchClass !== null && (int) $instance->researchClass->facilitator_id === (int) $user->id)
                || ($instance->group !== null && $instance->group->researchClass !== null && (int) $instance->group->researchClass->facilitator_id === (int) $user->id);
        }

        if ($requiredActorType === 'program_coordinator') {
            $class = $instance->researchClass ?? $instance->group?->researchClass;

            return $class !== null && $this->hasClassActorAssignment($user, $class, 'program_coordinator');
        }

        if ($requiredActorType === 'dean') {
            $class = $instance->researchClass ?? $instance->group?->researchClass;

            return $class !== null && $this->hasClassActorAssignment($user, $class, 'dean');
        }

        if ($requiredActorType === 'student_researcher') {
            return $instance->group !== null && $this->isCurrentGroupMember($user, $instance->group);
        }

        if (in_array($requiredActorType, ['panelist', 'panel_chair'], true)) {
            if ($instance->source_type === DefenseEvaluationRound::class && $instance->source) {
                return (int) $instance->source->summary_signer_user_id === (int) $user->id;
            }
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
        if (strtoupper($instance->definition->code) === 'RES-026') {
            if ($this->isSystemAdmin($user)) {
                return true;
            }

            $group = $instance->group;
            $class = $instance->researchClass ?? $group?->researchClass;

            return $group !== null && (
                $this->isCurrentGroupMember($user, $group)
                || (int) $group->adviser_id === (int) $user->id
                || (int) $class?->facilitator_id === (int) $user->id
                || ($instance->titlePresentation !== null && $instance->titlePresentation->defense->activePanelAssignments()->where('user_id', $user->id)->exists())
                || ($class !== null && ResearchClassActorAssignment::query()->where('research_class_id', $class->id)->where('user_id', $user->id)->where('status', 'active')->exists())
            );
        }

        if ($this->isSystemAdmin($user) || $user->can('users.manage') || $user->can('dashboards.dean.view') || $user->hasRole('college-dean') || $user->hasRole('dean')) {
            return true;
        }

        if ($instance->research_class_group_id !== null) {
            $group = $instance->group;
            if ($group !== null) {
                if ($this->isCurrentGroupMember($user, $group) || (int) $group->leader_student_id === (int) $user->id) {
                    return true;
                }
                if ((int) $group->adviser_id === (int) $user->id) {
                    return true;
                }
                if ($group->researchClass !== null && (int) $group->researchClass->facilitator_id === (int) $user->id) {
                    return true;
                }
                if ($instance->titlePresentation !== null && $instance->titlePresentation->defense->activePanelAssignments()->where('user_id', $user->id)->exists()) {
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

    public function isCurrentGroupMember(User $user, ResearchClassGroup $group): bool
    {
        return ResearchClassGroupMember::query()
            ->where('research_class_group_id', $group->id)
            ->where('student_id', $user->id)
            ->exists();
    }

    /** @param list<string> $permissions */
    private function hasExplicitPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            try {
                if ($user->hasPermissionTo($permission)) {
                    return true;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return false;
    }

    /** @param list<string> $permissions */
    private function hasAnyPermission(User $user, array $permissions): bool
    {
        if ($user->can('users.manage') || $user->hasRole('college-dean') || $user->hasRole('dean')) {
            return true;
        }

        foreach ($permissions as $permission) {
            try {
                if ($user->hasPermissionTo($permission) || $user->can($permission)) {
                    return true;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return false;
    }

    private function isSystemAdmin(User $user): bool
    {
        return $user->can('users.manage');
    }
}
