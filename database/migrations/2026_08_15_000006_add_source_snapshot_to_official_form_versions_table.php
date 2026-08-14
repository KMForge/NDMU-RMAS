<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('official_form_versions', function (Blueprint $table) {
            $table->json('source_snapshot')->nullable()->after('payload');
        });
    }

    public function down(): void
    {
        Schema::table('official_form_versions', function (Blueprint $table) {
            $table->dropColumn('source_snapshot');
        });
    }
};
