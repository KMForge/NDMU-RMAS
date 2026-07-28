<?php

namespace Tests\Feature\Revisions;

use App\Enums\RevisionStatus;
use App\Models\Document;
use App\Models\RevisionRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RevisionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_assigned_student_can_start_a_revision_and_event_is_audited(): void
    {
        $student = $this->student();
        $revision = $this->revision($student, $this->adviser());

        $this->actingAs($student)
            ->patchJson(route('student.revisions.start', $revision))
            ->assertOk()
            ->assertJsonPath('revision.status', 'in_progress');

        $this->assertDatabaseHas('revision_requests', [
            'id' => $revision->getKey(),
            'status' => 'in_progress',
        ]);
        $this->assertDatabaseHas('revision_request_events', [
            'revision_request_id' => $revision->getKey(),
            'actor_id' => $student->getKey(),
            'action' => 'started',
            'from_status' => 'open',
            'to_status' => 'in_progress',
        ]);
    }

    public function test_assigned_student_can_securely_submit_a_revised_document(): void
    {
        $student = $this->student();
        $adviser = $this->adviser();
        $revision = $this->revision($student, $adviser, RevisionStatus::InProgress);

        $this->actingAs($student)
            ->postJson(route('student.revisions.submit', $revision), [
                'submission_token' => (string) Str::uuid(),
                'document' => $this->pdf('corrected-methodology.pdf'),
            ])
            ->assertCreated()
            ->assertJsonPath('revision.status', 'submitted')
            ->assertJsonPath(
                'revision.document.original_filename',
                'corrected-methodology.pdf',
            )
            ->assertJsonMissingPath('revision.document.storage_path')
            ->assertJsonMissingPath('revision.document.stored_filename');

        $document = Document::query()->sole();

        $this->assertSame($revision->getKey(), $document->revision_request_id);
        $this->assertSame($student->getKey(), $document->user_id);
        Storage::disk('local')->assertExists($document->storage_path);
        $this->assertDatabaseHas('revision_requests', [
            'id' => $revision->getKey(),
            'status' => 'submitted',
        ]);
        $this->assertDatabaseHas('revision_request_events', [
            'revision_request_id' => $revision->getKey(),
            'actor_id' => $student->getKey(),
            'document_id' => $document->getKey(),
            'action' => 'submitted',
            'to_status' => 'submitted',
        ]);
        $this->assertDatabaseHas('document_upload_audits', [
            'document_id' => $document->getKey(),
            'user_id' => $student->getKey(),
            'upload_status' => 'success',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $adviser->getKey(),
        ]);
    }

    public function test_revision_submission_rejects_invalid_files_and_duplicate_state(): void
    {
        $student = $this->student();
        $revision = $this->revision($student, $this->adviser());

        $this->actingAs($student)
            ->postJson(route('student.revisions.submit', $revision), [
                'submission_token' => (string) Str::uuid(),
                'document' => UploadedFile::fake()->createWithContent(
                    'payload.php.pdf',
                    '<?php echo "bad";',
                ),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('document');

        $this->actingAs($student)
            ->postJson(route('student.revisions.submit', $revision), [
                'submission_token' => (string) Str::uuid(),
                'document' => $this->pdf(),
            ])
            ->assertCreated();

        $this->actingAs($student)
            ->postJson(route('student.revisions.submit', $revision), [
                'submission_token' => (string) Str::uuid(),
                'document' => $this->pdf('another-revision.pdf'),
            ])
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'This revision request is not accepting another document.',
            );

        $this->assertDatabaseCount('documents', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_adviser_can_resolve_and_reopen_a_submitted_revision(): void
    {
        $student = $this->student();
        $adviser = $this->adviser();
        $revision = $this->revision($student, $adviser, RevisionStatus::Submitted);

        $this->actingAs($adviser)
            ->patchJson(route('adviser.revisions.resolve', $revision), [
                'notes' => '<b>Required corrections completed.</b>',
            ])
            ->assertOk()
            ->assertJsonPath('revision.status', 'resolved');

        $this->assertDatabaseHas('revision_request_events', [
            'revision_request_id' => $revision->getKey(),
            'actor_id' => $adviser->getKey(),
            'action' => 'resolved',
            'from_status' => 'submitted',
            'to_status' => 'resolved',
            'notes' => 'Required corrections completed.',
        ]);
        $this->assertNotNull($revision->fresh()->resolved_at);

        $this->actingAs($adviser)
            ->patchJson(route('adviser.revisions.reopen', $revision), [
                'notes' => 'One more citation needs correction.',
            ])
            ->assertOk()
            ->assertJsonPath('revision.status', 'open');

        $this->assertNull($revision->fresh()->resolved_at);
        $this->assertDatabaseHas('revision_request_events', [
            'revision_request_id' => $revision->getKey(),
            'actor_id' => $adviser->getKey(),
            'action' => 'reopened',
            'from_status' => 'resolved',
            'to_status' => 'open',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $student->getKey(),
        ]);
    }

    public function test_other_users_cannot_change_a_revision_request(): void
    {
        $student = $this->student();
        $adviser = $this->adviser();
        $revision = $this->revision($student, $adviser, RevisionStatus::Submitted);

        $this->actingAs($this->student())
            ->patchJson(route('student.revisions.start', $revision))
            ->assertForbidden();

        $this->actingAs($this->adviser())
            ->patchJson(route('adviser.revisions.resolve', $revision))
            ->assertForbidden();

        $this->assertSame(RevisionStatus::Submitted, $revision->fresh()->status);
        $this->assertDatabaseCount('revision_request_events', 0);
    }

    private function student(): User
    {
        $student = User::factory()->create();
        $student->assignRole('student-researcher');

        return $student;
    }

    private function adviser(): User
    {
        $adviser = User::factory()->create();
        $adviser->assignRole('research-adviser');

        return $adviser;
    }

    private function revision(
        User $student,
        User $adviser,
        RevisionStatus $status = RevisionStatus::Open,
    ): RevisionRequest {
        return RevisionRequest::query()->create([
            'requested_by' => $adviser->getKey(),
            'assigned_to' => $student->getKey(),
            'title' => 'Revise Research Methodology',
            'instructions' => 'Correct the sampling method and citations.',
            'status' => $status,
            'due_at' => now()->addWeek(),
        ]);
    }

    private function pdf(string $filename = 'revised-paper.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $filename,
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n",
        );
    }
}
