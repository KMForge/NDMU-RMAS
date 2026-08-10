<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_reviews', function (Blueprint $table): void {
            $table->foreignId('supersedes_review_id')
                ->nullable()
                ->after('reviewer_id')
                ->constrained('document_reviews')
                ->nullOnDelete();
            $table->boolean('is_superseded')->default(false)->after('supersedes_review_id');
            $table->text('correction_reason')->nullable()->after('review_notes');

            $table->index(['document_id', 'is_superseded', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('document_reviews', function (Blueprint $table): void {
            $table->dropIndex(['document_id', 'is_superseded', 'reviewed_at']);
            $table->dropForeign(['supersedes_review_id']);
            $table->dropColumn(['supersedes_review_id', 'is_superseded', 'correction_reason']);
        });
    }
};
