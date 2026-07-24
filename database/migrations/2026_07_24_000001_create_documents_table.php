<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('submission_token');
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('file_type', 10);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('file_size');
            $table->string('storage_disk', 50);
            $table->text('storage_path');
            $table->char('content_sha256', 64);
            $table->timestampTz('submitted_at');
            $table->string('status', 32)->default('pending');
            $table->timestampsTz();

            $table->unique(['user_id', 'submission_token']);
            $table->unique('storage_path');
            $table->index(['user_id', 'submitted_at']);
            $table->index(['status', 'submitted_at']);
            $table->index('content_sha256');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
