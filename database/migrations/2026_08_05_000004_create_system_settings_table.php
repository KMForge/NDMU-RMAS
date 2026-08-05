<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('system_name')->default('NDMU Research Management and Assistance System');
            $table->string('support_email')->default('research@ndmu.edu.ph');
            $table->boolean('student_registration_enabled')->default(true);
            $table->boolean('email_notifications_enabled')->default(true);
            $table->string('maintenance_notice', 500)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        DB::table('system_settings')->insert([
            'system_name' => 'NDMU Research Management and Assistance System',
            'support_email' => 'research@ndmu.edu.ph',
            'student_registration_enabled' => true,
            'email_notifications_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
