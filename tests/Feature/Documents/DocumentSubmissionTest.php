<?php

namespace Tests\Feature\Documents;

use App\Models\Document;
use App\Models\User;
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
            'document' => $this->pdf(),
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_authorized_student_can_submit_a_valid_pdf(): void
    {
        $user = $this->student();

        $response = $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document' => $this->pdf('Research Paper.pdf'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Document submitted successfully.')
            ->assertJsonPath('document.original_filename', 'Research Paper.pdf')
            ->assertJsonPath('document.file_type', 'pdf')
            ->assertJsonPath('document.status', 'pending')
            ->assertJsonMissingPath('document.storage_path')
            ->assertJsonMissingPath('document.stored_filename');

        $document = Document::query()->sole();

        Storage::disk('local')->assertExists($document->storage_path);
        $this->assertNotSame('Research Paper.pdf', $document->stored_filename);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f-]{36}\.pdf$/',
            $document->stored_filename,
        );
        $this->assertDatabaseHas('document_upload_audits', [
            'document_id' => $document->getKey(),
            'user_id' => $user->getKey(),
            'upload_status' => 'success',
            'failure_reason' => null,
        ]);
    }

    public function test_authorized_student_can_submit_a_valid_docx(): void
    {
        $user = $this->student();

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document' => $this->docx(),
        ])->assertCreated()
            ->assertJsonPath('document.file_type', 'docx');

        $this->assertDatabaseHas('documents', [
            'user_id' => $user->getKey(),
            'file_type' => 'docx',
            'status' => 'pending',
        ]);
    }

    public function test_executable_and_disguised_files_are_rejected_and_audited(): void
    {
        $user = $this->student();

        foreach (['payload.exe', 'payload.php.pdf', 'payload.js.docx'] as $filename) {
            $this->actingAs($user)->postJson(route('student.documents.store'), [
                'submission_token' => (string) Str::uuid(),
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
        $user = $this->student();

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document' => UploadedFile::fake()->createWithContent('corrupt.pdf', '%PDF-1.7 broken'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('document');

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document' => UploadedFile::fake()->createWithContent('empty.pdf', ''),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('document');

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_upload_audits', 2);
    }

    public function test_document_larger_than_ten_megabytes_is_rejected(): void
    {
        $user = $this->student();

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('document');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_missing_upload_permission_is_rejected_and_audited(): void
    {
        $user = $this->student();
        Role::findByName('student-researcher')->syncPermissions(['research.view-own']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document' => $this->pdf(),
        ])->assertForbidden()
            ->assertExactJson([
                'message' => 'You do not have permission to submit documents.',
            ]);

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseHas('document_upload_audits', [
            'user_id' => $user->getKey(),
            'upload_status' => 'failed',
            'failure_reason' => 'You do not have permission to submit documents.',
        ]);
    }

    public function test_same_submission_token_cannot_create_duplicate_documents(): void
    {
        $user = $this->student();
        $token = (string) Str::uuid();

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => $token,
            'document' => $this->pdf(),
        ])->assertCreated();

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => $token,
            'document' => $this->pdf(),
        ])->assertConflict();

        $this->assertDatabaseCount('documents', 1);
        $this->assertDatabaseCount('document_upload_audits', 2);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_database_failure_rolls_back_record_and_deletes_stored_file(): void
    {
        $user = $this->student();
        $audit = Mockery::mock(RecordDocumentUploadAttempt::class);
        $audit->shouldReceive('success')
            ->once()
            ->andThrow(new RuntimeException('Simulated database failure.'));
        $audit->shouldReceive('failure')->once();
        $this->app->instance(RecordDocumentUploadAttempt::class, $audit);

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document' => $this->pdf(),
        ])->assertInternalServerError()
            ->assertExactJson([
                'message' => 'The document could not be stored securely. Please try again.',
            ]);

        $this->assertDatabaseCount('documents', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_upload_rate_limiter_rejects_the_sixth_attempt_and_audits_it(): void
    {
        $user = $this->student();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->actingAs($user)->postJson(route('student.documents.store'), [
                'submission_token' => (string) Str::uuid(),
                'document' => UploadedFile::fake()->createWithContent('invalid.exe', 'invalid'),
            ])->assertUnprocessable();
        }

        $this->actingAs($user)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document' => UploadedFile::fake()->createWithContent('invalid.exe', 'invalid'),
        ])->assertTooManyRequests()
            ->assertJsonPath('message', 'Too many upload attempts. Please wait before trying again.');

        $this->assertDatabaseCount('document_upload_audits', 6);
        $this->assertDatabaseHas('document_upload_audits', [
            'upload_status' => 'failed',
            'failure_reason' => 'Too many upload attempts. Please wait before trying again.',
        ]);
    }

    public function test_owner_and_authorized_administrator_can_access_document_but_another_student_cannot(): void
    {
        $owner = $this->student();

        $this->actingAs($owner)->postJson(route('student.documents.store'), [
            'submission_token' => (string) Str::uuid(),
            'document' => $this->pdf(),
        ])->assertCreated();

        $document = Document::query()->sole();

        $this->actingAs($owner)
            ->get(route('documents.view', $document))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $otherStudent = $this->student();
        $this->actingAs($otherStudent)
            ->get(route('documents.download', $document))
            ->assertForbidden();

        $administrator = User::factory()->create();
        $administrator->assignRole('system-administrator');

        $this->actingAs($administrator)
            ->get(route('documents.download', $document))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
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
