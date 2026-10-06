<?php

use App\Enums\AccountStatus;
use App\Models\Defense;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\Administration\Actions\CreateSystemBackup;
use App\Modules\Administration\Actions\RunScheduledSystemBackup;
use App\Modules\ResearchProgress\Actions\ReconcileWorkflowMilestones;
use App\Modules\ResearchProgress\Actions\ResetDryRunGroupProgress;
use App\Modules\TitlePresentations\Actions\LinkScheduledTitlePresentation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('title-presentations:link-schedule {defense : Existing title-presentation defense ID}', function (int $defense, LinkScheduledTitlePresentation $action): int {
    $record = Defense::with('group.researchClass.facilitator')->findOrFail($defense);
    if ($record->defense_type !== 'title_presentation') {
        $this->error('This defense is not a Title Presentation.');

        return 1;
    }
    $actor = $record->group?->researchClass?->facilitator;
    if ($actor === null) {
        $this->error('The owning facilitator could not be resolved.');

        return 1;
    }
    try {
        $presentation = $action->handle($actor, $record);
        $this->info("Defense #{$record->id} is linked to RES-026 instance #{$presentation->official_form_instance_id} ({$presentation->status}). No approval or signature was changed.");

        return 0;
    } catch (InvalidArgumentException|AuthorizationException $exception) {
        $this->error($exception->getMessage());

        return 1;
    }
})->purpose('Link an existing title schedule and panel to its unambiguous submitted RES-026, preserving approvals');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('system-backup:run-scheduled', function (RunScheduledSystemBackup $action): int {
    $this->line($action->handle() ? 'Scheduled backup evaluated and started.' : 'No system backup is due.');

    return 0;
})->purpose('Create the due scheduled system backup');

Artisan::command('system-backup:create', function (CreateSystemBackup $action): int {
    $this->info('Creating system backup...');
    try {
        $backup = $action->handle(null, 'manual');
        if ($backup->status === 'completed') {
            $sizeMb = round(($backup->size_bytes ?? 0) / 1024 / 1024, 2);
            $this->info("Backup completed successfully: {$backup->filename} ({$sizeMb} MB)");
            $this->line("Location: storage/app/{$backup->storage_path}");

            return 0;
        }

        $this->error("Backup failed: {$backup->failure_message}");

        return 1;
    } catch (Throwable $e) {
        $this->error("Backup error: {$e->getMessage()}");

        return 1;
    }
})->purpose('Create an immediate manual system backup archive');

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
