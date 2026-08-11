<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('research_progress_updates');
        Schema::dropIfExists('research_milestones');

        Schema::create('milestone_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sequence')->unique();
            $table->decimal('weight', 8, 4)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->index(['is_active', 'sequence']);
        });

        Schema::create('research_group_milestones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_class_group_id')->constrained()->restrictOnDelete();
            $table->foreignId('milestone_definition_id')->constrained()->restrictOnDelete();
            $table->string('status', 32)->default('pending');
            $table->timestampTz('due_at')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('not_applicable_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['research_class_group_id', 'milestone_definition_id'], 'group_milestone_unique');
            $table->index(['research_class_group_id', 'status']);
            $table->index(['status', 'due_at']);
        });

        Schema::create('research_group_milestone_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_group_milestone_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 48);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->text('reason')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->boolean('override_order')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampTz('created_at')->nullable();
            $table->index(['research_group_milestone_id', 'occurred_at'], 'milestone_event_history_index');
            $table->index(['actor_id', 'occurred_at']);
        });

        Schema::create('milestone_evidences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_group_milestone_id')->constrained()->restrictOnDelete();
            $table->string('evidence_type', 40);
            $table->unsignedBigInteger('evidence_id');
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('summary', 500)->nullable();
            $table->timestampTz('linked_at');
            $table->timestampsTz();
            $table->unique(['research_group_milestone_id', 'evidence_type', 'evidence_id'], 'milestone_evidence_unique');
            $table->index(['evidence_type', 'evidence_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestone_evidences');
        Schema::dropIfExists('research_group_milestone_events');
        Schema::dropIfExists('research_group_milestones');
        Schema::dropIfExists('milestone_definitions');

        Schema::create('research_milestones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->timestampTz('due_at')->nullable();
            $table->unsignedSmallInteger('sequence');
            $table->boolean('is_required')->default(true);
            $table->timestampsTz();
            $table->unique(['academic_term_id', 'program_id', 'sequence']);
            $table->unique(['academic_term_id', 'program_id', 'name']);
        });

        Schema::create('research_progress_updates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('research_project_id')->nullable();
            $table->unsignedBigInteger('milestone_id')->nullable();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('evidence_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 32);
            $table->unsignedSmallInteger('progress_percentage')->default(0);
            $table->text('summary')->nullable();
            $table->text('feedback')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
        });
    }
};
