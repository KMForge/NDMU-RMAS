<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('official_form_instances', function (Blueprint $table) {
            $table->foreignId('defense_evaluation_id')->nullable()->constrained('defense_evaluations')->onDelete('restrict');
            $table->index('defense_evaluation_id');
        });
    }

    public function down(): void
    {
        Schema::table('official_form_instances', function (Blueprint $table) {
            $table->dropForeign(['defense_evaluation_id']);
            $table->dropColumn('defense_evaluation_id');
        });
    }
};
