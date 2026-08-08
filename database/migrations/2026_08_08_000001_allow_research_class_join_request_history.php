<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_class_enrollments', function (Blueprint $table): void {
            $table->dropUnique(['research_class_id', 'student_id']);

            $table->index(
                ['student_id', 'research_class_id', 'status', 'reviewed_at'],
                'class_enrollments_student_class_status_reviewed_index',
            );
            $table->index(
                ['student_id', 'status', 'requested_at'],
                'class_enrollments_student_status_requested_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('research_class_enrollments', function (Blueprint $table): void {
            $table->dropIndex('class_enrollments_student_class_status_reviewed_index');
            $table->dropIndex('class_enrollments_student_status_requested_index');

            $table->unique(['research_class_id', 'student_id']);
        });
    }
};
