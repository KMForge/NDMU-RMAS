<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_signatures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('storage_disk', 50);
            $table->text('storage_path')->unique();
            $table->string('original_filename', 255);
            $table->string('mime_type', 50);
            $table->unsignedBigInteger('file_size');
            $table->char('content_sha256', 64);
            $table->timestampTz('registered_at');
            $table->timestampsTz();

            $table->index(['user_id', 'registered_at']);
            $table->index('content_sha256');
        });

        Schema::create('signature_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_signature_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 30);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampsTz();

            $table->index(['user_id', 'occurred_at']);
            $table->index(['action', 'occurred_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public.user_signatures ENABLE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE public.signature_audits ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_audits');
        Schema::dropIfExists('user_signatures');
    }
};
