<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_form_signature_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('official_form_signature_id')
                ->unique()
                ->constrained('official_form_signatures')
                ->cascadeOnDelete();
            $table->char('public_reference', 36)->unique();
            $table->timestampsTz();
        });

        DB::table('official_form_signatures')
            ->orderBy('id')
            ->select('id')
            ->chunkById(250, function ($signatures): void {
                $now = now();
                $rows = collect($signatures)->map(fn ($signature): array => [
                    'official_form_signature_id' => $signature->id,
                    'public_reference' => (string) Str::uuid(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                if ($rows !== []) {
                    DB::table('official_form_signature_verifications')->insert($rows);
                }
            });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public.official_form_signature_verifications ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('official_form_signature_verifications');
    }
};
