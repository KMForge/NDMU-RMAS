<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('official_form_definitions')
            ->where('code', 'RES-027')
            ->update(['cardinality' => 'per_actor']);
    }

    public function down(): void
    {
        DB::table('official_form_definitions')
            ->where('code', 'RES-027')
            ->update(['cardinality' => 'single_per_group']);
    }
};
