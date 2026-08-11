<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'milestone_definitions',
        'research_group_milestones',
        'research_group_milestone_events',
        'milestone_evidences',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tables as $table) {
            DB::statement("ALTER TABLE public.{$table} ENABLE ROW LEVEL SECURITY");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_reverse($this->tables) as $table) {
            DB::statement("ALTER TABLE public.{$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
