<?php

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('user_type', 20)->default(UserType::Faculty->value)->index();
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false)->index();
            $table->boolean('is_assignable')->default(true)->index();
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->string('module')->nullable()->index();
            $table->string('scope')->nullable();
        });

        DB::table('users')->update(['user_type' => UserType::Faculty->value]);

        DB::table('users')
            ->whereNotNull('student_id')
            ->update(['user_type' => UserType::Student->value]);

        $this->setTypeForLegacyRole('student-researcher', UserType::Student);
        $this->setTypeForLegacyRole('system-administrator', UserType::Admin);
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropIndex(['module']);
            $table->dropColumn(['display_name', 'description', 'module', 'scope']);
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropIndex(['is_system']);
            $table->dropIndex(['is_assignable']);
            $table->dropColumn(['display_name', 'description', 'is_system', 'is_assignable']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['user_type']);
            $table->dropColumn('user_type');
        });
    }

    private function setTypeForLegacyRole(string $roleName, UserType $type): void
    {
        $roleId = DB::table('roles')
            ->where('name', $roleName)
            ->where('guard_name', 'web')
            ->value('id');

        if ($roleId === null) {
            return;
        }

        $userIds = DB::table('model_has_roles')
            ->where('role_id', $roleId)
            ->where('model_type', User::class)
            ->pluck('model_id');

        DB::table('users')->whereIn('id', $userIds)->update(['user_type' => $type->value]);
    }
};
