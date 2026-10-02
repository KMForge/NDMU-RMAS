<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_backups', function (Blueprint $table): void {
            $table->string('verification_status', 20)->nullable()->after('sha256');
            $table->text('verification_message')->nullable()->after('verification_status');
            $table->timestampTz('verified_at')->nullable()->after('verification_message');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            $table->index(['verification_status', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::table('system_backups', function (Blueprint $table): void {
            $table->dropForeign(['verified_by']);
            $table->dropIndex(['verification_status', 'verified_at']);
            $table->dropColumn(['verification_status', 'verification_message', 'verified_at', 'verified_by']);
        });
    }
};
