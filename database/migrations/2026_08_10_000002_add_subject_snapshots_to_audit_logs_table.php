<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('subject_name')->nullable()->after('actor_email');
            $table->string('subject_email')->nullable()->after('subject_name');
            $table->index('subject_email');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex(['subject_email']);
            $table->dropColumn(['subject_name', 'subject_email']);
        });
    }
};
