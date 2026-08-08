<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_class_groups', function (Blueprint $table): void {
            $table->string('status', 20)->default('active')->after('created_by');
            $table->timestampTz('disbanded_at')->nullable()->after('status');
            $table->index(['research_class_id', 'status']);
        });

        Schema::create('research_class_group_adviser_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_class_group_id')->constrained('research_class_groups')->cascadeOnDelete();
            $table->foreignId('adviser_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestampTz('requested_at');
            $table->timestampTz('responded_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();

            $table->index(['research_class_group_id', 'status']);
            $table->index(['adviser_id', 'status']);
        });

        Schema::create('research_class_group_adviser_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_class_group_id')->constrained('research_class_groups')->cascadeOnDelete();
            $table->foreignId('adviser_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('assigned_at');
            $table->timestampTz('ended_at')->nullable();
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['research_class_group_id', 'adviser_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public.research_class_group_adviser_requests ENABLE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE public.research_class_group_adviser_histories ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('research_class_group_adviser_histories');
        Schema::dropIfExists('research_class_group_adviser_requests');

        Schema::table('research_class_groups', function (Blueprint $table): void {
            $table->dropIndex(['research_class_id', 'status']);
            $table->dropColumn(['status', 'disbanded_at']);
        });
    }
};
