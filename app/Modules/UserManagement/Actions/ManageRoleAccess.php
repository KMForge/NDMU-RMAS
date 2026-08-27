<?php

namespace App\Modules\UserManagement\Actions;

use App\Enums\UserType;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ManageRoleAccess
{
    public function __construct(private readonly AuditLogWriter $auditLogs) {}

    /**
     * @param  list<string>  $permissions
     */
    public function saveCustomRole(User $actor, ?Role $role, string $name, array $permissions): Role
    {
        $this->authorizeManager($actor);
        $this->ensureCatalogPermissions($permissions);

        if ($role !== null && $this->isProtected($role->name) && $name !== $role->name) {
            throw ValidationException::withMessages([
                'roleName' => 'The protected administrator role cannot be renamed.',
            ]);
        }

        if ($role?->name === 'administrator') {
            $required = ['dashboards.admin.view', 'users.manage', 'roles.manage', 'permissions.manage'];

            if (collect($required)->diff($permissions)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'selectedPermissions' => 'Administrator must retain dashboard, user, role, and permission management access.',
                ]);
            }
        }

        return DB::transaction(function () use ($actor, $role, $name, $permissions): Role {
            $oldValues = $role === null ? null : [
                'name' => $role->name,
                'permissions' => $role->permissions()->pluck('name')->sort()->values()->all(),
            ];

            $role ??= new Role(['guard_name' => 'web']);
            $role->name = $name;
            $role->guard_name = 'web';
            $role->display_name = $role->display_name ?: str($name)->headline()->toString();
            $role->is_assignable = true;
            $role->save();
            $role->syncPermissions($permissions);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $this->audit($actor, $role, $oldValues === null ? 'role.created' : 'role.updated', $oldValues, [
                'name' => $role->name,
                'permissions' => collect($permissions)->sort()->values()->all(),
            ]);

            return $role->refresh();
        });
    }

    public function deleteCustomRole(User $actor, Role $role): void
    {
        $this->authorizeManager($actor);

        if ($this->isProtected($role->name)) {
            throw ValidationException::withMessages([
                'roleName' => 'Built-in portal roles cannot be deleted.',
            ]);
        }

        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'roleName' => 'Remove this role from every user before deleting it.',
            ]);
        }

        DB::transaction(function () use ($actor, $role): void {
            $oldValues = [
                'name' => $role->name,
                'permissions' => $role->permissions()->pluck('name')->sort()->values()->all(),
            ];
            $this->audit($actor, $role, 'role.deleted', $oldValues, null);
            $role->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    /**
     * @param  list<string>  $roles
     */
    public function syncUserRoles(User $actor, User $subject, array $roles, UserType $userType): User
    {
        $this->authorizeManager($actor);

        if ($actor->is($subject)) {
            throw ValidationException::withMessages([
                'assignedRoles' => 'You cannot modify your own roles.',
            ]);
        }

        $validRoles = Role::query()
            ->where('guard_name', 'web')
            ->where('is_assignable', true)
            ->whereIn('name', $roles)
            ->pluck('name')
            ->all();

        if (count($validRoles) !== count(array_unique($roles))) {
            throw ValidationException::withMessages([
                'assignedRoles' => 'One or more selected roles are invalid.',
            ]);
        }

        if ($validRoles === [] && $userType !== UserType::Faculty) {
            throw ValidationException::withMessages([
                'assignedRoles' => 'Student and administrator accounts must retain their required access role.',
            ]);
        }

        if ($userType === UserType::Faculty && collect($validRoles)->intersect(['administrator', 'student', 'faculty'])->isNotEmpty()) {
            throw ValidationException::withMessages([
                'assignedRoles' => 'Faculty accounts may only receive operational responsibility roles.',
            ]);
        }

        if ($userType === UserType::Student && collect($validRoles)->intersect(['administrator', 'faculty'])->isNotEmpty()) {
            throw ValidationException::withMessages([
                'assignedRoles' => 'Student accounts cannot receive administrator or faculty identity roles.',
            ]);
        }

        if ($userType === UserType::Admin && collect($validRoles)->intersect(['student', 'faculty'])->isNotEmpty()) {
            throw ValidationException::withMessages([
                'assignedRoles' => 'Administrator accounts cannot receive student or faculty identity roles.',
            ]);
        }

        if ($userType === UserType::Student && ! in_array('student', $validRoles, true)) {
            throw ValidationException::withMessages([
                'assignedRoles' => 'Student accounts must retain the Student role.',
            ]);
        }

        if ($userType === UserType::Admin && ! collect($validRoles)->contains(
            fn (string $role): bool => Role::findByName($role)->hasPermissionTo('roles.manage')
        )) {
            throw ValidationException::withMessages([
                'assignedRoles' => 'Administrator accounts must retain role management access.',
            ]);
        }

        if (
            $subject->can('roles.manage')
            && ! Role::query()->whereIn('name', $validRoles)
                ->whereHas('permissions', fn ($query) => $query->where('name', 'roles.manage'))
                ->exists()
            && User::permission('roles.manage')->count() <= 1
        ) {
            throw ValidationException::withMessages([
                'assignedRoles' => 'The last access-control administrator cannot lose role management access.',
            ]);
        }

        return DB::transaction(function () use ($actor, $subject, $validRoles, $userType): User {
            $oldRoles = $subject->roles()->pluck('name')->sort()->values()->all();
            $oldUserType = $subject->user_type->value;
            $subject->forceFill(['user_type' => $userType])->save();
            $subject->syncRoles($validRoles);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $this->audit($actor, $subject, 'user.access-updated', [
                'user_type' => $oldUserType,
                'roles' => $oldRoles,
            ], [
                'user_type' => $userType->value,
                'roles' => collect($validRoles)->sort()->values()->all(),
            ]);

            return $subject->refresh();
        });
    }

    /** @param list<string> $permissions */
    private function ensureCatalogPermissions(array $permissions): void
    {
        $catalog = collect(config('access-control.permissions', []))
            ->flatMap(fn (array $group): array => array_keys($group));
        $unknown = collect($permissions)->diff($catalog);

        if ($unknown->isNotEmpty()) {
            throw ValidationException::withMessages([
                'selectedPermissions' => 'The selected permission catalog is invalid.',
            ]);
        }
    }

    private function authorizeManager(User $actor): void
    {
        abort_unless($actor->can('roles.manage') && $actor->can('permissions.manage'), 403);
    }

    private function isProtected(string $role): bool
    {
        return in_array($role, config('access-control.protected_roles', []), true);
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function audit(User $actor, Role|User $subject, string $event, ?array $oldValues, ?array $newValues): void
    {
        $isUser = $subject instanceof User;
        $subjectName = match (true) {
            $subject instanceof User => $subject->name,
            $subject instanceof Role => $subject->display_name ?: str($subject->name)->headline()->toString(),
        };

        $this->auditLogs->write(
            actor: $actor,
            event: $event,
            description: $isUser ? 'User role assignments were updated.' : 'Role access configuration was updated.',
            requestContext: AuditRequestContext::fromRequest(request()),
            auditable: $subject,
            subjectName: $subjectName,
            subjectEmail: $isUser ? $subject->email : null,
            oldValues: $oldValues,
            newValues: $newValues,
            actorContext: 'administrator',
        );
    }
}
