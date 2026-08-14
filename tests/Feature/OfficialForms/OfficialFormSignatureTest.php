<?php

namespace Tests\Feature\OfficialForms;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Models\UserSignature;
use App\Modules\OfficialForms\Actions\ApplyOfficialFormSignature;
use App\Modules\OfficialForms\Actions\CreateOfficialFormInstance;
use App\Modules\OfficialForms\Actions\SaveOfficialFormDraft;
use App\Modules\OfficialForms\Actions\SyncOfficialFormCatalog;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OfficialFormSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        (new SyncOfficialFormCatalog)->handle();

        Config::set('signatures.verification_key', 'test_secret_verification_key_32_bytes_long!!');
        Config::set('signatures.verification_key_version', 'v1');
    }

    public function test_signed_action_creates_applied_signature_and_verification_records(): void
    {
        [$adviser, $instance] = $this->createFormInstanceForAdviser('RES-040');
        $this->enrollSignature($adviser);

        $actionHandler = app(ApplyOfficialFormSignature::class);
        $sigRecord = $actionHandler->handle(
            $adviser,
            $instance->id,
            $instance->current_version_id,
            'endorse',
            'research_adviser'
        );

        $this->assertDatabaseHas('official_form_signatures', [
            'id' => $sigRecord->id,
            'official_form_instance_id' => $instance->id,
            'official_form_version_id' => $instance->current_version_id,
            'signer_user_id' => $adviser->id,
            'actor_type' => 'research_adviser',
            'academic_action' => 'endorse',
        ]);

        $this->assertDatabaseHas('official_form_verifications', [
            'official_form_version_id' => $instance->current_version_id,
        ]);

        Storage::disk('local')->assertExists($sigRecord->signature_storage_path);
    }

    public function test_missing_enrolled_signature_blocks_signed_action(): void
    {
        [$adviser, $instance] = $this->createFormInstanceForAdviser('RES-040');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No digital signature registered');

        app(ApplyOfficialFormSignature::class)->handle(
            $adviser,
            $instance->id,
            $instance->current_version_id,
            'endorse',
            'research_adviser'
        );
    }

    public function test_stale_expected_version_cannot_be_signed(): void
    {
        [$adviser, $instance] = $this->createFormInstanceForAdviser('RES-040');
        $this->enrollSignature($adviser);

        $staleVersionId = 99999;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stale form version');

        app(ApplyOfficialFormSignature::class)->handle(
            $adviser,
            $instance->id,
            $staleVersionId,
            'endorse',
            'research_adviser'
        );
    }

    public function test_creating_version_2_leaves_version_1_signatures_intact_and_version_2_unsigned(): void
    {
        $student = User::factory()->create([
            'user_type' => UserType::Student,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $student->assignRole('student');
        Permission::findOrCreate('forms.res-049.sign');
        $student->givePermissionTo('forms.res-049.sign');

        $group = $this->createGroup(leader: $student);
        $student->update(['research_class_group_id' => $group->id]);

        $instance = app(CreateOfficialFormInstance::class)->handle(
            $student,
            'RES-049',
            $group->id,
            null,
            'general'
        );

        $this->enrollSignature($student);

        $actionHandler = app(ApplyOfficialFormSignature::class);
        $sig1 = $actionHandler->handle(
            $student,
            $instance->id,
            $instance->current_version_id,
            'sign_authorship',
            'student_researcher'
        );

        $v1Id = $instance->current_version_id;

        // Save v2 draft
        app(SaveOfficialFormDraft::class)->handle($student, $instance, ['authorship_confirmed' => true]);
        $instance->refresh();

        $v2Id = $instance->current_version_id;
        $this->assertNotEquals($v1Id, $v2Id);

        // v1 retains its signature
        $this->assertDatabaseHas('official_form_signatures', [
            'id' => $sig1->id,
            'official_form_version_id' => $v1Id,
        ]);

        // v2 has no signatures
        $this->assertDatabaseMissing('official_form_signatures', [
            'official_form_version_id' => $v2Id,
        ]);
    }

    public function test_missing_signature_verification_key_fails_closed(): void
    {
        Config::set('signatures.verification_key', null);

        [$adviser, $instance] = $this->createFormInstanceForAdviser('RES-040');
        $this->enrollSignature($adviser);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SIGNATURE_VERIFICATION_KEY is not configured');

        app(ApplyOfficialFormSignature::class)->handle(
            $adviser,
            $instance->id,
            $instance->current_version_id,
            'endorse',
            'research_adviser'
        );
    }

    public function test_corrupt_enrolled_specimen_cannot_be_applied(): void
    {
        [$adviser, $instance] = $this->createFormInstanceForAdviser('RES-040');
        $this->enrollSignature($adviser);

        $specimen = UserSignature::query()->where('user_id', $adviser->id)->first();
        // Tamper file content on disk
        Storage::disk('local')->put($specimen->storage_path, 'corrupted content');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Signature specimen integrity check failed');

        app(ApplyOfficialFormSignature::class)->handle(
            $adviser,
            $instance->id,
            $instance->current_version_id,
            'endorse',
            'research_adviser'
        );
    }

    public function test_res049_sign_authorship_does_not_change_instance_status_without_verified_rule(): void
    {
        $student = User::factory()->create([
            'user_type' => UserType::Student,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $student->assignRole('student');
        Permission::findOrCreate('forms.res-049.sign');
        $student->givePermissionTo('forms.res-049.sign');

        $group = $this->createGroup(leader: $student);
        $student->update(['research_class_group_id' => $group->id]);

        $instance = app(CreateOfficialFormInstance::class)->handle(
            $student,
            'RES-049',
            $group->id,
            null,
            'general'
        );

        $initialStatus = $instance->status;
        $this->enrollSignature($student);

        $sigRecord = app(ApplyOfficialFormSignature::class)->handle(
            $student,
            $instance->id,
            $instance->current_version_id,
            'sign_authorship',
            'student_researcher'
        );

        $instance->refresh();

        // Status remains unchanged (no invented transition)
        $this->assertSame($initialStatus, $instance->status);
        $this->assertDatabaseHas('official_form_signatures', [
            'id' => $sigRecord->id,
            'official_form_version_id' => $instance->current_version_id,
            'signer_user_id' => $student->id,
            'academic_action' => 'sign_authorship',
        ]);
    }

    private function createFormInstanceForAdviser(string $code = 'RES-040'): array
    {
        $adviser = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $adviser->assignRole('thesis-adviser');
        Permission::findOrCreate('forms.res-040.endorse');
        Permission::findOrCreate('forms.res-040.view');
        $adviser->givePermissionTo('forms.res-040.endorse', 'forms.res-040.view');

        $group = $this->createGroup(adviser: $adviser);

        $instance = app(CreateOfficialFormInstance::class)->handle(
            $adviser,
            $code,
            $group->id,
            null,
            'general'
        );

        return [$adviser, $instance];
    }

    private function createGroup(?User $leader = null, ?User $adviser = null): ResearchClassGroup
    {
        $facilitator = User::factory()->create(['user_type' => UserType::Faculty]);
        $class = ResearchClass::query()->forceCreate([
            'facilitator_id' => $facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone Class',
            'join_code_hash' => hash('sha256', 'CAP-'.strtoupper(bin2hex(random_bytes(3)))),
            'join_code_encrypted' => 'CAP-123456',
            'is_active' => true,
        ]);

        $leaderUser = $leader ?? User::factory()->create(['user_type' => UserType::Student]);

        $group = ResearchClassGroup::query()->create([
            'research_class_id' => $class->id,
            'name' => 'Group '.bin2hex(random_bytes(3)),
            'leader_student_id' => $leaderUser->id,
            'adviser_id' => $adviser?->id,
            'created_by' => $facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'status' => 'active',
        ]);

        $enrollment = ResearchClassEnrollment::query()->create([
            'research_class_id' => $class->id,
            'student_id' => $leaderUser->id,
            'status' => 'active',
            'requested_at' => now(),
            'joined_at' => now(),
        ]);

        $group->members()->create([
            'student_id' => $leaderUser->id,
            'research_class_id' => $class->id,
            'research_class_enrollment_id' => $enrollment->id,
            'assigned_by' => $facilitator->id,
        ]);

        return $group;
    }

    private function enrollSignature(User $user): UserSignature
    {
        $pngHeader = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15c4\x00\x00\x00\rIDATx\x9cc\xf8\xff\xff?\x03\x00\x05\xfe\x02\xfe\xa79\xfd\x05\x00\x00\x00\x00IEND\xaeB`\x82";
        $file = UploadedFile::fake()->createWithContent('signature.png', $pngHeader);

        $this->actingAs($user)->putJson(route('signature.store'), ['signature' => $file])->assertOk();

        return UserSignature::query()->where('user_id', $user->id)->sole();
    }
}
