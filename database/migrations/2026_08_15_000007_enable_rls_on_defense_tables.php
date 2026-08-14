<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public.defense_rooms ENABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defenses ENABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defense_schedules ENABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defense_panel_assignments ENABLE ROW LEVEL SECURITY;');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public.defense_rooms DISABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defenses DISABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defense_schedules DISABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defense_panel_assignments DISABLE ROW LEVEL SECURITY;');
        }
    }
};
