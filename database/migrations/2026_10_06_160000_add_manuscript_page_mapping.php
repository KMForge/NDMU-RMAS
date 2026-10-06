<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->json('page_mapping')->nullable();
        });
        Schema::table('document_review_comments', function (Blueprint $table): void {
            $table->string('page_label', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('document_review_comments', fn (Blueprint $table) => $table->dropColumn('page_label'));
        Schema::table('documents', fn (Blueprint $table) => $table->dropColumn('page_mapping'));
    }
};
