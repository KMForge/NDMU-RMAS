<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $matrix = [
            'student-researcher' => ['research.view-own', 'research.create', 'research.update-own', 'proposal.submit', 'documents.upload', 'documents.download', 'revisions.resolve', 'defenses.view', 'evaluations.view-own'],
            'research-adviser' => ['research.view-assigned', 'proposal.review', 'documents.review', 'documents.download', 'revisions.create', 'revisions.resolve', 'defenses.view', 'evaluations.view-assigned'],
            'panelist' => ['research.view-assigned', 'documents.download', 'defenses.view', 'evaluations.create', 'evaluations.view-own', 'evaluations.view-assigned'],
            'research-facilitator' => ['research.view-all', 'proposal.review', 'proposal.approve', 'documents.review', 'documents.download', 'revisions.create', 'defenses.view', 'defenses.manage', 'evaluations.view-assigned', 'reports.view', 'reports.export', 'notifications.broadcast'],
            'college-dean' => ['research.view-college', 'research.approve', 'proposal.approve', 'documents.download', 'defenses.view', 'evaluations.view-assigned', 'reports.view', 'reports.export'],
            'system-administrator' => ['research.view-all', 'documents.download', 'defenses.view', 'reports.view', 'reports.export', 'users.manage', 'audit-logs.view', 'settings.manage', 'notifications.broadcast'],
        ];

        collect($matrix)->flatten()->unique()->each(
            fn (string $name) => Permission::findOrCreate($name, 'web'),
        );

        foreach ($matrix as $roleName => $permissions) {
            Role::findOrCreate($roleName, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
