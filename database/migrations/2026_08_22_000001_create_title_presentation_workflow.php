<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('defense_panel_assignments', function (Blueprint $table): void {
            $table->string('panel_position', 32)->nullable()->after('user_id');
            $table->text('change_reason')->nullable()->after('ended_at');
            $table->index(['defense_id', 'panel_position', 'ended_at'], 'defense_panel_position_active_idx');
        });

        Schema::create('title_presentations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('defense_id')->unique()->constrained('defenses')->restrictOnDelete();
            $table->foreignId('official_form_instance_id')->unique()->constrained('official_form_instances')->restrictOnDelete();
            $table->foreignId('official_form_version_id')->constrained('official_form_versions')->restrictOnDelete();
            $table->string('status', 40)->default('scheduled')->index();
            $table->unsignedTinyInteger('approved_title_number')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('result_recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('result_recorded_at')->nullable();
            $table->timestamp('presented_at')->nullable();
            $table->foreignId('presentation_completed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['official_form_version_id', 'status'], 'title_presentation_version_status_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public.title_presentations ENABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.title_presentations ADD CONSTRAINT title_presentations_approved_number_check CHECK (approved_title_number IS NULL OR approved_title_number BETWEEN 1 AND 3);');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('title_presentations');

        Schema::table('defense_panel_assignments', function (Blueprint $table): void {
            $table->dropIndex('defense_panel_position_active_idx');
            $table->dropColumn(['panel_position', 'change_reason']);
        });
    }
};
