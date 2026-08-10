<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seedPermissionCatalog();
        $this->seedDefaultRoles();
        $this->migrateLegacyAssignments();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedPermissionCatalog(): void
    {
        $timestamp = now();
        $rows = [];

        foreach (config('access-control.permissions', []) as $module => $permissions) {
            foreach ($permissions as $name => $metadata) {
                $rows[] = [
                    'name' => $name,
                    'guard_name' => 'web',
                    'display_name' => $metadata['label'] ?? Str::headline($name),
                    'description' => $metadata['description'] ?? null,
                    'module' => $module,
                    'scope' => $metadata['scope'] ?? null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        Permission::query()->upsert(
            $rows,
            ['name', 'guard_name'],
            ['display_name', 'description', 'module', 'scope', 'updated_at'],
        );
    }

    private function seedDefaultRoles(): void
    {
        foreach (config('access-control.roles', []) as $name => $definition) {
            $role = Role::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [
                    'display_name' => $definition['label'],
                    'description' => $definition['description'],
                    'is_system' => true,
                    'is_assignable' => true,
                ],
            );

            $role->forceFill([
                'display_name' => $definition['label'],
                'description' => $definition['description'],
                'is_system' => true,
                'is_assignable' => true,
            ])->save();

            // Keep system roles usable after reseeding without removing any
            // custom permissions deliberately added by an administrator.
            $role->givePermissionTo($definition['permissions']);
        }
    }

    private function migrateLegacyAssignments(): void
    {
        foreach (config('access-control.legacy_role_aliases', []) as $legacy => $canonical) {
            $canonicalRole = Role::query()->where('name', $canonical)->where('guard_name', 'web')->firstOrFail();
            $legacyRole = Role::query()->firstOrCreate(['name' => $legacy, 'guard_name' => 'web']);

            $legacyRole->forceFill([
                'display_name' => ($legacyRole->display_name ?: Str::headline($legacy)).' (Legacy)',
                'description' => "Compatibility alias for {$canonicalRole->display_name}.",
                'is_system' => true,
                'is_assignable' => false,
            ])->save();

            // Add the current canonical capabilities so old sessions continue
            // to work while user assignments are migrated safely.
            $legacyRole->givePermissionTo($canonicalRole->permissions);

            User::role($legacy)->with('roles')->each(function (User $user) use ($canonical, $legacy): void {
                if (! $user->hasRole($canonical)) {
                    $user->assignRole($canonical);
                }

                $user->removeRole($legacy);
            });
        }

        User::query()->where('user_type', UserType::Faculty->value)->each(function (User $user): void {
            if (! $user->hasRole('faculty')) {
                $user->assignRole('faculty');
            }
        });
    }
}
