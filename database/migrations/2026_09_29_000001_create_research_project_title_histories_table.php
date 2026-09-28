<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_project_title_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_project_id')->constrained()->cascadeOnDelete();
            $table->string('previous_title', 500);
            $table->string('revised_title', 500);
            $table->text('reason');
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('effective_at');
            $table->timestampsTz();

            $table->index(['research_project_id', 'effective_at']);
            $table->index(['changed_by', 'effective_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_project_title_histories');
    }
};
