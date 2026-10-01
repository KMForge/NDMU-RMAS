<?php

namespace Tests\Feature;

use App\Livewire\AdminDashboard;
use App\Models\SystemBackup;
use App\Models\SystemBackupSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SystemBackupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_administrator_can_view_and_update_backup_schedule(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->set('tab', 'backups')
            ->assertSee('Backup Management')
            ->set('backupScheduleEnabled', true)
            ->set('backupFrequency', 'weekly')
            ->set('backupRunTime', '22:30')
            ->set('backupRetentionCount', 8)
            ->call('saveBackupSchedule')
            ->assertHasNoErrors()
            ->assertSee('Backup schedule saved successfully.');

        $this->assertDatabaseHas('system_backup_settings', [
            'enabled' => true,
            'frequency' => 'weekly',
            'run_time' => '22:30',
            'retention_count' => 8,
            'updated_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'system-backup.schedule-updated',
            'user_id' => $admin->id,
        ]);
    }

    public function test_only_authorized_administrator_can_download_a_completed_backup(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        $student = User::factory()->create();
        $student->assignRole('student-researcher');

        Storage::disk('local')->put('system-backups/example.zip', 'archive');
        $backup = SystemBackup::query()->create([
            'filename' => 'example.zip',
            'storage_disk' => 'local',
            'storage_path' => 'system-backups/example.zip',
            'status' => 'completed',
            'trigger' => 'manual',
            'size_bytes' => 7,
            'sha256' => hash('sha256', 'archive'),
            'triggered_by' => $admin->id,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($student)->get(route('admin.backups.download', $backup))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.backups.download', $backup))
            ->assertOk()
            ->assertDownload('example.zip');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'system-backup.downloaded',
            'user_id' => $admin->id,
            'auditable_id' => $backup->id,
        ]);
    }

    public function test_administrator_can_delete_a_backup_and_its_private_archive(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        Storage::disk('local')->put('system-backups/delete-me.zip', 'archive');

        $backup = SystemBackup::query()->create([
            'filename' => 'delete-me.zip',
            'storage_disk' => 'local',
            'storage_path' => 'system-backups/delete-me.zip',
            'status' => 'completed',
            'trigger' => 'manual',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($admin);
        Livewire::test(AdminDashboard::class)
            ->call('deleteSystemBackup', $backup->id)
            ->assertHasNoErrors();

        Storage::disk('local')->assertMissing('system-backups/delete-me.zip');
        $this->assertDatabaseMissing('system_backups', ['id' => $backup->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'system-backup.deleted', 'user_id' => $admin->id]);
    }

    public function test_backup_schedule_defaults_to_disabled(): void
    {
        $settings = SystemBackupSetting::query()->firstOrFail();

        $this->assertFalse($settings->enabled);
        $this->assertSame('daily', $settings->frequency);
        $this->assertSame(14, $settings->retention_count);
    }
}
