<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colleges', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('college_id')->constrained()->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['college_id', 'name']);
        });

        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('degree_level', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['department_id', 'name']);
        });

        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 30)->unique();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->boolean('is_current')->default(false);
            $table->timestampsTz();
        });

        Schema::create('academic_terms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->date('starts_at');
            $table->date('ends_at');
            $table->boolean('is_current')->default(false);
            $table->timestampsTz();

            $table->unique(['academic_year_id', 'name']);
        });

        Schema::create('student_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->string('student_number', 50)->unique();
            $table->unsignedSmallInteger('year_level')->nullable();
            $table->string('contact_number', 30)->nullable();
            $table->timestampsTz();
        });

        Schema::create('faculty_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('employee_number', 50)->unique();
            $table->string('academic_rank', 100)->nullable();
            $table->text('specialization')->nullable();
            $table->string('contact_number', 30)->nullable();
            $table->timestampsTz();
        });

        Schema::create('research_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_term_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['academic_term_id', 'name']);
        });

        Schema::create('research_group_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->string('member_role', 30)->default('member');
            $table->timestampTz('joined_at')->useCurrent();
            $table->timestampTz('left_at')->nullable();
            $table->timestampsTz();

            $table->unique(['research_group_id', 'student_profile_id']);
        });

        Schema::create('research_projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_group_id')->constrained()->restrictOnDelete();
            $table->string('title', 500);
            $table->text('abstract')->nullable();
            $table->json('keywords')->default('[]');
            $table->string('category', 150)->nullable();
            $table->string('status', 50)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();

            $table->index('status');
            $table->index('research_group_id');
        });

        Schema::create('adviser_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('adviser_id')->constrained('faculty_profiles')->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('active');
            $table->text('remarks')->nullable();
            $table->timestampTz('assigned_at')->useCurrent();
            $table->timestampTz('ended_at')->nullable();
            $table->timestampsTz();

            $table->index(['research_project_id', 'status']);
            $table->index(['adviser_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adviser_assignments');
        Schema::dropIfExists('research_projects');
        Schema::dropIfExists('research_group_members');
        Schema::dropIfExists('research_groups');
        Schema::dropIfExists('faculty_profiles');
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('academic_terms');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('colleges');
    }
};
