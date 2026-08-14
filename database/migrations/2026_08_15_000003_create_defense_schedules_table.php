<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defense_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defense_id')->constrained('defenses')->onDelete('restrict');
            $table->foreignId('room_id')->constrained('defense_rooms')->onDelete('restrict');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('current');
            $table->foreignId('scheduled_by')->constrained('users')->onDelete('restrict');
            $table->text('reason')->nullable();
            $table->foreignId('supersedes_schedule_id')->nullable()->constrained('defense_schedules')->onDelete('restrict');
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
            $table->index(['room_id', 'status']);
            $table->index(['defense_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defense_schedules');
    }
};
