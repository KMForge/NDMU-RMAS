<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->foreignId('revision_request_id')
                ->nullable()
                ->after('user_id')
                ->constrained('revision_requests')
                ->nullOnDelete();

            $table->index(['revision_request_id', 'submitted_at']);
        });

        Schema::create('revision_request_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('revision_request_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('actor_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('document_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('action', 32);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampsTz();

            $table->index(['revision_request_id', 'occurred_at']);
            $table->index(['actor_id', 'occurred_at']);
            $table->index(['action', 'to_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revision_request_events');

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('revision_request_id');
        });
    }
};
