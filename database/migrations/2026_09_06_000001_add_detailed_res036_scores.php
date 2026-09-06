<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('defense_evaluation_rounds', function (Blueprint $table): void {
            $table->string('program_code', 16)->nullable()->after('research_class_group_id');
        });

        Schema::table('defense_evaluations', function (Blueprint $table): void {
            $table->json('paper_criterion_scores')->nullable()->after('relevance_score');
            $table->string('rubric_version', 32)->nullable()->after('paper_criterion_scores');
        });

        Schema::table('defense_evaluation_student_scores', function (Blueprint $table): void {
            $table->json('presentation_criterion_scores')->nullable()->after('effectiveness_score');
        });
    }

    public function down(): void
    {
        Schema::table('defense_evaluation_student_scores', function (Blueprint $table): void {
            $table->dropColumn('presentation_criterion_scores');
        });

        Schema::table('defense_evaluations', function (Blueprint $table): void {
            $table->dropColumn(['paper_criterion_scores', 'rubric_version']);
        });

        Schema::table('defense_evaluation_rounds', function (Blueprint $table): void {
            $table->dropColumn('program_code');
        });
    }
};
