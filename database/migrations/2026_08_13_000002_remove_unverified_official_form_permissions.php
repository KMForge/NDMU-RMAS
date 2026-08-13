<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $unverified = DB::table('permissions')->where('name', 'forms.res-026.approve')->value('id');
        if ($unverified !== null) {
            DB::table('role_has_permissions')->where('permission_id', $unverified)->delete();
            DB::table('model_has_permissions')->where('permission_id', $unverified)->delete();
            DB::table('permissions')->where('id', $unverified)->delete();
        }

        $endorse = DB::table('permissions')->where('name', 'forms.res-047.endorse')->value('id');
        if ($endorse !== null) {
            $roleIds = DB::table('roles')
                ->whereIn('name', ['research-facilitator', 'department-chair', 'dean'])
                ->pluck('id');

            DB::table('role_has_permissions')
                ->where('permission_id', $endorse)
                ->whereIn('role_id', $roleIds)
                ->delete();
        }
    }

    public function down(): void
    {
        // These permissions represented unverified institutional authority.
        // Reintroducing them requires a new evidence-backed migration.
    }
};
