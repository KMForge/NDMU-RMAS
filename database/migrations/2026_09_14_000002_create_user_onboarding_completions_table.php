<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_onboarding_completions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('workspace', 32);
            $table->string('status', 16);
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->unique(['user_id', 'workspace'], 'user_onboarding_workspace_unique');
            $table->index(['workspace', 'status'], 'onboarding_workspace_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_onboarding_completions');
    }
};
