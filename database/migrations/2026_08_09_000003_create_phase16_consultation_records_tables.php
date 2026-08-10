<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Re-create or alter consultation_requests to include modern fields while preserving legacy columns
        if (Schema::hasTable('consultation_requests')) {
            Schema::table('consultation_requests', function (Blueprint $table): void {
                $table->foreignId('research_project_id')->nullable()->change();
                $table->foreignId('adviser_assignment_id')->nullable()->change();

                if (! Schema::hasColumn('consultation_requests', 'research_class_group_id')) {
                    $table->foreignId('research_class_group_id')->nullable()->after('id')->constrained('research_class_groups')->nullOnDelete();
                }
                if (! Schema::hasColumn('consultation_requests', 'assigned_adviser_id')) {
                    $table->foreignId('assigned_adviser_id')->nullable()->after('research_class_group_id')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('consultation_requests', 'confirmed_start_at')) {
                    $table->timestampTz('confirmed_start_at')->nullable()->after('preferred_at');
                }
                if (! Schema::hasColumn('consultation_requests', 'confirmed_end_at')) {
                    $table->timestampTz('confirmed_end_at')->nullable()->after('confirmed_start_at');
                }
                if (! Schema::hasColumn('consultation_requests', 'duration_minutes')) {
                    $table->unsignedInteger('duration_minutes')->default(60)->after('confirmed_end_at');
                }
                if (! Schema::hasColumn('consultation_requests', 'location')) {
                    $table->text('location')->nullable()->after('consultation_mode');
                }
                if (! Schema::hasColumn('consultation_requests', 'meeting_url')) {
                    $table->text('meeting_url')->nullable()->after('location');
                }
                if (! Schema::hasColumn('consultation_requests', 'document_stage')) {
                    $table->string('document_stage', 40)->nullable()->after('meeting_url');
                }
                if (! Schema::hasColumn('consultation_requests', 'document_id')) {
                    $table->foreignId('document_id')->nullable()->after('document_stage')->constrained('documents')->nullOnDelete();
                }
                if (! Schema::hasColumn('consultation_requests', 'cancelled_by')) {
                    $table->foreignId('cancelled_by')->nullable()->after('review_notes')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('consultation_requests', 'cancelled_at')) {
                    $table->timestampTz('cancelled_at')->nullable()->after('cancelled_by');
                }
                if (! Schema::hasColumn('consultation_requests', 'cancellation_reason')) {
                    $table->text('cancellation_reason')->nullable()->after('cancelled_at');
                }
            });
        } else {
            Schema::create('consultation_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('research_class_group_id')->nullable()->constrained('research_class_groups')->nullOnDelete();
                $table->foreignId('assigned_adviser_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('research_project_id')->nullable()->constrained('research_projects')->nullOnDelete();
                $table->foreignId('adviser_assignment_id')->nullable()->constrained('adviser_assignments')->nullOnDelete();
                $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
                $table->uuid('request_token');
                $table->timestampTz('preferred_at');
                $table->timestampTz('confirmed_start_at')->nullable();
                $table->timestampTz('confirmed_end_at')->nullable();
                $table->unsignedInteger('duration_minutes')->default(60);
                $table->string('consultation_mode', 20);
                $table->text('location')->nullable();
                $table->text('meeting_url')->nullable();
                $table->string('document_stage', 40)->nullable();
                $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
                $table->text('agenda');
                $table->string('status', 20)->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestampTz('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestampTz('cancelled_at')->nullable();
                $table->text('cancellation_reason')->nullable();
                $table->timestampsTz();

                $table->unique(['requested_by', 'request_token']);
                $table->index(['assigned_adviser_id', 'status', 'confirmed_start_at', 'confirmed_end_at']);
                $table->index(['research_class_group_id', 'status']);
            });
        }

        // Table: consultation_schedule_proposals
        Schema::create('consultation_schedule_proposals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consultation_request_id')->constrained('consultation_requests')->cascadeOnDelete();
            $table->foreignId('proposed_by')->constrained('users')->cascadeOnDelete();
            $table->timestampTz('proposed_start_at');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending_response');
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('responded_at')->nullable();
            $table->timestampsTz();

            $table->index(['consultation_request_id', 'status']);
        });

        // Table: consultation_records
        Schema::create('consultation_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consultation_request_id')->constrained('consultation_requests')->cascadeOnDelete();
            $table->foreignId('research_class_group_id')->constrained('research_class_groups')->cascadeOnDelete();
            $table->foreignId('conducted_by')->constrained('users')->cascadeOnDelete();
            $table->timestampTz('consulted_at');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->string('consultation_mode', 20);
            $table->text('location')->nullable();
            $table->text('meeting_url')->nullable();
            $table->text('agenda');
            $table->text('discussion');
            $table->text('recommendations')->nullable();
            $table->timestampTz('next_consultation_at')->nullable();
            $table->foreignId('supersedes_record_id')->nullable()->constrained('consultation_records')->nullOnDelete();
            $table->boolean('is_superseded')->default(false);
            $table->text('correction_reason')->nullable();
            $table->timestampsTz();

            $table->index(['research_class_group_id']);
            $table->index(['consultation_request_id']);
            $table->index(['conducted_by']);
        });

        // Table: consultation_attendances
        Schema::create('consultation_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consultation_record_id')->constrained('consultation_records')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('attended')->default(true);
            $table->timestampsTz();

            $table->unique(['consultation_record_id', 'student_id']);
        });

        // Table: consultation_audits
        Schema::create('consultation_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consultation_request_id')->constrained('consultation_requests')->cascadeOnDelete();
            $table->foreignId('research_class_group_id')->constrained('research_class_groups')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 50);
            $table->string('status', 20);
            $table->string('ip_address', 45)->nullable();
            $table->timestampTz('occurred_at');
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->index(['research_class_group_id', 'action']);
            $table->index(['consultation_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_audits');
        Schema::dropIfExists('consultation_attendances');
        Schema::dropIfExists('consultation_records');
        Schema::dropIfExists('consultation_schedule_proposals');
        // Do not drop consultation_requests if legacy table existed prior
    }
};
