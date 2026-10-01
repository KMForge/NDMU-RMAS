<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_backup_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('frequency', 16)->default('daily');
            $table->time('run_time')->default('23:00');
            $table->unsignedSmallInteger('retention_count')->default(14);
            $table->timestampTz('last_scheduled_for')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('system_backups', function (Blueprint $table): void {
            $table->id();
            $table->string('filename');
            $table->string('storage_disk', 40)->default('local');
            $table->string('storage_path', 1000)->nullable();
            $table->string('status', 20)->default('running')->index();
            $table->string('trigger', 20)->default('manual')->index();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('sha256', 64)->nullable();
            $table->text('failure_message')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('started_at');
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
        });

        DB::table('system_backup_settings')->insert([
            'enabled' => false,
            'frequency' => 'daily',
            'run_time' => '23:00',
            'retention_count' => 14,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_backups');
        Schema::dropIfExists('system_backup_settings');
    }
};
