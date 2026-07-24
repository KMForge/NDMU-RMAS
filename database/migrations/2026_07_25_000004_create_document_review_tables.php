<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('decision', 32);
            $table->text('review_notes')->nullable();
            $table->timestampTz('reviewed_at');
            $table->timestampsTz();

            $table->index(['document_id', 'reviewed_at']);
            $table->index(['reviewer_id', 'reviewed_at']);
            $table->index(['decision', 'reviewed_at']);
        });

        Schema::create('document_review_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('document_review_comments')
                ->cascadeOnDelete();
            $table->unsignedInteger('page_number')->nullable();
            $table->string('severity', 20)->default('comment');
            $table->text('comment');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();

            $table->index(['document_id', 'resolved_at', 'created_at']);
            $table->index(['author_id', 'created_at']);
            $table->index(['severity', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_review_comments');
        Schema::dropIfExists('document_reviews');
    }
};
