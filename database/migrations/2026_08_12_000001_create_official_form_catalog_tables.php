<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_form_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('default_category', 64);
            $table->string('ownership_scope', 32)->default('research_group');
            $table->string('cardinality', 32)->default('single_per_group');
            $table->string('template_view', 150);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('official_form_instances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('official_form_definition_id')->constrained('official_form_definitions')->restrictOnDelete();
            $table->foreignId('research_class_group_id')->nullable()->constrained('research_class_groups')->nullOnDelete();
            $table->foreignId('research_class_id')->nullable()->constrained('research_classes')->nullOnDelete();
            $table->string('context_key', 64)->default('general');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('initiated_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 32)->default('draft');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamps();

            $table->index(['research_class_group_id', 'official_form_definition_id', 'status'], 'idx_form_instance_group_lookup');
            $table->index(['research_class_id', 'official_form_definition_id', 'status'], 'idx_form_instance_class_lookup');
            $table->index(['source_type', 'source_id'], 'idx_form_instance_source');
        });

        Schema::create('official_form_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('official_form_instance_id')->constrained('official_form_instances')->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('payload');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('supersedes_version_id')->nullable()->constrained('official_form_versions')->nullOnDelete();
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->unique(['official_form_instance_id', 'version_number']);
            $table->index(['official_form_instance_id', 'is_current']);
        });

        Schema::table('official_form_instances', function (Blueprint $table): void {
            $table->foreign('current_version_id')->references('id')->on('official_form_versions')->nullOnDelete();
        });

        Schema::create('official_form_actor_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('official_form_instance_id')->constrained('official_form_instances')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('actor_type', 64);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->string('status', 32)->default('active');
            $table->timestamps();

            $table->unique(['official_form_instance_id', 'actor_type', 'user_id'], 'form_actor_assignment_unique');
            $table->index(['user_id', 'actor_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('official_form_instances', function (Blueprint $table): void {
            $table->dropForeign(['current_version_id']);
        });

        Schema::dropIfExists('official_form_actor_assignments');
        Schema::dropIfExists('official_form_versions');
        Schema::dropIfExists('official_form_instances');
        Schema::dropIfExists('official_form_definitions');
    }
};
