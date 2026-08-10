<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_class_groups', function (Blueprint $table): void {
            $table->foreignId('leader_student_id')
                ->nullable()
                ->after('research_class_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->foreignId('research_class_group_id')
                ->nullable()
                ->after('user_id')
                ->constrained('research_class_groups')
                ->cascadeOnDelete();
            $table->unsignedInteger('version_number')->default(1)->after('mime_type');
            $table->boolean('is_current')->default(true)->after('version_number');

            $table->index(['research_class_group_id', 'is_current']);
        });

        Schema::table('document_upload_audits', function (Blueprint $table): void {
            $table->foreignId('research_class_group_id')
                ->nullable()
                ->after('user_id')
                ->constrained('research_class_groups')
                ->nullOnDelete();

            $table->index(['research_class_group_id', 'attempted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('document_upload_audits', function (Blueprint $table): void {
            $table->dropForeign(['research_class_group_id']);
            $table->dropColumn('research_class_group_id');
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropIndex(['research_class_group_id', 'is_current']);
            $table->dropForeign(['research_class_group_id']);
            $table->dropColumn(['research_class_group_id', 'version_number', 'is_current']);
        });

        Schema::table('research_class_groups', function (Blueprint $table): void {
            $table->dropForeign(['leader_student_id']);
            $table->dropColumn('leader_student_id');
        });
    }
};
