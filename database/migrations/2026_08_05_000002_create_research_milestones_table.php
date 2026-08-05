<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_milestones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->timestampTz('due_at')->nullable();
            $table->unsignedSmallInteger('sequence');
            $table->boolean('is_required')->default(true);
            $table->timestampsTz();

            $table->unique(['academic_term_id', 'program_id', 'sequence']);
            $table->unique(['academic_term_id', 'program_id', 'name']);
            $table->index(['academic_term_id', 'program_id', 'is_required']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public.research_milestones ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('research_milestones');
    }
};
