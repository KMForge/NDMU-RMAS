<?php

namespace App\Policies;

use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;

class OfficialFormInstancePolicy
{
    /** @var array<string, list<string>> */
    private const FORM_ACTION_PERMISSIONS = [
        'res-026' => ['fill' => ['forms.res-026.fill', 'forms.res-026.submit'], 'approve' => ['forms.res-026.approve']],
        'res-027' => ['fill' => ['forms.res-027.respond'], 'approve' => ['forms.res-027.respond']],
        'res-028' => ['fill' => ['forms.res-028.respond'], 'approve' => ['forms.res-028.respond']],
        'res-029' => ['fill' => ['forms.res-029.respond'], 'approve' => ['forms.res-029.respond']],
        'res-030' => ['fill' => ['forms.res-030.submit'], 'approve' => ['forms.res-030.approve']],
        'res-031' => ['fill' => ['forms.res-031.fill'], 'approve' => ['forms.res-031.sign']],
        'res-032' => ['fill' => ['forms.res-032.fill'], 'approve' => ['forms.res-032.sign']],
        'res-033' => ['fill' => ['forms.res-033.endorse'], 'approve' => ['forms.res-033.endorse', 'forms.res-033.approve']],
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
                || $user->can('users.manage');
        }

        if ($definition->ownership_scope === 'research_class' && $class !== null) {
            return $class->facilitator_id === $user->id
                || $user->can('users.manage');
        }

        return true;
    }

    public function updateDraft(User $user, OfficialFormInstance $instance): bool
    {
        if (! in_array($instance->status, ['draft', 'returned_for_correction'], true)) {
            return false;
        }

        $code = strtolower($instance->definition->code);
        $allowedFillPermissions = self::FORM_ACTION_PERMISSIONS[$code]['fill'] ?? ["forms.{$code}.fill", "forms.{$code}.submit"];
        $hasEditPermission = false;
        foreach ($allowedFillPermissions as $perm) {
            if ($user->hasPermissionTo($perm)) {
                $hasEditPermission = true;
                break;
            }
        }

        if (! $hasEditPermission) {
            return false;
        }

        if ($instance->initiated_by === $user->id) {
            return true;
        }

        return $this->checkContextualAccess($user, $instance);
    }

    public function submit(User $user, OfficialFormInstance $instance): bool
    {
        $code = strtolower($instance->definition->code);
        $allowedFillPermissions = self::FORM_ACTION_PERMISSIONS[$code]['fill'] ?? ["forms.{$code}.submit", "forms.{$code}.fill"];
        $hasSubmitPermission = false;
        foreach ($allowedFillPermissions as $perm) {
            if ($user->hasPermissionTo($perm)) {
                $hasSubmitPermission = true;
                break;
            }
        }

        if (! $hasSubmitPermission) {
            return false;
        }

        return $this->checkContextualAccess($user, $instance);
    }

    public function endorse(User $user, OfficialFormInstance $instance): bool
    {
        return $this->evaluateActionPermission($user, $instance, 'approve');
    }

    public function certify(User $user, OfficialFormInstance $instance): bool
    {
        return $this->evaluateActionPermission($user, $instance, 'approve');
    }

    public function approve(User $user, OfficialFormInstance $instance): bool
    {
        return $this->evaluateActionPermission($user, $instance, 'approve');
    }

    private function evaluateActionPermission(User $user, OfficialFormInstance $instance, string $actionCategory): bool
    {
        $code = strtolower($instance->definition->code);
        $allowedPermissions = self::FORM_ACTION_PERMISSIONS[$code][$actionCategory] ?? ["forms.{$code}.{$actionCategory}"];

        $hasPerm = false;
        foreach ($allowedPermissions as $perm) {
            if ($user->hasPermissionTo($perm)) {
                $hasPerm = true;
                break;
            }
        }

        if (! $hasPerm && ! $user->can('users.manage')) {
            return false;
        }

        return $this->checkContextualAccess($user, $instance);
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
