<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_form_signatures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('official_form_instance_id')->constrained('official_form_instances')->restrictOnDelete();
            $table->foreignId('official_form_version_id')->constrained('official_form_versions')->restrictOnDelete();
            $table->foreignId('signer_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('user_signature_id')->nullable()->constrained('user_signatures')->nullOnDelete();
            $table->string('actor_type', 64);
            $table->string('academic_action', 64);
            $table->string('signer_name_snapshot', 255);
            $table->string('signer_email_snapshot', 255);
            $table->string('signature_storage_disk', 50)->default('local');
            $table->text('signature_storage_path');
            $table->char('signature_sha256', 64);
            $table->char('version_payload_sha256', 64);
            $table->char('attestation_hash', 64);
            $table->string('attestation_key_version', 16)->default('v1');
            $table->timestampTz('signed_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampsTz();

            $table->unique(['official_form_version_id', 'signer_user_id', 'actor_type', 'academic_action'], 'idx_official_form_signatures_unique');
            $table->index(['official_form_instance_id', 'official_form_version_id'], 'idx_form_signatures_instance_version');
        });

        Schema::create('official_form_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('official_form_version_id')->unique()->constrained('official_form_versions')->restrictOnDelete();
            $table->char('public_reference', 36)->unique();
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public.official_form_signatures ENABLE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE public.official_form_verifications ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('official_form_verifications');
        Schema::dropIfExists('official_form_signatures');
    }
};
