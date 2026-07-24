<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_class_enrollments', function (Blueprint $table): void {
            $table->string('status', 20)->default('pending')->change();
            $table->timestampTz('joined_at')->nullable()->change();
            $table->timestampTz('requested_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->index(
                ['research_class_id', 'status', 'requested_at'],
                'class_enrollments_request_queue_index',
            );
        });

        DB::table('research_class_enrollments')
            ->whereNull('requested_at')
            ->update(['requested_at' => DB::raw('COALESCE(joined_at, created_at)')]);
    }

    public function down(): void
    {
        DB::table('research_class_enrollments')
            ->whereNull('joined_at')
            ->update(['joined_at' => DB::raw('COALESCE(requested_at, created_at)')]);

        Schema::table('research_class_enrollments', function (Blueprint $table): void {
            $table->dropIndex('class_enrollments_request_queue_index');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['requested_at', 'reviewed_at']);
            $table->string('status', 20)->default('active')->change();
            $table->timestampTz('joined_at')->nullable(false)->change();
        });
    }
};
