<?php

use App\Enums\AccountStatus;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\ResearchProgress\Actions\ReconcileWorkflowMilestones;
use App\Modules\ResearchProgress\Actions\ResetDryRunGroupProgress;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('research-progress:reconcile', function (ReconcileWorkflowMilestones $action): void {
    $result = $action->execute();

    $this->info("Research progress reconciled: {$result['started']} title workflow record(s) processed; {$result['completed']} finalized RES-026 record(s) processed.");
})->purpose('Idempotently synchronize research milestones from authoritative workflow records');

Artisan::command('user:verify {email?}', function (?string $email = null) {
    if ($email) {
        $user = User::where('email', $email)->orWhere('student_id', $email)->first();
        if (! $user) {
            $this->error("User not found for: {$email}");

            return 1;
        }
        $user->forceFill([
            'email_verified_at' => now(),
            'status' => AccountStatus::Active,
            'approved_at' => $user->approved_at ?? now(),
        ])->save();
        $this->info("User {$user->name} ({$user->email}) is now verified and active.");

        return 0;
    }

    $count = 0;
    foreach (User::whereNull('email_verified_at')->orWhere('status', AccountStatus::Pending)->get() as $user) {
        $user->forceFill([
            'email_verified_at' => $user->email_verified_at ?? now(),
            'status' => AccountStatus::Active,
            'approved_at' => $user->approved_at ?? now(),
        ])->save();
        $count++;
    }
    $this->info("Verified and activated {$count} user account(s).");

    return 0;
})->purpose('Force mark a user or all pending users as email verified and active');

Artisan::command('dryrun:reset {group_id?}', function (ResetDryRunGroupProgress $action) {
    $groupId = $this->argument('group_id');
    $targetGroup = null;
    if ($groupId !== null) {
        $targetGroup = ResearchClassGroup::find($groupId);
        if (! $targetGroup) {
            $this->error("Research class group not found for ID: {$groupId}");

            return 1;
        }
    }

    $result = $action->execute($targetGroup);

    $this->info("Reset {$result['groups_reset']} group(s): ".implode(', ', $result['group_names']));
    $this->line("- Defenses deleted: {$result['defenses_deleted']}");
    $this->line("- Official forms deleted: {$result['forms_deleted']}");
    $this->line("- Milestones reset: {$result['milestones_reset']}");

    return 0;
})->purpose('Delete progress, forms, and defense schedules for DRY RUN groups');
