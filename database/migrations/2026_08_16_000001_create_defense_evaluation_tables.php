<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defense_evaluation_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defense_id')->constrained('defenses')->onDelete('restrict');
            $table->foreignId('defense_schedule_id')->constrained('defense_schedules')->onDelete('restrict');
            $table->foreignId('research_class_group_id')->constrained('research_class_groups')->onDelete('restrict');
            $table->string('status')->default('open');
            $table->foreignId('summary_signer_user_id')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('opened_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('opened_at');
            $table->timestamp('all_submitted_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index(['defense_id', 'status']);
            $table->index(['defense_schedule_id']);
        });

        Schema::create('defense_evaluation_round_panelists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defense_evaluation_round_id')->constrained('defense_evaluation_rounds')->onDelete('cascade');
            $table->foreignId('defense_panel_assignment_id')->constrained('defense_panel_assignments')->onDelete('restrict');
            $table->foreignId('panelist_user_id')->constrained('users')->onDelete('restrict');
            $table->unsignedTinyInteger('position');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['defense_evaluation_round_id', 'panelist_user_id'], 'derp_round_panelist_unique');
        });

        Schema::create('defense_evaluation_round_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defense_evaluation_round_id')->constrained('defense_evaluation_rounds')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('restrict');
            $table->string('student_name_snapshot');
            $table->foreignId('group_member_id')->nullable()->constrained('research_class_group_members')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['defense_evaluation_round_id', 'student_id'], 'ders_round_student_unique');
        });

        Schema::create('defense_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defense_evaluation_round_id')->constrained('defense_evaluation_rounds')->onDelete('restrict');
            $table->foreignId('round_panelist_id')->constrained('defense_evaluation_round_panelists')->onDelete('restrict');
            $table->foreignId('panelist_user_id')->constrained('users')->onDelete('restrict');
            $table->string('status')->default('draft');
            $table->decimal('research_quality_score', 5, 2)->nullable();
            $table->decimal('originality_score', 5, 2)->nullable();
            $table->decimal('relevance_score', 5, 2)->nullable();
            $table->decimal('research_paper_total', 5, 2)->nullable();
            $table->text('general_comments')->nullable();
            $table->text('recommendations')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['defense_evaluation_round_id', 'panelist_user_id'], 'de_round_panelist_unique');
        });

        Schema::create('defense_evaluation_student_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defense_evaluation_id')->constrained('defense_evaluations')->onDelete('cascade');
            $table->foreignId('round_student_id')->constrained('defense_evaluation_round_students')->onDelete('restrict');
            $table->foreignId('student_id')->constrained('users')->onDelete('restrict');
            $table->decimal('communication_score', 5, 2)->nullable();
            $table->decimal('organization_score', 5, 2)->nullable();
            $table->decimal('effectiveness_score', 5, 2)->nullable();
            $table->decimal('presentation_total', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['defense_evaluation_id', 'student_id'], 'dess_eval_student_unique');
        });

        Schema::create('defense_evaluation_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defense_evaluation_round_id')->constrained('defense_evaluation_rounds')->onDelete('restrict');
            $table->decimal('research_paper_average', 5, 2);
            $table->string('status')->default('calculated');
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->unique(['defense_evaluation_round_id'], 'des_round_unique');
        });

        Schema::create('defense_evaluation_student_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defense_evaluation_summary_id')->constrained('defense_evaluation_summaries')->onDelete('cascade');
            $table->foreignId('round_student_id')->constrained('defense_evaluation_round_students')->onDelete('restrict');
            $table->foreignId('student_id')->constrained('users')->onDelete('restrict');
            $table->decimal('presentation_average', 5, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['defense_evaluation_summary_id', 'student_id'], 'dessum_summary_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defense_evaluation_student_summaries');
        Schema::dropIfExists('defense_evaluation_summaries');
        Schema::dropIfExists('defense_evaluation_student_scores');
        Schema::dropIfExists('defense_evaluations');
        Schema::dropIfExists('defense_evaluation_round_students');
        Schema::dropIfExists('defense_evaluation_round_panelists');
        Schema::dropIfExists('defense_evaluation_rounds');
    }
};
