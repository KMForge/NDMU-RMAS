<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('document_stage', 32)->nullable()->after('mime_type');
            $table->index(
                ['research_class_group_id', 'document_stage', 'is_current', 'submitted_at'],
                'documents_repository_stream_index',
            );
        });

        Schema::create('research_class_group_member_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_class_group_id')->constrained()->restrictOnDelete();
            $table->foreignId('research_class_id')->constrained()->restrictOnDelete();
            $table->foreignId('research_class_enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('joined_at')->nullable();
            $table->timestampTz('archived_at');
            $table->string('archive_reason', 32)->default('group_disbanded');
            $table->timestampsTz();

            $table->unique(['research_class_group_id', 'student_id'], 'group_member_history_unique');
            $table->index(['student_id', 'archived_at']);
        });

        Schema::create('document_access_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 16);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->timestampTz('accessed_at');
            $table->timestampsTz();

            $table->index(['document_id', 'accessed_at']);
            $table->index(['user_id', 'accessed_at']);
            $table->index(['action', 'accessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_access_audits');
        Schema::dropIfExists('research_class_group_member_histories');

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropIndex('documents_repository_stream_index');
            $table->dropColumn('document_stage');
        });
    }
};
