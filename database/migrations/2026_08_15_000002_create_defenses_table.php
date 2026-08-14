<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_class_group_id')->constrained('research_class_groups')->onDelete('restrict');
            $table->string('defense_type');
            $table->string('status')->default('scheduled');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index(['research_class_group_id', 'defense_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defenses');
    }
};
