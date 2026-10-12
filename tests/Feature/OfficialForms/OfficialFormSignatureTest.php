<?php

namespace Tests\Feature\OfficialForms;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\OfficialFormVersion;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Models\UserSignature;
use App\Modules\Classes\Actions\RequestAdviserForResearchClassGroup;
use App\Modules\Dashboard\Services\UserLiveStateService;
use App\Modules\OfficialForms\Actions\ApplyOfficialFormSignature;
use App\Modules\OfficialForms\Actions\AssignResearchClassFormActor;
use App\Modules\OfficialForms\Actions\CompleteAuthorshipCertificate;
use App\Modules\OfficialForms\Actions\CreateOfficialFormInstance;
use App\Modules\OfficialForms\Actions\SaveOfficialFormDraft;
use App\Modules\OfficialForms\Actions\SyncOfficialFormCatalog;
use App\Modules\OfficialForms\Services\GetPendingAcademicActionsForUser;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;
use App\Modules\OfficialForms\Services\OfficialFormSignatureHasher;
use Carbon\CarbonImmutable;
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
            'endorse'
        );

        $this->assertDatabaseHas('official_form_signatures', [
            'id' => $sigRecord->id,
            'official_form_instance_id' => $instance->id,
            'official_form_version_id' => $instance->current_version_id,
            'signer_user_id' => $adviser->id,
            'actor_type' => 'adviser',
            'academic_action' => 'endorse',
        ]);

        $this->assertDatabaseHas('official_form_verifications', [
            'official_form_version_id' => $instance->current_version_id,
        ]);

        Storage::disk('local')->assertExists($sigRecord->signature_storage_path);
    }

    public function test_signing_automatic_res027_invitation_activates_the_invited_adviser(): void
    {
        $facilitator = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $facilitator->assignRole('research-facilitator');

        $coordinator = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $coordinator->assignRole('program-coordinator');

        $adviser = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $adviser->assignRole('thesis-adviser');

        $class = ResearchClass::query()->forceCreate([
            'facilitator_id' => $facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone Class',
            'join_code_hash' => hash('sha256', 'RES027'),
            'join_code_encrypted' => 'RES027',
            'is_active' => true,
        ]);
        $group = ResearchClassGroup::query()->create([
            'research_class_id' => $class->id,
            'name' => 'RES-027 Group',
            'created_by' => $facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'status' => 'active',
        ]);

        $request = app(RequestAdviserForResearchClassGroup::class)
            ->handle($coordinator, $class, $group, $adviser);
        $invitation = $request->officialFormInvitation()->sole();

        $this->assertNull($group->fresh()->adviser_id);
        $this->assertSame('pending', $request->fresh()->status);

        $this->actingAs($adviser)
            ->get(route('official-forms.workspace.show', $invitation))
            ->assertOk()
            ->assertSee('RES-027');

        $this->enrollSignature($adviser);
        app(ApplyOfficialFormSignature::class)->handle(
            $adviser,
            $invitation->id,
            $invitation->current_version_id,
            'respond',
        );

        $this->assertSame($adviser->id, $group->fresh()->adviser_id);
        $this->assertSame('accepted', $request->fresh()->status);
        $this->assertDatabaseHas('research_class_group_adviser_histories', [
            'research_class_group_id' => $group->id,
            'adviser_id' => $adviser->id,
            'assigned_by' => $coordinator->id,
        ]);
        $this->assertDatabaseHas('official_form_signatures', [
            'official_form_instance_id' => $invitation->id,
            'signer_user_id' => $adviser->id,
            'academic_action' => 'respond',
        ]);
    }

    public function test_res033_is_endorsed_and_received_with_one_signature_action_when_roles_share_a_user(): void
    {
        $adviserCoordinator = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $adviserCoordinator->assignRole('thesis-adviser', 'program-coordinator');

        $group = $this->createGroup(adviser: $adviserCoordinator);
        $instance = app(CreateOfficialFormInstance::class)->handle(
            $adviserCoordinator,
            'RES-033',
            $group->id,
            null,
            'proposal_defense',
            payload: ['defense_type' => 'proposal_defense'],
        );
        $this->enrollSignature($adviserCoordinator);
        $originalPayload = $instance->currentVersion->payload;
        $unsignedHtml = view('pages.adviser.forms.res-033', ['officialFormInstance' => $instance, 'payload' => ['date_received' => '2000-01-01']])->render();
        $this->assertStringNotContainsString('2000-01-01', $unsignedHtml);
        $this->assertStringNotContainsString('name="payload[date_received]"', $unsignedHtml);
        $this->travelTo(CarbonImmutable::parse('2026-10-12 23:30:00', 'UTC'));

        app(ApplyOfficialFormSignature::class)->handle(
            $adviserCoordinator,
            $instance->id,
            $instance->current_version_id,
            'endorse',
        );

        $this->assertSame('received', $instance->fresh()->status);
        $this->assertDatabaseHas('official_form_signatures', [
            'official_form_instance_id' => $instance->id,
            'signer_user_id' => $adviserCoordinator->id,
            'actor_type' => 'adviser',
            'academic_action' => 'endorse',
        ]);
        $this->assertDatabaseHas('official_form_signatures', [
            'official_form_instance_id' => $instance->id,
            'signer_user_id' => $adviserCoordinator->id,
            'actor_type' => 'program_head',
            'academic_action' => 'receive',
        ]);
        $this->assertDatabaseCount('official_form_signatures', 2);
        $receivedInstance = $instance->fresh(['currentVersion.signatures']);
        $receivedHtml = view('pages.adviser.forms.res-033', ['officialFormInstance' => $receivedInstance, 'payload' => $originalPayload])->render();
        $this->assertStringContainsString('value="2026-10-13"', $receivedHtml);
        $this->assertSame($originalPayload, $receivedInstance->currentVersion->payload);
        $this->actingAs($adviserCoordinator)->get(route('official-forms.print', $receivedInstance))
            ->assertOk()->assertSee('value="2026-10-13"', false);
    }

    public function test_res041_displays_coordinator_receipt_without_claiming_an_instructor_endorsement(): void
    {
        $group = $this->createGroup();
        $instructor = $group->researchClass->facilitator;
        $instructor->update(['status' => AccountStatus::Active, 'approved_at' => now(), 'email_verified_at' => now()]);
        $instructor->assignRole('research-facilitator', 'program-coordinator');
        $instructor->givePermissionTo('forms.res-041.fill', 'forms.res-041.receive');
        app(AssignResearchClassFormActor::class)
            ->handle($instructor, $group->researchClass, $instructor, 'research_instructor');
        $this->enrollSignature($instructor);
        $instance = app(CreateOfficialFormInstance::class)->handle($instructor, 'RES-041', classId: $group->research_class_id);

        app(ApplyOfficialFormSignature::class)->handle($instructor, $instance->id, $instance->current_version_id, 'receive');
        $instance->refresh()->load('currentVersion.signatures.verification');
        $html = view('pages.facilitator.forms.res-041', ['officialFormInstance' => $instance, 'payload' => $instance->currentVersion->payload])->render();
        $this->assertStringContainsString('Received by Program Coordinator', $html);
        $this->assertSame(1, substr_count($html, 'Signed & Digital Attestation Verified'));
        $this->assertStringNotContainsString('Digital signature pending approval', $html);
        $this->assertSame(1, substr_count($html, 'official-signature-field text-center'));
        $this->actingAs($instructor)->get(route('official-forms.print', $instance))
            ->assertOk()->assertSee('Signed & Digital Attestation Verified', false)->assertSee('Received by Program Coordinator');
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
            'endorse'
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
            'endorse'
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
            'sign_authorship'
        );

        $v1Id = $instance->current_version_id;

        // A completed certificate must be returned for correction before editing.
        $instance->refresh()->update(['status' => 'returned_for_correction']);
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
        $this->assertFalse(app(CompleteAuthorshipCertificate::class)->handle($instance));
        $this->assertSame('draft', $instance->fresh()->status);
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
            'endorse'
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
            'endorse'
        );
    }

    public function test_res049_completes_when_the_only_required_author_signs(): void
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

        $sigRecord = app(ApplyOfficialFormSignature::class)->handle(
            $student,
            $instance->id,
            $instance->current_version_id,
            'sign_authorship'
        );

        $instance->refresh();

        $this->assertSame('completed', $instance->status);
        $this->assertDatabaseHas('official_form_signatures', [
            'id' => $sigRecord->id,
            'official_form_version_id' => $instance->current_version_id,
            'signer_user_id' => $student->id,
            'academic_action' => 'sign_authorship',
            'actor_type' => 'student_researcher',
        ]);
    }

    public function test_res049_requires_all_coauthors_on_the_current_version_and_completion_is_idempotent(): void
    {
        $students = collect([1, 2])->map(function () {
            $student = User::factory()->create([
                'user_type' => UserType::Student, 'status' => AccountStatus::Active,
                'approved_at' => now(), 'email_verified_at' => now(),
            ]);
            $student->assignRole('student');
            $student->givePermissionTo('forms.res-049.sign');
            $this->enrollSignature($student);

            return $student;
        });
        $group = $this->createGroup(leader: $students[0]);
        $enrollment = ResearchClassEnrollment::create([
            'research_class_id' => $group->research_class_id, 'student_id' => $students[1]->id,
            'status' => 'active', 'requested_at' => now(), 'joined_at' => now(),
        ]);
        $group->members()->create([
            'student_id' => $students[1]->id, 'research_class_id' => $group->research_class_id,
            'research_class_enrollment_id' => $enrollment->id, 'assigned_by' => $group->created_by,
        ]);
        $instance = app(CreateOfficialFormInstance::class)->handle($students[0], 'RES-049', $group->id);
        $instance->update(['status' => 'submitted']);
        $sign = app(ApplyOfficialFormSignature::class);
        $sign->handle($students[0], $instance->id, $instance->current_version_id, 'sign_authorship');
        $this->assertSame('submitted', $instance->fresh()->status);
        $sign->handle($students[1], $instance->id, $instance->current_version_id, 'sign_authorship');
        $this->assertSame('completed', $instance->fresh()->status);
        $completion = app(CompleteAuthorshipCertificate::class);
        $this->assertFalse($completion->handle($instance));
        $this->assertDatabaseCount('official_form_signatures', 2);
        $this->assertSame(1, AuditLog::where('event', 'official_form.authorship_completed')->count());

        $oldVersion = $instance->currentVersion;
        $oldVersion->update(['is_current' => false]);
        $newVersion = OfficialFormVersion::create([
            'official_form_instance_id' => $instance->id, 'version_number' => 2,
            'payload' => $oldVersion->payload, 'created_by' => $students[0]->id,
            'supersedes_version_id' => $oldVersion->id, 'is_current' => true,
        ]);
        $instance->refresh()->update(['current_version_id' => $newVersion->id, 'status' => 'submitted']);
        $this->assertFalse($completion->handle($instance));
        $this->assertSame('submitted', $instance->fresh()->status);
        $sign->handle($students[0], $instance->id, $newVersion->id, 'sign_authorship');
        $this->assertSame('submitted', $instance->fresh()->status);
        $sign->handle($students[1], $instance->id, $newVersion->id, 'sign_authorship');
        $this->assertSame('completed', $instance->fresh()->status);
    }

    public function test_actor_type_is_derived_server_side_and_browser_input_is_ignored(): void
    {
        [$adviser, $instance] = $this->createFormInstanceForAdviser('RES-040');
        $this->enrollSignature($adviser);

        // 1. Attempting to post actor_type in workspace sign route is rejected by rejectUnexpectedInput
        $this->actingAs($adviser)
            ->post(route('official-forms.workspace.sign-action', ['instance' => $instance->id, 'action' => 'endorse']), [
                'expected_version_id' => $instance->current_version_id,
                'actor_type' => 'dean', // Forgery attempt in payload
            ])
            ->assertSessionHasErrors();

        // 2. Legitimate request with ONLY expected_version_id succeeds and derives actor_type server-side
        $this->actingAs($adviser)
            ->post(route('official-forms.workspace.sign-action', ['instance' => $instance->id, 'action' => 'endorse']), [
                'expected_version_id' => $instance->current_version_id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('official_form_signatures', [
            'official_form_instance_id' => $instance->id,
            'signer_user_id' => $adviser->id,
            'actor_type' => 'adviser', // Derived server-side!
        ]);
    }

    public function test_res049_student_researcher_does_not_require_official_form_actor_assignment(): void
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

        $instance = app(CreateOfficialFormInstance::class)->handle(
            $student,
            'RES-049',
            $group->id,
            null,
            'general'
        );

        $this->enrollSignature($student);

        // Prove no OfficialFormActorAssignment exists
        $this->assertDatabaseMissing('official_form_actor_assignments', [
            'official_form_instance_id' => $instance->id,
            'user_id' => $student->id,
        ]);

        $sigRecord = app(ApplyOfficialFormSignature::class)->handle(
            $student,
            $instance->id,
            $instance->current_version_id,
            'sign_authorship'
        );

        $this->assertSame('student_researcher', $sigRecord->actor_type);
    }

    public function test_res049_student_can_sign_through_workspace_without_submitting_system_managed_actor_type(): void
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
        $instance = app(CreateOfficialFormInstance::class)->handle(
            $student,
            'RES-049',
            $group->id,
            null,
            'general'
        );
        $this->enrollSignature($student);

        $this->actingAs($student)
            ->post(route('official-forms.workspace.sign-action', [$instance, 'sign_authorship']), [
                'expected_version_id' => $instance->current_version_id,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('official_form_success', 'Digital signature attestation recorded.');

        $this->assertDatabaseHas('official_form_signatures', [
            'official_form_instance_id' => $instance->id,
            'official_form_version_id' => $instance->current_version_id,
            'signer_user_id' => $student->id,
            'academic_action' => 'sign_authorship',
            'actor_type' => 'student_researcher',
        ]);

        $this->actingAs($student)
            ->get(route('official-forms.workspace.show', $instance))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee('Signed & Digital Attestation Verified', false)
            ->assertSee('data-digital-signature-status="verified"', false)
            ->assertSee('Authorship Signed by You')
            ->assertDontSee('Sign Authorship Attestation');
    }

    public function test_non_member_cannot_sign_res049(): void
    {
        $student1 = User::factory()->create([
            'user_type' => UserType::Student,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $student1->assignRole('student');
        Permission::findOrCreate('forms.res-049.sign');
        $student1->givePermissionTo('forms.res-049.sign');

        $student2 = User::factory()->create([
            'user_type' => UserType::Student,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $student2->assignRole('student');
        $student2->givePermissionTo('forms.res-049.sign');

        $group = $this->createGroup(leader: $student1);

        $instance = app(CreateOfficialFormInstance::class)->handle(
            $student1,
            'RES-049',
            $group->id,
            null,
            'general'
        );

        $this->enrollSignature($student2);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You are not authorized to sign RES-049 authorship attestation.');

        app(ApplyOfficialFormSignature::class)->handle(
            $student2,
            $instance->id,
            $instance->current_version_id,
            'sign_authorship'
        );
    }

    public function test_unknown_academic_action_fails_closed(): void
    {
        [$adviser, $instance] = $this->createFormInstanceForAdviser('RES-040');
        $this->enrollSignature($adviser);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Action 'invalid_action' is not supported");

        app(ApplyOfficialFormSignature::class)->handle(
            $adviser,
            $instance->id,
            $instance->current_version_id,
            'invalid_action'
        );
    }

    public function test_idor_on_applied_signature_image_route_is_blocked(): void
    {
        [$adviser1, $instance1] = $this->createFormInstanceForAdviser('RES-040');
        $this->enrollSignature($adviser1);
        $sig1 = app(ApplyOfficialFormSignature::class)->handle(
            $adviser1,
            $instance1->id,
            $instance1->current_version_id,
            'endorse'
        );

        $unauthorizedUser = User::factory()->create(['user_type' => UserType::Student]);

        $this->actingAs($unauthorizedUser)
            ->get(route('official-forms.workspace.signature-image', ['signature' => $sig1->id]))
            ->assertForbidden();
    }

    public function test_signature_hasher_includes_source_snapshot(): void
    {
        [$adviser, $instance] = $this->createFormInstanceForAdviser('RES-040');
        /** @var OfficialFormVersion $version */
        $version = $instance->currentVersion;

        $hasher = app(OfficialFormSignatureHasher::class);

        // 1. Version without source_snapshot
        $version->source_snapshot = null;
        $hash1 = $hasher->hashVersion($version);

        // 2. Version with source_snapshot
        $version->source_snapshot = [
            'schedule_id' => 10,
            'defense_type' => 'proposal_defense',
            'room' => 'RM-101',
        ];
        $hash2 = $hasher->hashVersion($version);

        $this->assertNotEquals($hash1, $hash2);

        // 3. Key order variation produces identical hash due to recursive key sorting
        $version->source_snapshot = [
            'room' => 'RM-101',
            'schedule_id' => 10,
            'defense_type' => 'proposal_defense',
        ];
        $hash3 = $hasher->hashVersion($version);

        $this->assertSame($hash2, $hash3);
    }

    public function test_signature_hasher_preserves_exact_phase20_hash_for_null_source_snapshot(): void
    {
        [$adviser, $instance] = $this->createFormInstanceForAdviser('RES-040');
        /** @var OfficialFormVersion $version */
        $version = $instance->currentVersion;
        $version->source_snapshot = null;

        $hasher = app(OfficialFormSignatureHasher::class);
        $actualHash = $hasher->hashVersion($version);

        // Construct expected Phase 20 canonical JSON structure (without source_snapshot key)
        $expectedPhase20Structure = [
            'form_code' => 'RES-040',
            'official_form_instance_id' => (int) $instance->id,
            'official_form_version_id' => (int) $version->id,
            'version_number' => (int) $version->version_number,
            'payload' => [],
        ];
        $expectedCanonicalJson = json_encode($expectedPhase20Structure, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $expectedHash = hash('sha256', (string) $expectedCanonicalJson);
        $this->assertSame($expectedHash, $actualHash);
    }

    public function test_invitation_form_res028_requires_conforme_and_coordinator_before_dean_approval(): void
    {
        $panelist = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $panelist->assignRole('panelist');
        Permission::findOrCreate('forms.res-028.respond');
        Permission::findOrCreate('forms.res-028.view');
        $panelist->givePermissionTo('forms.res-028.respond', 'forms.res-028.view');

        $group = $this->createGroup();
        $class = $group->researchClass;
        $coordinator = $class->facilitator;
        $coordinator->assignRole('program-coordinator');
        Permission::findOrCreate('classes.assign-advisers');
        Permission::findOrCreate('forms.res-028.view');
        $coordinator->givePermissionTo('classes.assign-advisers', 'forms.res-028.view');

        $dean = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $dean->assignRole('college-dean');
        Permission::findOrCreate('dashboards.dean.view');
        Permission::findOrCreate('forms.res-028.view');
        $dean->givePermissionTo('dashboards.dean.view', 'forms.res-028.view');

        $instance = app(CreateOfficialFormInstance::class)->handle(
            $coordinator,
            'RES-028',
            $group->id,
            null,
            'panelist_invitation'
        );

        $instance->actorAssignments()->create([
            'user_id' => $panelist->id,
            'actor_type' => 'panelist',
            'status' => 'active',
            'assigned_by' => $coordinator->id,
            'assigned_at' => now(),
        ]);

        $this->enrollSignature($panelist);
        $this->enrollSignature($coordinator);
        $this->enrollSignature($dean);

        $instance->refresh();
        $auth = app(OfficialFormAuthorization::class);

        // Another panelist has their own invitation and a class-level assignment,
        // neither of which permits access to this panelist's individual invitation.
        $otherPanelist = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(), 'email_verified_at' => now(),
        ]);
        $otherPanelist->assignRole('panelist');
        $otherPanelist->givePermissionTo('forms.res-028.respond', 'forms.res-028.view');
        $otherInvitation = app(CreateOfficialFormInstance::class)->handle(
            $coordinator, 'RES-028', $group->id, null, 'another_panelist_invitation'
        );
        $otherInvitation->actorAssignments()->create([
            'user_id' => $otherPanelist->id, 'actor_type' => 'panelist',
            'status' => 'active', 'assigned_by' => $coordinator->id, 'assigned_at' => now(),
        ]);
        $class->officialFormActorAssignments()->create([
            'user_id' => $otherPanelist->id, 'actor_type' => 'panelist',
            'status' => 'active', 'assigned_by' => $coordinator->id, 'assigned_at' => now(),
        ]);
        $this->assertFalse($auth->canPerformAction($otherPanelist, $instance, 'respond'));
        $this->assertFalse($auth->canPerformAction($otherPanelist, $instance, 'conforme'));
        $this->assertFalse($auth->canSubmit($otherPanelist, $instance));
        $this->actingAs($otherPanelist)->get(route('official-forms.workspace.show', $instance))
            ->assertForbidden();
        $this->actingAs($otherPanelist)->post(route('official-forms.workspace.sign-action', [$instance, 'respond']), [
            'expected_version_id' => $instance->current_version_id,
        ])->assertSessionHasErrors('official_form');
        $this->actingAs($otherPanelist)->post(route('official-forms.workspace.panel-invitation.decline', $instance))
            ->assertForbidden();
        $this->assertDatabaseMissing('official_form_signatures', [
            'official_form_instance_id' => $instance->id, 'signer_user_id' => $otherPanelist->id,
        ]);
        $this->assertTrue($auth->canPerformAction($panelist, $instance, 'respond'), 'Exact invitee must be allowed to respond.');
        $this->actingAs($panelist)->get(route('official-forms.workspace.show', $instance))
            ->assertOk()->assertSee('Sign & Accept Invitation');
        $this->actingAs($coordinator)->get(route('official-forms.workspace.show', $instance))->assertOk();
        $this->assertFalse(app(GetPendingAcademicActionsForUser::class)
            ->execute($otherPanelist)->contains(fn ($action) => $action['instance_id'] === $instance->id));

        // Step 1: Panelist signs Conforme (respond)
        $actionHandler = app(ApplyOfficialFormSignature::class);
        $actionHandler->handle(
            $panelist,
            $instance->id,
            $instance->current_version_id,
            'respond'
        );

        $instance->refresh();
        // Crucial requirement: Form must NOT be marked approved after only the panelist signs
        $this->assertSame('conformed', $instance->status);
        $this->assertNotSame('approved', $instance->status);

        // Step 2: Dean cannot approve yet because Program Coordinator hasn't signed
        $this->assertFalse($auth->canPerformAction($dean, $instance, 'approve'));

        // Step 3: Program Coordinator endorses
        $actionHandler->handle(
            $coordinator,
            $instance->id,
            $instance->current_version_id,
            'endorse'
        );

        $instance->refresh();
        $this->assertSame('conformed', $instance->status);

        // Step 4: Now both Invitee and Coordinator have signed, Dean is authorized to approve
        $this->assertTrue($auth->canPerformAction($dean, $instance, 'approve'), 'Dean must be allowed after both required signatures.');

        // Step 5: Dean notes and approves
        $actionHandler->handle(
            $dean,
            $instance->id,
            $instance->current_version_id,
            'approve'
        );

        $instance->refresh();
        $this->assertSame('approved', $instance->status);

        // Verify all 3 signatures are registered
        $this->assertCount(3, $instance->currentVersion->signatures);
    }

    public function test_unexpected_signature_failure_is_not_reported_as_a_success(): void
    {
        [$adviser, $instance] = $this->createFormInstanceForAdviser();
        $this->mock(ApplyOfficialFormSignature::class, function ($mock): void {
            $mock->shouldReceive('handle')->once()->andThrow(new \RuntimeException('Signature storage unavailable'));
        });

        $this->actingAs($adviser)
            ->post(route('official-forms.workspace.sign-action', [$instance, 'endorse']), [
                'expected_version_id' => $instance->current_version_id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('official_form')
            ->assertSessionMissing('official_form_success');

        $this->assertDatabaseCount('official_form_signatures', 0);
    }

    public function test_broadcast_failure_does_not_hide_a_successfully_saved_signature(): void
    {
        [$adviser, $instance] = $this->createFormInstanceForAdviser();
        $this->enrollSignature($adviser);
        $this->mock(UserLiveStateService::class, function ($mock): void {
            $mock->shouldReceive('broadcast')->once()->andThrow(new \RuntimeException('Broadcast unavailable'));
        });

        $this->actingAs($adviser)
            ->post(route('official-forms.workspace.sign-action', [$instance, 'endorse']), [
                'expected_version_id' => $instance->current_version_id,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('official_form_success', 'Digital signature attestation recorded.');

        $this->assertDatabaseHas('official_form_signatures', [
            'official_form_instance_id' => $instance->id,
            'signer_user_id' => $adviser->id,
            'academic_action' => 'endorse',
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
        $file = UploadedFile::fake()->image('signature.png', 400, 150);

        $this->actingAs($user)->putJson(route('signature.store'), ['signature' => $file])->assertOk();

        return UserSignature::query()->where('user_id', $user->id)->sole();
    }
}
