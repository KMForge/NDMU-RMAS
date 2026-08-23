<?php

use App\Modules\ResearchProgress\Actions\ReconcileWorkflowMilestones;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('research-progress:reconcile', function (ReconcileWorkflowMilestones $action): void {
    $result = $action->execute();

    $this->info("Research progress reconciled: {$result['started']} title workflow record(s) processed; {$result['completed']} finalized RES-026 record(s) processed.");
})->purpose('Idempotently synchronize research milestones from authoritative workflow records');
