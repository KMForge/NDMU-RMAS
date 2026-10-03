<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_backup_settings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('max_import_mb')->default(10)->after('retention_count');
        });
    }

    public function down(): void
    {
        Schema::table('system_backup_settings', function (Blueprint $table): void {
            $table->dropColumn('max_import_mb');
        });
    }
};
