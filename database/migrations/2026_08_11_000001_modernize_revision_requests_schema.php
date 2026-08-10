<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revision_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('revision_requests', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->change();
            }

            if (! Schema::hasColumn('revision_requests', 'research_class_group_id')) {
                $table->foreignId('research_class_group_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('research_class_groups')
                    ->restrictOnDelete();
            }

            if (! Schema::hasColumn('revision_requests', 'source_document_review_id')) {
                $table->foreignId('source_document_review_id')
                    ->nullable()
                    ->after('document_id')
                    ->constrained('document_reviews')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('revision_requests', 'submitted_document_id')) {
                $table->foreignId('submitted_document_id')
                    ->nullable()
                    ->after('source_document_review_id')
                    ->constrained('documents')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('revision_requests', 'source_type')) {
                $table->string('source_type', 32)->default('document_review')->after('submitted_document_id');
            }

            if (! Schema::hasColumn('revision_requests', 'invalidated_at')) {
                $table->timestampTz('invalidated_at')->nullable()->after('resolved_at');
                $table->text('invalidated_reason')->nullable()->after('invalidated_at');
            }
        });

        Schema::table('revision_requests', function (Blueprint $table): void {
            $table->unique('source_document_review_id', 'revision_requests_source_review_unique');
            $table->index(['research_class_group_id', 'status'], 'rev_req_group_status_idx');
            $table->index(['submitted_document_id'], 'rev_req_submitted_doc_idx');
            $table->index(['due_at'], 'rev_req_due_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('revision_requests', function (Blueprint $table): void {
            $table->dropUnique('revision_requests_source_review_unique');
            $table->dropIndex('rev_req_group_status_idx');
            $table->dropIndex('rev_req_submitted_doc_idx');
            $table->dropIndex('rev_req_due_at_idx');

            $table->dropConstrainedForeignId('research_class_group_id');
            $table->dropConstrainedForeignId('source_document_review_id');
            $table->dropConstrainedForeignId('submitted_document_id');
            $table->dropColumn(['source_type', 'invalidated_at', 'invalidated_reason']);
        });
    }
};
