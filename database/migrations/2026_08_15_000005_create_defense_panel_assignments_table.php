<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defense_panel_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defense_id')->constrained('defenses')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('assigned_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['defense_id', 'user_id']);
            $table->index(['user_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defense_panel_assignments');
    }
};
