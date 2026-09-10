<?php

use App\Modules\ResearchProgress\Actions\ResetDryRunGroupProgress;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(ResetDryRunGroupProgress::class)->execute();
    }

    public function down(): void
    {
        // Deletion of test/dry-run records is irreversible by design.
    }
};
