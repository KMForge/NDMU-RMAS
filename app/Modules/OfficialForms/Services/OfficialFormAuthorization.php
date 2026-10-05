<?php

namespace App\Modules\OfficialForms\Services;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\Defense;
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
    /** @var array<string, bool> */
    private array $groupPanelistCache = [];

    /** @var array<string, bool> */
    private array $groupMemberCache = [];

    /** @var array<string, bool> */
    private array $groupActorAssignmentCache = [];

    /** @var array<int, bool> */
    private array $evaluationRoundReleasedCache = [];

    public function __construct(
        private readonly InstitutionalActorResolver $institutionalActors = new InstitutionalActorResolver,
    ) {}

    public function isAssignedGroupPanelist(User $user, ResearchClassGroup $group): bool
    {
        $cacheKey = "{$user->id}:{$group->id}";
        if (array_key_exists($cacheKey, $this->groupPanelistCache)) {
            return $this->groupPanelistCache[$cacheKey];
        }

        return $this->groupPanelistCache[$cacheKey] = ($group->defenses()
            ->whereHas('activePanelAssignments', fn ($q) => $q->where('user_id', $user->id))
            ->exists()
            || $group->panelCommittees()
                ->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
                ->exists()
            || OfficialFormInstance::query()
                ->where('research_class_group_id', $group->id)
                ->whereHas('actorAssignments', fn ($q) => $q->where('user_id', $user->id)->where('actor_type', 'panelist')->where('status', 'active'))
                ->exists());
    }

    public function hasGroupActorAssignment(User $user, ResearchClassGroup $group, string $actorType): bool
    {
        $cacheKey = "{$user->id}:{$group->id}:{$actorType}";
        if (array_key_exists($cacheKey, $this->groupActorAssignmentCache)) {
            return $this->groupActorAssignmentCache[$cacheKey];
        }

        return $this->groupActorAssignmentCache[$cacheKey] = OfficialFormInstance::query()
            ->where('research_class_group_id', $group->id)
            ->whereHas('actorAssignments', fn ($q) => $q->where('user_id', $user->id)->where('actor_type', $actorType)->where('status', 'active'))
            ->exists();
    }

    public function isEvaluationRoundReleased(int|string $roundId): bool
    {
        $key = (int) $roundId;
        if (array_key_exists($key, $this->evaluationRoundReleasedCache)) {
            return $this->evaluationRoundReleasedCache[$key];
        }

        return $this->evaluationRoundReleasedCache[$key] = DefenseEvaluationRound::query()
            ->where('id', $key)
            ->where('status', 'released')
            ->exists();
    }

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
        'res-027' => [
            'fill' => ['forms.res-027.respond'],
            'endorse' => ['classes.assign-advisers', 'dashboards.facilitator.view', 'forms.res-027.view'],
            'respond' => ['forms.res-027.respond'],
            'conforme' => ['forms.res-027.respond'],
            'approve' => ['dashboards.dean.view', 'research.approve', 'forms.res-047.approve'],
        ],
        'res-028' => [
            'fill' => ['forms.res-028.respond'],
            'endorse' => ['classes.assign-advisers', 'dashboards.facilitator.view', 'forms.res-028.view'],
            'respond' => ['forms.res-028.respond'],
            'conforme' => ['forms.res-028.respond'],
            'approve' => ['dashboards.dean.view', 'research.approve', 'forms.res-047.approve'],
        ],
        'res-029' => [
            'fill' => ['forms.res-029.respond'],
            'endorse' => ['classes.assign-advisers', 'dashboards.facilitator.view', 'forms.res-029.view'],
            'respond' => ['forms.res-029.respond'],
            'conforme' => ['forms.res-029.respond'],
            'approve' => ['dashboards.dean.view', 'research.approve', 'forms.res-047.approve'],
        ],
        'res-030' => ['fill' => ['forms.res-030.submit'], 'approve' => ['forms.res-030.approve'], 'reject' => ['forms.res-030.approve']],
        'res-031' => ['fill' => ['forms.res-031.sign'], 'sign' => ['forms.res-031.sign']],
        'res-032' => ['fill' => ['forms.res-032.fill'], 'sign' => ['forms.res-032.fill']],
        'res-033' => ['fill' => ['forms.res-033.endorse'], 'endorse' => ['forms.res-033.endorse'], 'receive' => ['forms.res-033.endorse']],
        'res-034' => ['fill' => ['forms.res-034.fill']],
        'res-035' => ['fill' => ['forms.res-035.record'], 'record' => ['forms.res-035.record']],
        'res-036' => ['fill' => ['forms.res-036.evaluate'], 'evaluate' => ['forms.res-036.evaluate']],
        'res-037' => ['fill' => ['forms.res-037.sign'], 'sign' => ['forms.res-037.sign']],
        'res-038' => ['fill' => ['forms.res-038.endorse'], 'endorse' => ['forms.res-038.endorse'], 'conforme' => ['forms.res-038.endorse']],
        'res-039' => ['view' => ['forms.res-039.view', 'forms.res-039.approve'], 'fill' => ['forms.res-039.fill', 'forms.res-039.approve', 'evaluations.create'], 'sign' => ['forms.res-039.fill', 'forms.res-039.approve']],
        'res-040' => ['view' => ['forms.res-040.view'], 'fill' => ['forms.res-040.endorse'], 'endorse' => ['forms.res-040.endorse'], 'receive' => ['forms.res-040.receive', 'dashboards.facilitator.view']],
        'res-041' => ['view' => ['forms.res-041.view'], 'fill' => ['forms.res-041.fill'], 'endorse' => ['forms.res-041.endorse'], 'receive' => ['forms.res-041.receive']],
        'res-042' => ['fill' => ['forms.res-042.submit']],
        'res-043a' => ['view' => ['forms.res-043a.view'], 'fill' => ['forms.res-043a.validate'], 'validate' => ['forms.res-043a.validate']],
        'res-043b' => ['view' => ['forms.res-043b.view'], 'fill' => ['forms.res-043b.validate'], 'validate' => ['forms.res-043b.validate']],
        'res-044' => ['fill' => ['forms.res-044.endorse']],
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
        'res-027' => [
            'endorse' => 'program_coordinator',
            'respond' => 'adviser',
            'conforme' => 'adviser',
            'approve' => 'dean',
        ],
        'res-028' => [
            'endorse' => 'program_coordinator',
            'respond' => 'panelist',
            'conforme' => 'panelist',
            'approve' => 'dean',
        ],
        'res-029' => [
            'endorse' => 'program_coordinator',
            'respond' => 'language_editor',
            'conforme' => 'language_editor',
            'approve' => 'dean',
        ],
        'res-030' => ['approve' => 'authorized_reviewer', 'reject' => 'authorized_reviewer'],
        'res-031' => ['fill' => 'adviser', 'sign' => 'adviser'],
        'res-032' => ['sign' => 'specialist'],
        'res-033' => ['endorse' => 'adviser', 'receive' => 'program_head'],
        'res-034' => ['fill' => 'panel_chair'],
        'res-035' => ['record' => 'adviser'],
        'res-036' => ['fill' => 'panelist', 'evaluate' => 'panelist'],
        'res-037' => ['fill' => 'panel_chair', 'sign' => 'panel_chair'],
        'res-038' => ['endorse' => 'program_head', 'conforme' => 'adviser', 'receive' => 'adviser'],
        'res-039' => ['sign' => 'adviser'],
        'res-040' => ['fill' => 'adviser', 'endorse' => 'adviser', 'receive' => 'research_instructor'],
        'res-041' => ['fill' => 'research_instructor', 'endorse' => 'research_instructor', 'receive' => 'program_coordinator'],
        'res-043a' => ['fill' => 'instrument_validator', 'validate' => 'instrument_validator'],
        'res-043b' => ['fill' => 'instrument_validator', 'validate' => 'instrument_validator'],
        'res-045' => ['fill' => 'language_editor', 'certify' => 'language_editor'],
        'res-046' => ['fill' => 'technical_editor', 'certify' => 'technical_editor'],
        'res-047' => ['fill' => 'adviser', 'endorse' => 'adviser', 'approve' => 'dean'],
        'res-048' => ['fill' => 'student_researcher'],
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
            'endorse' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action', 'conformed', 'approved'], 'to' => 'endorsed'],
            'respond' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action', 'endorsed', 'conformed', 'approved'], 'to' => 'conformed'],
            'conforme' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action', 'endorsed', 'conformed', 'approved'], 'to' => 'conformed'],
            'approve' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action', 'endorsed', 'conformed'], 'to' => 'approved'],
        ],
        'res-028' => [
            'endorse' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action', 'conformed', 'approved'], 'to' => 'endorsed'],
            'respond' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action', 'endorsed', 'conformed', 'approved'], 'to' => 'conformed'],
            'conforme' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action', 'endorsed', 'conformed', 'approved'], 'to' => 'conformed'],
            'approve' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action', 'endorsed', 'conformed'], 'to' => 'approved'],
        ],
        'res-029' => [
            'endorse' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action', 'conformed', 'approved'], 'to' => 'endorsed'],
            'respond' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action', 'endorsed', 'conformed', 'approved'], 'to' => 'conformed'],
            'conforme' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action', 'endorsed', 'conformed', 'approved'], 'to' => 'conformed'],
            'approve' => ['from' => ['draft', 'submitted', 'in_progress', 'pending_action', 'endorsed', 'conformed'], 'to' => 'approved'],
        ],
        'res-030' => [
            'approve' => ['from' => ['submitted'], 'to' => 'approved'],
            'reject' => ['from' => ['submitted'], 'to' => 'rejected'],
        ],
        'res-031' => [
            'sign' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'signed'],
        ],
        'res-032' => [
            'sign' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'completed'],
        ],
        'res-033' => [
            'endorse' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'endorsed'],
            'receive' => ['from' => ['endorsed'], 'to' => 'received'],
        ],
        'res-034' => [
            'fill' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'completed'],
        ],
        'res-035' => [
            'record' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'completed'],
        ],
        'res-036' => [
            'evaluate' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'submitted'],
        ],
        'res-037' => ['sign' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'signed']],
        'res-038' => [
            'endorse' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'endorsed'],
            'conforme' => ['from' => ['draft', 'submitted', 'endorsed', 'in_progress'], 'to' => 'conformed'],
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
        'res-043a' => ['validate' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'completed']],
        'res-043b' => ['validate' => ['from' => ['draft', 'submitted', 'in_progress'], 'to' => 'completed']],
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
        if (! $defense || ! in_array($defense->status, ['scheduled', 'in_progress'], true) || (int) $defense->current_schedule_id !== (int) $schedule->id) {
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

        if (in_array($code, ['res-027', 'res-028'], true) && $group?->researchClass !== null) {
            return $this->institutionalActors->isProgramCoordinator($user, $group->researchClass, $group)
                && $user->can('classes.assign-advisers');
        }

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
            if ($code === 'res-030') {
                return $hasPerm && $group->isLeader($user);
            }

            $requiredActorType = self::FORM_ACTION_ACTOR_TYPES[$code]['fill'] ?? null;

            if ($requiredActorType === 'adviser') {
                return (int) $group->adviser_id === (int) $user->id;
            }

            $hasGroupActorAssignment = $requiredActorType !== null && $this->hasGroupActorAssignment($user, $group, $requiredActorType);

            $isGroupPanelist = $this->isAssignedGroupPanelist($user, $group);

            $isGroupContext = $this->isCurrentGroupMember($user, $group)
                || (int) $group->leader_student_id === (int) $user->id
                || (int) $group->adviser_id === (int) $user->id
                || (int) $group->created_by === (int) $user->id
                || $this->isSystemAdmin($user)
                || $hasGroupActorAssignment
                || $isGroupPanelist;

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
        if ($code === 'res-030' && ($instance->group === null || ! $instance->group->isLeader($user))) {
            return false;
        }
        if ($code === 'res-036' && $instance->initiated_by !== null && (int) $instance->initiated_by !== (int) $user->id) {
            return false;
        }

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
        if ($code === 'res-036' && $instance->initiated_by !== null && (int) $instance->initiated_by !== (int) $user->id) {
            return false;
        }

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

        if (in_array($code, ['res-027', 'res-028', 'res-029'], true) && $action === 'approve') {
            $version = $instance->currentVersion;
            if ($version === null) {
                return false;
            }
            $version->loadMissing('signatures');

            $hasCoordinatorSignature = $version->signatures->contains(
                fn ($s) => in_array($s->actor_type, ['program_coordinator', 'program_head'], true) || $s->academic_action === 'endorse'
            );

            $hasInviteeSignature = match ($code) {
                'res-027' => $version->signatures->contains(fn ($s) => $s->actor_type === 'adviser' || in_array($s->academic_action, ['respond', 'conforme'], true)),
                'res-028' => $version->signatures->contains(fn ($s) => $s->actor_type === 'panelist' || in_array($s->academic_action, ['respond', 'conforme'], true)),
                'res-029' => $version->signatures->contains(fn ($s) => $s->actor_type === 'language_editor' || in_array($s->academic_action, ['respond', 'conforme'], true)),
                default => false,
            };

            if (! $hasCoordinatorSignature || ! $hasInviteeSignature) {
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
        $code = strtolower($instance->definition->code ?? '');

        if (in_array($code, ['res-027', 'res-028', 'res-029'], true)) {
            return $this->invitationTransitionFor($instance, $action);
        }

        return self::FORM_WORKFLOWS[$code][$action] ?? null;
    }

    /** @return array{from: list<string>, to: string}|null */
    public function invitationTransitionFor(OfficialFormInstance $instance, string $action): ?array
    {
        $version = $instance->currentVersion;
        $version?->loadMissing('signatures');
        $hasInviteeSigned = $version?->signatures?->contains(
            fn ($s) => in_array($s->actor_type, ['panelist', 'adviser', 'language_editor'], true)
                || in_array($s->academic_action, ['respond', 'conforme'], true)
        ) ?? false;

        return match ($action) {
            'endorse' => [
                'from' => ['draft', 'submitted', 'in_progress', 'pending_action', 'endorsed', 'conformed', 'approved'],
                'to' => $hasInviteeSigned ? 'conformed' : 'endorsed',
            ],
            'respond', 'conforme' => [
                'from' => ['draft', 'submitted', 'in_progress', 'pending_action', 'endorsed', 'conformed', 'approved'],
                'to' => 'conformed',
            ],
            'approve' => [
                'from' => ['draft', 'submitted', 'in_progress', 'pending_action', 'endorsed', 'conformed'],
                'to' => 'approved',
            ],
            default => null,
        };
    }

    public function requiredActorType(OfficialFormInstance $instance, string $action): ?string
    {
        return self::FORM_ACTION_ACTOR_TYPES[strtolower($instance->definition->code)][$action] ?? null;
    }

    private function checkSpecificActorTypeContext(User $user, OfficialFormInstance $instance, string $requiredActorType): bool
    {
        if ($requiredActorType === 'authorized_reviewer') {
            if (! $user->can('users.manage') && ! $user->hasPermissionTo('forms.res-030.approve')) {
                return false;
            }

            if ($this->isSystemAdmin($user)) {
                return true;
            }

            $class = $instance->researchClass ?? $instance->group?->researchClass;
            if ($class === null) {
                return false;
            }

            if ((int) $class->facilitator_id === (int) $user->id
                || $this->institutionalActors->isProgramCoordinator($user, $class, $instance->group)
                || $this->institutionalActors->isDean($user)) {
                return true;
            }

            $hasCoordinatorFallback = ! $this->institutionalActors->hasConfiguredProgramCoordinator($class, $instance->group)
                && ($this->hasClassActorAssignment($user, $class, 'program_coordinator')
                    || $this->hasClassActorAssignment($user, $class, 'program_head'));
            $hasDeanFallback = ! $this->institutionalActors->hasConfiguredDean()
                && $this->hasClassActorAssignment($user, $class, 'dean');

            return $hasCoordinatorFallback || $hasDeanFallback;
        }

        $titlePanelPosition = match ($requiredActorType) {
            'title_panel_chairperson' => 'chairperson',
            'title_panel_member_1' => 'member_1',
            'title_panel_member_2' => 'member_2',
            default => null,
        };
        if ($titlePanelPosition !== null) {
            return $instance->titlePresentation !== null
                && $this->hasActivePanelAssignment(
                    $instance->titlePresentation->defense,
                    $user,
                    $titlePanelPosition,
                );
        }

        if ($requiredActorType === 'adviser') {
            if (strtolower($instance->definition->code) === 'res-027') {
                return $this->hasInstanceActorAssignment($instance, $user, 'adviser');
            }

            return $instance->group !== null && (int) $instance->group->adviser_id === (int) $user->id;
        }

        if ($requiredActorType === 'facilitator') {
            return ($instance->researchClass !== null && (int) $instance->researchClass->facilitator_id === (int) $user->id)
                || ($instance->group !== null && $instance->group->researchClass !== null && (int) $instance->group->researchClass->facilitator_id === (int) $user->id);
        }

        if ($requiredActorType === 'program_coordinator' || $requiredActorType === 'program_head') {
            $class = $instance->researchClass ?? $instance->group?->researchClass;

            if ($this->institutionalActors->hasConfiguredProgramCoordinator($class, $instance->group)) {
                return $this->institutionalActors->isProgramCoordinator($user, $class, $instance->group);
            }

            // Compatibility path for installations that have not assigned a
            // current Program Coordinator role. Once one exists, live role
            // resolution is authoritative and stale saved assignments close.
            return $class !== null && ($this->hasClassActorAssignment($user, $class, 'program_coordinator') || $this->hasClassActorAssignment($user, $class, 'program_head'));
        }

        if ($requiredActorType === 'dean') {
            $class = $instance->researchClass ?? $instance->group?->researchClass;

            if ($this->institutionalActors->hasConfiguredDean()) {
                return $this->institutionalActors->isDean($user);
            }

            // Compatibility path for installations that have not assigned the
            // institutional Dean role yet. Once a Dean role exists, the unique
            // institutional resolver is authoritative and this fallback closes.
            return $class !== null && $this->hasClassActorAssignment($user, $class, 'dean');
        }

        if ($requiredActorType === 'student_researcher') {
            return $instance->group !== null && $this->isCurrentGroupMember($user, $instance->group);
        }

        if (in_array($requiredActorType, ['panelist', 'panel_chair'], true)) {
            if ($instance->source_type === DefenseEvaluationRound::class && $instance->source) {
                return (int) $instance->source->summary_signer_user_id === (int) $user->id;
            }
            if (strtolower($instance->definition->code) === 'res-036') {
                return (int) $instance->initiated_by === (int) $user->id;
            }
        }

        return $this->hasInstanceActorAssignment($instance, $user, $requiredActorType)
            || ($instance->group !== null && OfficialFormInstance::query()
                ->where('research_class_group_id', $instance->group->id)
                ->whereHas('actorAssignments', fn ($q) => $q->where('user_id', $user->id)->where('actor_type', $requiredActorType)->where('status', 'active'))
                ->exists())
            || (($class = $instance->researchClass ?? $instance->group?->researchClass) !== null
                && $this->hasClassActorAssignment($user, $class, $requiredActorType));
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
                || ($instance->titlePresentation !== null && $this->hasActivePanelAssignment($instance->titlePresentation->defense, $user))
                || $this->institutionalActors->isDean($user)
                || ($class !== null && $this->hasAnyClassActorAssignment($user, $class))
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
                if ($instance->titlePresentation !== null && $this->hasActivePanelAssignment($instance->titlePresentation->defense, $user)) {
                    return true;
                }
                if ($instance->source_type === DefenseEvaluationRound::class && $instance->source) {
                    if ((int) $instance->source->summary_signer_user_id === (int) $user->id || $instance->source->roundPanelists()->where('panelist_user_id', $user->id)->exists()) {
                        return true;
                    }
                }
                if ($instance->source_type === DefenseSchedule::class && $instance->source) {
                    if (DefensePanelAssignment::where('defense_id', $instance->source->defense_id)->where('user_id', $user->id)->exists()) {
                        return true;
                    }
                }

                $isAssignedGroupPanelist = $this->isAssignedGroupPanelist($user, $group);

                if ($isAssignedGroupPanelist) {
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
        $class = $instance->researchClass ?? $instance->group?->researchClass;
        if ($classId !== null && $class !== null && $this->hasAnyClassActorAssignment($user, $class)) {
            return true;
        }

        return $this->hasInstanceActorAssignment($instance, $user);
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
        if ($class->relationLoaded('officialFormActorAssignments')) {
            return $class->officialFormActorAssignments->contains(fn (ResearchClassActorAssignment $assignment): bool => (int) $assignment->user_id === (int) $user->id
                && $assignment->actor_type === $actorType
                && $assignment->status === 'active'
            );
        }

        return ResearchClassActorAssignment::query()
            ->where('research_class_id', $class->id)
            ->where('user_id', $user->id)
            ->where('actor_type', $actorType)
            ->where('status', 'active')
            ->exists();
    }

    public function isCurrentGroupMember(User $user, ResearchClassGroup $group): bool
    {
        if ($group->leader_student_id !== null && (int) $group->leader_student_id === (int) $user->id) {
            return true;
        }

        if ($group->relationLoaded('members')) {
            return $group->members->contains(fn (ResearchClassGroupMember $member): bool => (int) $member->student_id === (int) $user->id
            );
        }

        $cacheKey = "{$user->id}:{$group->id}";
        if (array_key_exists($cacheKey, $this->groupMemberCache)) {
            return $this->groupMemberCache[$cacheKey];
        }

        return $this->groupMemberCache[$cacheKey] = ResearchClassGroupMember::query()
            ->where('research_class_group_id', $group->id)
            ->where('student_id', $user->id)
            ->exists();
    }

    private function hasAnyClassActorAssignment(User $user, ResearchClass $class): bool
    {
        if ($class->relationLoaded('officialFormActorAssignments')) {
            return $class->officialFormActorAssignments->contains(fn (ResearchClassActorAssignment $assignment): bool => (int) $assignment->user_id === (int) $user->id
                && $assignment->status === 'active'
            );
        }

        return ResearchClassActorAssignment::query()
            ->where('research_class_id', $class->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    private function hasInstanceActorAssignment(OfficialFormInstance $instance, User $user, ?string $actorType = null): bool
    {
        if ($instance->relationLoaded('actorAssignments')) {
            return $instance->actorAssignments->contains(fn ($assignment): bool => (int) $assignment->user_id === (int) $user->id
                && $assignment->status === 'active'
                && ($actorType === null || $assignment->actor_type === $actorType)
            );
        }

        return $instance->actorAssignments()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->when($actorType !== null, fn ($query) => $query->where('actor_type', $actorType))
            ->exists();
    }

    private function hasActivePanelAssignment(Defense $defense, User $user, ?string $position = null): bool
    {
        if ($defense->relationLoaded('activePanelAssignments')) {
            return $defense->activePanelAssignments->contains(fn (DefensePanelAssignment $assignment): bool => (int) $assignment->user_id === (int) $user->id
                && ($position === null || $assignment->panel_position === $position)
            );
        }

        return $defense->activePanelAssignments()
            ->where('user_id', $user->id)
            ->when($position !== null, fn ($query) => $query->where('panel_position', $position))
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
