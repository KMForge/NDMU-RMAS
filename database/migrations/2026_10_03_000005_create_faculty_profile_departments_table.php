<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_profile_departments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('faculty_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['faculty_profile_id', 'department_id'], 'faculty_profile_department_unique');
            $table->index(['department_id', 'faculty_profile_id'], 'department_faculty_profile_index');
        });

        $now = now();
        DB::table('faculty_profiles')
            ->select(['id', 'department_id'])
            ->orderBy('id')
            ->chunkById(500, function ($profiles) use ($now): void {
                DB::table('faculty_profile_departments')->insertOrIgnore(
                    $profiles->map(fn ($profile): array => [
                        'faculty_profile_id' => $profile->id,
                        'department_id' => $profile->department_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all(),
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_profile_departments');
    }
};
