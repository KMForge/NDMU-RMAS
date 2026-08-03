<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_classes', function (Blueprint $table): void {
            $table->renameColumn('adviser_id', 'facilitator_id');
        });

        Schema::create('research_class_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('research_group_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('creation_token');
            $table->string('name', 120);
            $table->foreignId('adviser_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['research_class_id', 'creation_token']);
            $table->unique(['research_class_id', 'name']);
            $table->unique('research_group_id');
            $table->index(['adviser_id', 'research_class_id']);
        });

        Schema::create('research_class_group_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_class_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('research_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('research_class_enrollment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['research_class_id', 'student_id']);
            $table->unique(['research_class_group_id', 'student_id']);
            $table->index(['research_class_group_id', 'created_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public.research_class_groups ENABLE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE public.research_class_group_members ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('research_class_group_members');
        Schema::dropIfExists('research_class_groups');

        Schema::table('research_classes', function (Blueprint $table): void {
            $table->renameColumn('facilitator_id', 'adviser_id');
        });
    }
};
