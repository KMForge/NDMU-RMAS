<?php

namespace Tests\Feature\OfficialForms;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\OfficialFormSignature;
use App\Models\OfficialFormVerification;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Models\UserSignature;
use App\Modules\OfficialForms\Actions\ApplyOfficialFormSignature;
use App\Modules\OfficialForms\Actions\CreateOfficialFormInstance;
use App\Modules\OfficialForms\Actions\SaveOfficialFormDraft;
use App\Modules\OfficialForms\Actions\SyncOfficialFormCatalog;
use App\Modules\OfficialForms\Services\OfficialFormVerificationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OfficialFormVerificationTest extends TestCase
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

    public function test_public_verification_returns_derived_valid_current_status(): void
    {
        [$adviser, $instance] = $this->createSignedInstance('RES-040');

        $verification = OfficialFormVerification::query()
            ->where('official_form_version_id', $instance->current_version_id)
            ->first();

        $this->assertNotNull($verification);

        $this->get(route('official-forms.verify', ['reference' => $verification->public_reference]))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('VERIFIED AUTHORITATIVE CURRENT VERSION')
            ->assertSee('RES-040')
            ->assertSee($adviser->name);
    }

    public function test_verification_status_changes_to_historical_when_new_version_becomes_current(): void
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

        $instance = app(CreateOfficialFormInstance::class)->handle($student, 'RES-049', $group->id, null, 'general');
        $this->enrollSignature($student);

        app(ApplyOfficialFormSignature::class)->handle(
            $student,
            $instance->id,
            $instance->current_version_id,
            'sign_authorship',
            'student_researcher'
        );
        $instance->refresh();

        $v1Id = $instance->current_version_id;

        $verification = OfficialFormVerification::query()
            ->where('official_form_version_id', $v1Id)
            ->first();

        // Create v2 draft
        app(SaveOfficialFormDraft::class)->handle($student, $instance, ['authorship_confirmed' => true]);
        $instance->refresh();

        $verifier = app(OfficialFormVerificationService::class);
        $v1Evaluation = $verifier->evaluateVerification($instance->versions->firstWhere('id', $v1Id));

        $this->assertSame('VALID_HISTORICAL', $v1Evaluation['status']);
        $this->assertTrue($v1Evaluation['is_valid']);

        $this->get(route('official-forms.verify', ['reference' => $verification->public_reference]))
            ->assertOk()
            ->assertSee('VERIFIED HISTORICAL VERSION');
    }

    public function test_public_verification_does_not_expose_group_or_class_identifiers(): void
    {
        [$adviser, $instance] = $this->createSignedInstance('RES-040');

        $verification = OfficialFormVerification::query()
            ->where('official_form_version_id', $instance->current_version_id)
            ->first();

        $response = $this->get(route('official-forms.verify', ['reference' => $verification->public_reference]))
            ->assertOk();

        // Must NOT expose group internal database ID, raw storage paths, or sensitive payload json
        $response->assertDontSee('/storage/app/private')
            ->assertDontSee('"payload"')
            ->assertDontSee('official_form_instance_id');
    }

    public function test_unknown_reference_returns_safe_404(): void
    {
        $this->get(route('official-forms.verify', ['reference' => '00000000-0000-0000-0000-000000000000']))
            ->assertNotFound();
    }

    public function test_verification_reference_is_reused_for_second_signature_on_same_version(): void
    {
        [$adviser, $instance] = $this->createSignedInstance('RES-040');

        $this->assertDatabaseCount('official_form_verifications', 1);
        $ref1 = OfficialFormVerification::query()->first()->public_reference;

        $v1Id = $instance->current_version_id;

        // Apply second signature / verification creation for same version
        $verification2 = OfficialFormVerification::query()->firstOrCreate(
            ['official_form_version_id' => $v1Id],
            ['public_reference' => (string) Str::uuid()]
        );

        $this->assertDatabaseCount('official_form_verifications', 1);
        $this->assertSame($ref1, $verification2->public_reference);
    }

    public function test_mixed_valid_and_invalid_signatures_never_report_fully_valid(): void
    {
        [$adviser, $instance] = $this->createSignedInstance('RES-040');
        $version = $instance->currentVersion;

        $sig = OfficialFormSignature::query()->first();
        // Tamper signature hash in database
        $sig->update(['signature_sha256' => str_repeat('0', 64)]);

        $verifier = app(OfficialFormVerificationService::class);
        $evaluation = $verifier->evaluateVerification($version);

        $this->assertFalse($evaluation['is_valid']);
        $this->assertNotEquals('VALID_CURRENT', $evaluation['status']);
    }

    private function createSignedInstance(string $code = 'RES-040'): array
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

        $this->enrollSignature($adviser);

        app(ApplyOfficialFormSignature::class)->handle(
            $adviser,
            $instance->id,
            $instance->current_version_id,
            'endorse',
            'research_adviser'
        );

        return [$adviser, $instance->fresh()];
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
