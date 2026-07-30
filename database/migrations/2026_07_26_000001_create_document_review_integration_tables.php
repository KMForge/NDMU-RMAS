<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->json('data');
                $table->timestampTz('read_at')->nullable();
                $table->timestampsTz();
            });
        }

        if (! Schema::hasTable('document_review_audits')) {
            Schema::create('document_review_audits', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('document_id')->constrained()->cascadeOnDelete();
                $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->string('action', 40);
                $table->string('decision', 32)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestampTz('occurred_at');
                $table->json('metadata')->nullable();
                $table->timestampsTz();

                $table->index(['document_id', 'occurred_at']);
                $table->index(['reviewer_id', 'occurred_at']);
                $table->index(['student_id', 'occurred_at']);
                $table->index(['action', 'decision']);
            });
        }

        if (! Schema::hasTable('revision_requests')) {
            Schema::create('revision_requests', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('research_project_id')->nullable();
                $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
                $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->text('instructions');
                $table->string('status', 32)->default('open');
                $table->timestampTz('due_at')->nullable();
                $table->timestampTz('resolved_at')->nullable();
                $table->timestampsTz();

                $table->index(['research_project_id', 'status']);
                $table->index(['assigned_to', 'status', 'created_at']);
                $table->index(['document_id', 'status']);
            });
        }

        if (! Schema::hasTable('research_proposals')) {
            Schema::create('research_proposals', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('research_project_id')->nullable();
                $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('version')->default(1);
                $table->string('title');
                $table->string('status', 32)->default('pending');
                $table->text('review_notes')->nullable();
                $table->timestampTz('submitted_at')->nullable();
                $table->timestampTz('reviewed_at')->nullable();
                $table->timestampsTz();

                $table->unique('document_id');
                $table->index(['research_project_id', 'version']);
                $table->index(['submitted_by', 'status']);
                $table->index(['status', 'reviewed_at']);
            });
        }

        if (! Schema::hasTable('research_progress_updates')) {
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

                $table->index(['research_project_id', 'milestone_id', 'version']);
                $table->index(['submitted_by', 'status']);
                $table->index(['evidence_document_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('research_progress_updates');
        Schema::dropIfExists('research_proposals');
        Schema::dropIfExists('revision_requests');
        Schema::dropIfExists('document_review_audits');
        Schema::dropIfExists('notifications');
    }
};
