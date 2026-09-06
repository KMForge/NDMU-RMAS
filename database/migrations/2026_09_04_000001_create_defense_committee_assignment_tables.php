<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('research_class_panel_committees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_class_id')->constrained('research_classes')->cascadeOnDelete();
            $table->string('defense_type', 50);
            $table->foreignId('chairperson_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['research_class_id', 'defense_type'], 'rc_panel_comm_class_type_unique');
        });

        Schema::create('research_class_panel_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained('research_class_panel_committees')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('panel_position', 20); // member_1, member_2
            $table->timestamps();

            $table->unique(['committee_id', 'user_id'], 'rc_panel_members_user_unique');
            $table->unique(['committee_id', 'panel_position'], 'rc_panel_members_position_unique');
        });

        Schema::create('research_group_panel_committees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_class_group_id')->constrained('research_class_groups')->cascadeOnDelete();
            $table->string('defense_type', 50);
            $table->foreignId('chairperson_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_custom')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['research_class_group_id', 'defense_type'], 'rg_panel_comm_group_type_unique');
        });

        Schema::create('research_group_panel_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained('research_group_panel_committees')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('panel_position', 20); // member_1, member_2
            $table->timestamps();

            $table->unique(['committee_id', 'user_id'], 'rg_panel_members_user_unique');
            $table->unique(['committee_id', 'panel_position'], 'rg_panel_members_position_unique');
        });

        Schema::create('defense_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_class_id')->nullable()->constrained('research_classes')->nullOnDelete();
            $table->string('defense_type', 50);
            $table->foreignId('room_id')->constrained('defense_rooms')->cascadeOnDelete();
            $table->date('session_date');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status', 30)->default('current'); // current, completed, cancelled
            $table->foreignId('scheduled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['session_date', 'room_id']);
            $table->index(['starts_at', 'ends_at']);
        });

        Schema::table('defense_schedules', function (Blueprint $table) {
            $table->foreignId('defense_session_id')->nullable()->after('supersedes_schedule_id')->constrained('defense_sessions')->nullOnDelete();
            $table->unsignedSmallInteger('presentation_order')->nullable()->after('defense_session_id');

            $table->index(['defense_session_id', 'presentation_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('defense_schedules', function (Blueprint $table) {
            $table->dropIndex(['defense_session_id', 'presentation_order']);
            $table->dropConstrainedForeignId('defense_session_id');
            $table->dropColumn('presentation_order');
        });

        Schema::dropIfExists('defense_sessions');
        Schema::dropIfExists('research_group_panel_members');
        Schema::dropIfExists('research_group_panel_committees');
        Schema::dropIfExists('research_class_panel_members');
        Schema::dropIfExists('research_class_panel_committees');
    }
};
