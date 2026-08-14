<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('defenses', function (Blueprint $table) {
            $table->foreignId('current_schedule_id')->nullable()->after('status')->constrained('defense_schedules')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('defenses', function (Blueprint $table) {
            $table->dropForeign(['current_schedule_id']);
            $table->dropColumn('current_schedule_id');
        });
    }
};
