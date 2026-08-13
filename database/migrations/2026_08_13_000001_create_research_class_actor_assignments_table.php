<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_class_actor_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_class_id')->constrained('research_classes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('actor_type', 64);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->string('status', 32)->default('active');
            $table->timestamps();

            $table->unique(['research_class_id', 'actor_type', 'user_id'], 'class_actor_assignment_unique');
            $table->index(['user_id', 'actor_type', 'status'], 'class_actor_user_lookup');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE research_class_actor_assignments ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('research_class_actor_assignments');
    }
};
