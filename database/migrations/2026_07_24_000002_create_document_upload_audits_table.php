<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_upload_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_filename')->nullable();
            $table->string('ip_address', 45);
            $table->timestampTz('attempted_at');
            $table->string('upload_status', 16);
            $table->string('failure_reason', 500)->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'attempted_at']);
            $table->index(['upload_status', 'attempted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_upload_audits');
    }
};
