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
            'student-researcher' => ['research.view-own', 'research.create', 'research.update-own', 'proposal.submit', 'documents.upload', 'documents.download', 'consultations.request', 'classes.join', 'classes.view-enrolled', 'revisions.resolve', 'defenses.view', 'evaluations.view-own'],
            'research-adviser' => ['research.view-assigned', 'proposal.review', 'documents.review', 'documents.download', 'consultations.manage-assigned', 'classes.create', 'classes.view-own', 'revisions.create', 'revisions.resolve', 'defenses.view', 'evaluations.view-assigned'],
            'panelist' => ['research.view-assigned', 'documents.download', 'defenses.view', 'evaluations.create', 'evaluations.view-own', 'evaluations.view-assigned'],
            'research-facilitator' => ['research.view-all', 'proposal.review', 'proposal.approve', 'documents.review', 'documents.download', 'revisions.create', 'defenses.view', 'defenses.manage', 'evaluations.view-assigned', 'reports.view', 'reports.export', 'notifications.broadcast'],
            'college-dean' => ['research.view-college', 'research.approve', 'proposal.approve', 'documents.download', 'defenses.view', 'evaluations.view-assigned', 'reports.view', 'reports.export'],
            'system-administrator' => ['research.view-all', 'documents.download', 'documents.download-any', 'defenses.view', 'reports.view', 'reports.export', 'users.manage', 'audit-logs.view', 'settings.manage', 'notifications.broadcast'],
        ];

        $timestamp = now();
        $permissionNames = collect($matrix)->flatten()->unique()->values();

        Permission::query()->upsert(
            $permissionNames->map(fn (string $name) => [
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])->all(),
            ['name', 'guard_name'],
            ['updated_at'],
        );

        foreach ($matrix as $roleName => $permissions) {
            Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ])->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
