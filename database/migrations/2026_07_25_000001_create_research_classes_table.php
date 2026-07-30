<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_classes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('adviser_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('creation_token');
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->char('join_code_hash', 64)->unique();
            $table->text('join_code_encrypted');
            $table->unsignedSmallInteger('max_students')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['adviser_id', 'creation_token']);
            $table->index(['adviser_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_classes');
    }
};
