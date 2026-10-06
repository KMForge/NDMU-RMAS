<?php

namespace Tests\Feature;

use App\Livewire\AdminDashboard;
use App\Models\SystemBackup;
use App\Models\SystemBackupSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

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
            ->set('backupMaxImportMb', 64)
            ->call('saveBackupSchedule')
            ->assertHasNoErrors()
            ->assertSee('Backup schedule saved successfully.');

        $this->assertDatabaseHas('system_backup_settings', [
            'enabled' => true,
            'frequency' => 'weekly',
            'run_time' => '22:30',
            'retention_count' => 8,
            'max_import_mb' => 64,
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
            ->call('prepareSystemBackupDelete', $backup->id)
            ->assertSet('backupPendingDeleteId', $backup->id)
            ->set('backupDeleteConfirmation', 'DELETE delete-me.zip')
            ->call('deleteSystemBackup')
            ->assertHasNoErrors();

        Storage::disk('local')->assertMissing('system-backups/delete-me.zip');
        $this->assertDatabaseMissing('system_backups', ['id' => $backup->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'system-backup.deleted', 'user_id' => $admin->id]);
    }

    public function test_backup_cannot_be_deleted_without_record_bound_filename_confirmation(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        Storage::disk('local')->put('system-backups/protected.zip', 'archive');
        $backup = SystemBackup::query()->create([
            'filename' => 'protected.zip',
            'storage_disk' => 'local',
            'storage_path' => 'system-backups/protected.zip',
            'status' => 'completed',
            'trigger' => 'manual',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($admin);
        Livewire::test(AdminDashboard::class)
            ->set('tab', 'backups')
            ->call('prepareSystemBackupDelete', $backup->id)
            ->set('backupDeleteConfirmation', 'DELETE a-different-backup.zip')
            ->call('deleteSystemBackup')
            ->assertHasErrors(['backupDeleteConfirmation']);

        Storage::disk('local')->assertExists('system-backups/protected.zip');
        $this->assertDatabaseHas('system_backups', ['id' => $backup->id]);
        $this->assertDatabaseMissing('audit_logs', ['event' => 'system-backup.deleted']);
    }

    public function test_backup_schedule_defaults_to_disabled(): void
    {
        $settings = SystemBackupSetting::query()->firstOrFail();

        $this->assertFalse($settings->enabled);
        $this->assertSame('daily', $settings->frequency);
        $this->assertSame(14, $settings->retention_count);
        $this->assertSame(10, $settings->max_import_mb);
    }

    public function test_livewire_upload_timeout_supports_large_backup_imports(): void
    {
        $this->assertSame(
            max(5, (int) config('backups.upload_timeout_minutes')),
            config('livewire.temporary_file_upload.max_upload_time'),
        );
    }

    public function test_administrator_can_verify_a_complete_backup_archive(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $archivePath = storage_path('framework/testing/verified-backup.zip');
        $zip = new ZipArchive;
        $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('database/database.dump', 'postgresql-dump-content');
        $zip->addFromString('manifest.json', json_encode([
            'application' => config('app.name'),
            'database_driver' => 'pgsql',
        ], JSON_THROW_ON_ERROR));
        $zip->close();
        $contents = file_get_contents($archivePath);
        Storage::disk('local')->put('system-backups/verified-backup.zip', $contents);

        $backup = SystemBackup::query()->create([
            'filename' => 'verified-backup.zip',
            'storage_disk' => 'local',
            'storage_path' => 'system-backups/verified-backup.zip',
            'status' => 'completed',
            'trigger' => 'manual',
            'size_bytes' => strlen($contents),
            'sha256' => hash('sha256', $contents),
            'triggered_by' => $admin->id,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($admin);
        Livewire::test(AdminDashboard::class)
            ->call('verifySystemBackup', $backup->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('system_backups', [
            'id' => $backup->id,
            'verification_status' => 'verified',
            'verified_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'system-backup.verified',
            'user_id' => $admin->id,
            'outcome' => 'succeeded',
        ]);
    }

    public function test_administrator_can_import_and_verify_a_complete_backup_archive(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        $dump = 'complete-postgresql-custom-dump';
        $archivePath = storage_path('framework/testing/import-backup.zip');
        $zip = new ZipArchive;
        $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('database/database.dump', $dump);
        $zip->addFromString('private-files/documents/evidence.pdf', 'private-document');
        $zip->addFromString('manifest.json', json_encode([
            'backup_format_version' => 2,
            'application' => config('app.name'),
            'database_driver' => 'pgsql',
            'database_scope' => 'complete_schema_and_data',
            'database_dump_sha256' => hash('sha256', $dump),
            'includes_private_files' => true,
            'private_file_count' => 1,
        ], JSON_THROW_ON_ERROR));
        $zip->close();

        $this->actingAs($admin);
        Livewire::test(AdminDashboard::class)
            ->set('tab', 'backups')
            ->set('backupImportFile', UploadedFile::fake()->createWithContent('portable-backup.zip', file_get_contents($archivePath)))
            ->call('importSystemBackup')
            ->assertHasNoErrors()
            ->assertSee('was imported and verified');

        $backup = SystemBackup::query()->where('trigger', 'imported')->firstOrFail();
        $this->assertSame('verified', $backup->verification_status);
        $this->assertSame('complete_schema_and_data', $backup->manifest['database_scope']);
        $this->assertStringStartsWith('system-backups/imports/', $backup->storage_path);
        Storage::disk('local')->assertExists($backup->storage_path);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'system-backup.imported',
            'user_id' => $admin->id,
            'auditable_id' => $backup->id,
        ]);
    }

    public function test_configured_backup_import_limit_is_enforced(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        SystemBackupSetting::query()->firstOrFail()->update(['max_import_mb' => 1]);

        $this->actingAs($admin);
        Livewire::test(AdminDashboard::class)
            ->set('tab', 'backups')
            ->set('backupImportFile', UploadedFile::fake()->create('too-large.zip', 2048, 'application/zip'))
            ->call('importSystemBackup')
            ->assertHasErrors('backupImportFile');

        $this->assertDatabaseMissing('system_backups', ['trigger' => 'imported']);
    }

    public function test_restore_requires_a_verified_backup_and_exact_typed_confirmation(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        $backup = SystemBackup::query()->create([
            'filename' => 'verified-restore.zip',
            'storage_disk' => 'local',
            'storage_path' => 'system-backups/verified-restore.zip',
            'status' => 'completed',
            'trigger' => 'manual',
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $admin->id,
            'triggered_by' => $admin->id,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($admin);
        Livewire::test(AdminDashboard::class)
            ->set('tab', 'backups')
            ->call('prepareSystemBackupRestore', $backup->id)
            ->assertSet('backupPendingRestoreId', $backup->id)
            ->assertSee('Confirm complete database restore')
            ->set('backupRestoreConfirmation', 'RESTORE wrong-file.zip')
            ->call('restoreSystemBackup')
            ->assertHasErrors('backupRestoreConfirmation');
    }
}
