<?php

namespace Tests\Feature\Documents;

use App\Models\Document;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Modules\Classes\Actions\CreateResearchClass;
use App\Modules\Documents\Actions\RecordDocumentUploadAttempt;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use ZipArchive;

class DocumentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_submit_a_document(): void
    {
        $this->post(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'proposal_defense',
            'document_stage' => 'proposal_defense',
            'document_stage' => 'proposal_defense',
            'document_stage' => 'proposal_defense',
            'document' => $this->pdf(),
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_authorized_student_group_leader_can_submit_a_valid_pdf(): void
    {
        ['user' => $user, 'group' => $group] = $this->studentGroupLeader();

        $response = $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'proposal_defense',
            'document' => $this->pdf('Research Paper.pdf'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Document submitted successfully.')
            ->assertJsonPath('document.original_filename', 'Research Paper.pdf')
            ->assertJsonPath('document.file_type', 'pdf')
            ->assertJsonPath('document.version_number', 1)
            ->assertJsonPath('document.is_current', true)
            ->assertJsonPath('document.status', 'pending')
            ->assertJsonMissingPath('document.storage_path')
            ->assertJsonMissingPath('document.stored_filename');

        $document = Document::query()->sole();

        $this->assertSame($group->id, $document->research_class_group_id);
        $this->assertSame($user->id, $document->user_id);
        $this->assertTrue($document->is_current);
        $this->assertSame(1, $document->version_number);

        Storage::disk('local')->assertExists($document->storage_path);
        $this->assertNotSame('Research Paper.pdf', $document->stored_filename);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f-]{36}\.pdf$/',
            $document->stored_filename,
        );
        $this->assertDatabaseHas('document_upload_audits', [
            'document_id' => $document->getKey(),
            'user_id' => $user->getKey(),
            'research_class_group_id' => $group->getKey(),
            'upload_status' => 'success',
            'failure_reason' => null,
        ]);
    }

    public function test_authorized_student_group_leader_can_submit_a_valid_docx(): void
    {
        ['user' => $user, 'group' => $group] = $this->studentGroupLeader();

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'proposal_defense',
            'document' => $this->docx(),
        ])->assertCreated()
            ->assertJsonPath('document.file_type', 'docx')
            ->assertJsonPath('document.version_number', 1)
            ->assertJsonPath('document.is_current', true);

        $this->assertDatabaseHas('documents', [
            'user_id' => $user->getKey(),
            'research_class_group_id' => $group->getKey(),
            'file_type' => 'docx',
            'version_number' => 1,
            'is_current' => true,
            'status' => 'pending',
        ]);
    }

    public function test_non_leader_group_member_cannot_submit_a_document(): void
    {
        ['user' => $leader, 'group' => $group, 'class' => $class, 'facilitator' => $facilitator] = $this->studentGroupLeader();

        $nonLeader = $this->student();
        $enrollment = ResearchClassEnrollment::query()->create([
            'research_class_id' => $class->id,
            'student_id' => $nonLeader->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        ResearchClassGroupMember::query()->create([
            'research_class_group_id' => $group->id,
            'research_class_id' => $class->id,
            'research_class_enrollment_id' => $enrollment->id,
            'student_id' => $nonLeader->id,
            'assigned_by' => $facilitator->id,
        ]);

        $this->actingAs($nonLeader)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'proposal_defense',
            'document' => $this->pdf(),
        ])->assertForbidden()
            ->assertJsonPath('message', 'Only your assigned Group Leader can submit research documents.');

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseHas('document_upload_audits', [
            'user_id' => $nonLeader->getKey(),
            'research_class_group_id' => $group->getKey(),
            'upload_status' => 'failed',
            'failure_reason' => 'Only your assigned Group Leader can submit research documents.',
        ]);
    }

    public function test_student_dashboard_has_no_upload_shortcut_outside_research_proposal(): void
    {
        ['user' => $leader, 'group' => $group, 'class' => $class, 'facilitator' => $facilitator] = $this->studentGroupLeader();

        $nonLeader = $this->student();
        $enrollment = ResearchClassEnrollment::query()->create([
            'research_class_id' => $class->id,
            'student_id' => $nonLeader->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        ResearchClassGroupMember::query()->create([
            'research_class_group_id' => $group->id,
            'research_class_id' => $class->id,
            'research_class_enrollment_id' => $enrollment->id,
            'student_id' => $nonLeader->id,
            'assigned_by' => $facilitator->id,
        ]);

        $this->actingAs($nonLeader)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee('Submit Document')
            ->assertDontSee('student-document-upload-input')
            ->assertDontSee('data-document-upload-trigger')
            ->assertSee('Group Leader Only Action');
    }

    public function test_browser_upload_returns_group_leader_to_research_proposal(): void
    {
        ['user' => $leader] = $this->studentGroupLeader();

        $this->actingAs($leader)
            ->post(route('student.documents.store'), [
                'submission_token' => (string) Str::uuid(),
                'document_stage' => 'proposal_defense',
                'document' => $this->pdf('Research Proposal.pdf'),
            ])
            ->assertRedirect(route('student.dashboard', ['tab' => 'proposal']))
            ->assertSessionHas('document_success');
    }

    public function test_student_without_active_group_cannot_submit_a_document(): void
    {
        $user = $this->student();

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'proposal_defense',
            'document' => $this->pdf(),
        ])->assertForbidden()
            ->assertJsonPath('message', 'You do not belong to an active research group.');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_exact_duplicate_file_submission_is_rejected(): void
    {
        ['user' => $leader, 'group' => $group] = $this->studentGroupLeader();
        $pdfFile = $this->pdf('paper.pdf');

        $this->actingAs($leader)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'proposal_defense',
            'document' => $pdfFile,
        ])->assertCreated();

        $this->actingAs($leader)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'proposal_defense',
            'document' => $this->pdf('paper_copy.pdf'),
        ])->assertStatus(409)
            ->assertJsonPath('message', 'This exact file has already been submitted for your research group.');

        $this->assertDatabaseCount('documents', 1);
    }

    public function test_submitting_new_version_marks_previous_version_as_void_and_new_as_current(): void
    {
        ['user' => $leader, 'group' => $group] = $this->studentGroupLeader();

        $firstDoc = $this->pdf('Draft_v1.pdf');
        $this->actingAs($leader)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'proposal_defense',
            'document' => $firstDoc,
        ])->assertCreated()
            ->assertJsonPath('document.version_number', 1)
            ->assertJsonPath('document.is_current', true);

        $secondDoc = UploadedFile::fake()->createWithContent(
            'Draft_v2.pdf',
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Title (Version 2) >>\nendobj\n%%EOF\n",
        );

        $this->actingAs($leader)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'proposal_defense',
            'document' => $secondDoc,
        ])->assertCreated()
            ->assertJsonPath('document.version_number', 2)
            ->assertJsonPath('document.is_current', true);

        $this->assertDatabaseCount('documents', 2);

        $doc1 = Document::query()->where('version_number', 1)->sole();
        $doc2 = Document::query()->where('version_number', 2)->sole();

        $this->assertFalse($doc1->is_current);
        $this->assertTrue($doc2->is_current);
        $this->assertSame($group->id, $doc1->research_class_group_id);
        $this->assertSame($group->id, $doc2->research_class_group_id);
        Storage::disk('local')->assertExists($doc1->storage_path);
        Storage::disk('local')->assertExists($doc2->storage_path);

        $finalDefense = UploadedFile::fake()->createWithContent(
            'Final_Defense_v1.pdf',
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Title (Final Defense) >>\nendobj\n%%EOF\n",
        );

        $this->actingAs($leader)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'final_defense',
            'document' => $finalDefense,
        ])->assertCreated()
            ->assertJsonPath('document.document_stage', 'final_defense')
            ->assertJsonPath('document.version_number', 1);

        $this->assertDatabaseHas('documents', [
            'id' => $doc2->getKey(),
            'document_stage' => 'proposal_defense',
            'version_number' => 2,
            'is_current' => true,
        ]);
        $this->assertDatabaseHas('documents', [
            'document_stage' => 'final_defense',
            'version_number' => 1,
            'is_current' => true,
        ]);
    }

    public function test_document_submission_rejects_a_missing_or_invalid_stage(): void
    {
        ['user' => $leader] = $this->studentGroupLeader();

        $this->actingAs($leader)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document' => $this->pdf(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('document_stage');

        $this->actingAs($leader)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'chapter_three',
            'document' => $this->pdf(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('document_stage');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_facilitator_can_assign_group_leader(): void
    {
        ['facilitator' => $facilitator, 'class' => $class, 'group' => $group, 'user' => $leader] = $this->studentGroupLeader();

        $newLeader = $this->student();
        $enrollment = ResearchClassEnrollment::query()->create([
            'research_class_id' => $class->id,
            'student_id' => $newLeader->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        ResearchClassGroupMember::query()->create([
            'research_class_group_id' => $group->id,
            'research_class_id' => $class->id,
            'research_class_enrollment_id' => $enrollment->id,
            'student_id' => $newLeader->id,
            'assigned_by' => $facilitator->id,
        ]);

        $this->actingAs($facilitator)
            ->putJson(route('facilitator.classes.groups.leader.assign', [$class, $group]), [
                'student_id' => $newLeader->id,
            ])->assertOk()
            ->assertJsonPath('message', 'Group Leader assigned successfully.')
            ->assertJsonPath('group.leader_student_id', $newLeader->id);

        $this->assertSame($newLeader->id, $group->fresh()->leader_student_id);

        $this->actingAs($facilitator)
            ->get(route('facilitator.classes.show', $class))
            ->assertOk()
            ->assertSee($newLeader->name)
            ->assertSee('Group Leader')
            ->assertSee('Change Group Leader...')
            ->assertSee('Current Leader');
    }

    public function test_moving_group_leader_clears_old_group_leader(): void
    {
        ['facilitator' => $facilitator, 'class' => $class, 'group' => $group1, 'user' => $leader, 'enrollment' => $enrollment] = $this->studentGroupLeader();

        $group2 = ResearchClassGroup::query()->create([
            'research_class_id' => $class->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Group Beta',
            'created_by' => $facilitator->id,
            'status' => 'active',
        ]);

        $this->actingAs($facilitator)
            ->putJson(route('facilitator.classes.groups.students.assign', [$class, $group2, $enrollment]))
            ->assertOk();

        $this->assertNull($group1->fresh()->leader_student_id);
    }

    public function test_disbanding_group_clears_group_leader(): void
    {
        ['facilitator' => $facilitator, 'class' => $class, 'group' => $group] = $this->studentGroupLeader();

        $this->actingAs($facilitator)
            ->deleteJson(route('facilitator.classes.groups.disband', [$class, $group]))
            ->assertOk();

        $this->assertNull($group->fresh()->leader_student_id);
        $this->assertSame('disbanded', $group->fresh()->status);
    }

    public function test_executable_and_disguised_files_are_rejected_and_audited(): void
    {
        ['user' => $user] = $this->studentGroupLeader();

        foreach (['payload.exe', 'payload.php.pdf', 'payload.js.docx'] as $filename) {
            $this->actingAs($user)->postJson(route('student.documents.store'), [
                'submission_token' => (string) Str::uuid(),
                'document_stage' => 'proposal_defense',
                'document' => UploadedFile::fake()->createWithContent($filename, 'malicious'),
            ])->assertUnprocessable()
                ->assertJsonValidationErrors('document');
        }

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_upload_audits', 3);
        $this->assertDatabaseMissing('document_upload_audits', [
            'upload_status' => 'success',
        ]);
    }

    public function test_corrupted_pdf_and_empty_file_are_rejected(): void
    {
        ['user' => $user] = $this->studentGroupLeader();

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'proposal_defense',
            'document' => UploadedFile::fake()->createWithContent('corrupt.pdf', '%PDF-1.7 broken'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('document');

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'proposal_defense',
            'document' => UploadedFile::fake()->createWithContent('empty.pdf', ''),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('document');

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_upload_audits', 2);
    }

    public function test_document_larger_than_ten_megabytes_is_rejected(): void
    {
        ['user' => $user] = $this->studentGroupLeader();

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'proposal_defense',
            'document' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('document');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_missing_upload_permission_is_rejected_and_audited(): void
    {
        ['user' => $user] = $this->studentGroupLeader();

        Role::findByName('student-researcher')->syncPermissions([
            'dashboards.student.view',
            'research.view-own',
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'proposal_defense',
            'document' => $this->pdf(),
        ])->assertForbidden();

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_same_submission_token_cannot_create_duplicate_documents(): void
    {
        ['user' => $user] = $this->studentGroupLeader();
        $token = (string) Str::uuid();

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => $token,
            'document_stage' => 'proposal_defense',
            'document' => $this->pdf(),
        ])->assertCreated();

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => $token,
            'document_stage' => 'proposal_defense',
            'document' => $this->pdf(),
        ])->assertConflict();

        $this->assertDatabaseCount('documents', 1);
        $this->assertDatabaseCount('document_upload_audits', 2);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_database_failure_rolls_back_record_and_deletes_stored_file(): void
    {
        ['user' => $user] = $this->studentGroupLeader();
        $audit = Mockery::mock(RecordDocumentUploadAttempt::class);
        $audit->shouldReceive('success')
            ->once()
            ->andThrow(new RuntimeException('Simulated database failure.'));
        $audit->shouldReceive('failure')->once();
        $this->app->instance(RecordDocumentUploadAttempt::class, $audit);

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'proposal_defense',
            'document' => $this->pdf(),
        ])->assertInternalServerError()
            ->assertExactJson([
                'message' => 'The document could not be stored securely. Please try again.',
            ]);

        $this->assertDatabaseCount('documents', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_owner_and_authorized_administrator_can_access_document_but_another_student_cannot(): void
    {
        ['user' => $owner] = $this->studentGroupLeader();

        $this->actingAs($owner)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document_stage' => 'proposal_defense',
            'document' => $this->pdf(),
        ])->assertCreated();

        $document = Document::query()->sole();

        $this->actingAs($owner)
            ->get(route('documents.view', [$document, 'raw' => 1]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $otherStudent = $this->student();
        $this->actingAs($otherStudent)
            ->get(route('documents.download', $document))
            ->assertForbidden();

        $administrator = User::factory()->create();
        $administrator->assignRole('system-administrator');

        $this->actingAs($administrator)
            ->get(route('documents.download', $document))
            ->assertOk();

        $this->assertDatabaseHas('document_access_audits', [
            'document_id' => $document->getKey(),
            'user_id' => $owner->getKey(),
            'action' => 'viewed',
        ]);
        $this->assertDatabaseHas('document_access_audits', [
            'document_id' => $document->getKey(),
            'user_id' => $administrator->getKey(),
            'action' => 'downloaded',
        ]);
    }

    private function studentGroupLeader(?User $user = null): array
    {
        $user = $user ?? $this->student();

        $facilitator = User::factory()->create();
        $facilitator->assignRole('research-facilitator');

        $class = app(CreateResearchClass::class)->handle(
            $facilitator,
            (string) Str::uuid(),
            'Capstone 1',
            null,
            40,
        );

        $enrollment = ResearchClassEnrollment::query()->create([
            'research_class_id' => $class->id,
            'student_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $group = ResearchClassGroup::query()->create([
            'research_class_id' => $class->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Group Alpha',
            'created_by' => $facilitator->id,
            'status' => 'active',
            'leader_student_id' => $user->id,
        ]);

        ResearchClassGroupMember::query()->create([
            'research_class_group_id' => $group->id,
            'research_class_id' => $class->id,
            'research_class_enrollment_id' => $enrollment->id,
            'student_id' => $user->id,
            'assigned_by' => $facilitator->id,
        ]);

        return [
            'user' => $user,
            'facilitator' => $facilitator,
            'class' => $class,
            'enrollment' => $enrollment,
            'group' => $group,
        ];
    }

    private function student(): User
    {
        $user = User::factory()->create();
        $user->assignRole('student-researcher');

        return $user;
    }

    private function pdf(string $filename = 'paper.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $filename,
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n",
        );
    }

    private function docx(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ndmu-docx-');
        $this->assertNotFalse($path);

        $archive = new ZipArchive;
        $this->assertTrue($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $archive->addFromString(
            '[Content_Types].xml',
            '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>',
        );
        $archive->addFromString(
            '_rels/.rels',
            '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
        );
        $archive->addFromString(
            'word/document.xml',
            '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p/></w:body></w:document>',
        );
        $archive->close();

        return new UploadedFile(
            $path,
            'paper.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true,
        );
    }
}
