<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_group_adviser_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('official_form_instance_id');
            $table->foreignId('research_class_group_id');
            $table->foreignId('previous_adviser_id');
            $table->foreignId('requested_adviser_id');
            $table->foreignId('requested_by');
            $table->text('reason');
            $table->text('supporting_explanation')->nullable();
            $table->string('supporting_document_disk', 50)->nullable();
            $table->string('supporting_document_path', 500)->nullable();
            $table->string('supporting_document_original_name', 255)->nullable();
            $table->string('supporting_document_mime_type', 100)->nullable();
            $table->unsignedBigInteger('supporting_document_size')->nullable();
            $table->char('supporting_document_sha256', 64)->nullable();
            $table->string('status', 30)->default('submitted');
            $table->timestampTz('leader_confirmed_at');
            $table->foreignId('reviewed_by')->nullable();
            $table->text('reviewer_remarks')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('effective_at')->nullable();
            $table->timestampsTz();

            $table->index(['research_class_group_id', 'status'], 'adviser_change_group_status_idx');
            $table->index(['requested_adviser_id', 'status'], 'adviser_change_requested_status_idx');
            $table->unique('official_form_instance_id', 'adviser_change_form_unique');
            $table->foreign('official_form_instance_id', 'adviser_change_form_fk')->references('id')->on('official_form_instances')->cascadeOnDelete();
            $table->foreign('research_class_group_id', 'adviser_change_group_fk')->references('id')->on('research_class_groups')->cascadeOnDelete();
            $table->foreign('previous_adviser_id', 'adviser_change_previous_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('requested_adviser_id', 'adviser_change_requested_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('requested_by', 'adviser_change_requester_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('reviewed_by', 'adviser_change_reviewer_fk')->references('id')->on('users')->nullOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE research_group_adviser_change_requests ADD CONSTRAINT adviser_change_status_check CHECK (status IN ('submitted', 'approved', 'rejected', 'cancelled'))");
            DB::statement('ALTER TABLE research_group_adviser_change_requests ADD CONSTRAINT adviser_change_distinct_advisers_check CHECK (previous_adviser_id <> requested_adviser_id)');
            DB::statement("CREATE UNIQUE INDEX adviser_change_one_pending_per_group ON research_group_adviser_change_requests (research_class_group_id) WHERE status = 'submitted'");
            DB::statement('ALTER TABLE research_group_adviser_change_requests ENABLE ROW LEVEL SECURITY');
        }

        if (Schema::hasTable('official_form_definitions')) {
            DB::table('official_form_definitions')->where('code', 'RES-030')->update([
                'title' => 'Adviser Change Request Form',
                'default_category' => 'Adviser Change',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('research_group_adviser_change_requests');

        if (Schema::hasTable('official_form_definitions')) {
            DB::table('official_form_definitions')->where('code', 'RES-030')->update([
                'title' => 'Request for Change of Personnel',
                'default_category' => 'Personnel Change',
                'updated_at' => now(),
            ]);
        }
    }
};
