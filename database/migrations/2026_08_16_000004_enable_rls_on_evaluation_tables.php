<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public.defense_evaluation_rounds ENABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defense_evaluation_round_panelists ENABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defense_evaluation_round_students ENABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defense_evaluations ENABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defense_evaluation_student_scores ENABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defense_evaluation_summaries ENABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defense_evaluation_student_summaries ENABLE ROW LEVEL SECURITY;');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public.defense_evaluation_rounds DISABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defense_evaluation_round_panelists DISABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defense_evaluation_round_students DISABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defense_evaluations DISABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defense_evaluation_student_scores DISABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defense_evaluation_summaries DISABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE public.defense_evaluation_student_summaries DISABLE ROW LEVEL SECURITY;');
        }
    }
};
