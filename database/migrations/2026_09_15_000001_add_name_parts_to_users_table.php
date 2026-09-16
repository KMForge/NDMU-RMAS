<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('first_name', 100)->nullable()->index();
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100)->nullable()->index();
            $table->string('suffix', 20)->nullable();
        });

        DB::table('users')->whereNull('first_name')->orderBy('id')->each(function ($user): void {
            $parts = preg_split('/\s+/', trim((string) $user->name)) ?: [];
            if (empty($parts) || $parts === ['']) {
                return;
            }

            if (count($parts) === 1) {
                DB::table('users')->where('id', $user->id)->update([
                    'first_name' => $parts[0],
                    'last_name' => $parts[0],
                ]);

                return;
            }

            $first = array_shift($parts);
            $last = array_pop($parts);
            $middle = ! empty($parts) ? implode(' ', $parts) : null;

            DB::table('users')->where('id', $user->id)->update([
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
            ]);
        });
    }
};
