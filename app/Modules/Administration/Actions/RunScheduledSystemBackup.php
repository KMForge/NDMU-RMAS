<?php

namespace App\Modules\Administration\Actions;

use App\Models\SystemBackup;
use App\Models\SystemBackupSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class RunScheduledSystemBackup
{
    public function __construct(private readonly CreateSystemBackup $createBackup) {}

    public function handle(): bool
    {
        $timezone = (string) config('ndmu-rmas.timezone', config('app.timezone'));
        $now = CarbonImmutable::now($timezone);
        $shouldRun = false;

        DB::transaction(function () use ($now, &$shouldRun): void {
            $settings = SystemBackupSetting::query()->lockForUpdate()->first();
            if (! $settings?->enabled || $now->format('H:i') < substr((string) $settings->run_time, 0, 5)) {
                return;
            }

            $periodStart = match ($settings->frequency) {
                'weekly' => $now->startOfWeek(),
                'monthly' => $now->startOfMonth(),
                default => $now->startOfDay(),
            };

            if ($settings->last_scheduled_for?->setTimezone($now->timezone)->gte($periodStart)) {
                return;
            }

            $settings->update(['last_scheduled_for' => $now->utc()]);
            $shouldRun = true;
        });

        if (! $shouldRun) {
            return false;
        }

        try {
            $this->createBackup->handle(null, 'scheduled');
            $this->prune();
        } catch (Throwable) {
            // The failed record and sanitized reason are persisted by CreateSystemBackup.
        }

        return true;
    }

    private function prune(): void
    {
        $keep = max(1, (int) SystemBackupSetting::query()->value('retention_count'));
        $expired = SystemBackup::query()
            ->where('status', 'completed')
            ->latest('completed_at')
            ->latest('id')
            ->skip($keep)
            ->take(500)
            ->get();

        foreach ($expired as $backup) {
            if ($backup->storage_path !== null) {
                Storage::disk($backup->storage_disk)->delete($backup->storage_path);
            }
            $backup->delete();
        }
    }
}
