<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', 'classes.assign-advisers')
            ->where('guard_name', 'web')
            ->value('id');
        $facilitatorRoleId = DB::table('roles')
            ->where('name', 'research-facilitator')
            ->where('guard_name', 'web')
            ->value('id');
        $coordinatorRoleId = DB::table('roles')
            ->where('name', 'program-coordinator')
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId === null) {
            return;
        }

        if ($facilitatorRoleId !== null) {
            DB::table('role_has_permissions')
                ->where('permission_id', $permissionId)
                ->where('role_id', $facilitatorRoleId)
                ->delete();
        }

        if ($coordinatorRoleId !== null) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $coordinatorRoleId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', 'classes.assign-advisers')
            ->where('guard_name', 'web')
            ->value('id');
        $facilitatorRoleId = DB::table('roles')
            ->where('name', 'research-facilitator')
            ->where('guard_name', 'web')
            ->value('id');
        $coordinatorRoleId = DB::table('roles')
            ->where('name', 'program-coordinator')
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId === null) {
            return;
        }

        if ($coordinatorRoleId !== null) {
            DB::table('role_has_permissions')
                ->where('permission_id', $permissionId)
                ->where('role_id', $coordinatorRoleId)
                ->delete();
        }

        if ($facilitatorRoleId !== null) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $facilitatorRoleId,
            ]);
        }
    }
};
