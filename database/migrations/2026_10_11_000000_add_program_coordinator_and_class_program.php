<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table): void {
            $table->foreignId('coordinator_user_id')->nullable()->index()->constrained('users')->restrictOnDelete();
        });
        Schema::table('research_classes', function (Blueprint $table): void {
            $table->foreignId('program_id')->nullable()->index()->constrained('programs')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('research_classes', function (Blueprint $table): void {
            $table->dropIndex(['program_id']);
            $table->dropConstrainedForeignId('program_id');
        });
        Schema::table('programs', function (Blueprint $table): void {
            $table->dropIndex(['coordinator_user_id']);
            $table->dropConstrainedForeignId('coordinator_user_id');
        });
    }
};
