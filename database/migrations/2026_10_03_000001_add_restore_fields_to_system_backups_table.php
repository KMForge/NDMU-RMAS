<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_backups', function (Blueprint $table): void {
            $table->json('manifest')->nullable()->after('sha256');
            $table->string('restore_status', 20)->nullable()->index()->after('verification_message');
            $table->text('restore_message')->nullable()->after('restore_status');
            $table->timestampTz('restored_at')->nullable()->after('restore_message');
            $table->foreignId('restored_by')->nullable()->after('restored_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('system_backups', function (Blueprint $table): void {
            $table->dropForeign(['restored_by']);
            $table->dropIndex(['restore_status']);
            $table->dropColumn(['manifest', 'restore_status', 'restore_message', 'restored_at', 'restored_by']);
        });
    }
};
