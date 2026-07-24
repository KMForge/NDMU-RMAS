<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentReviewComment;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdviserDocumentReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_adviser_document_queue_is_scoped_to_active_students(): void
    {
        $adviser = $this->adviser();
        $otherAdviser = $this->adviser();
        $student = $this->student('Visible Researcher');
        $hiddenStudent = $this->student('Hidden Researcher');
        $this->enroll($adviser, $student);
        $this->enroll($otherAdviser, $hiddenStudent);
        $visibleDocument = $this->document($student, 'visible-paper.pdf');
        $this->document($hiddenStudent, 'hidden-paper.pdf');

        $this->actingAs($adviser)
            ->get(route('adviser.dashboard', [
                'tab' => 'docreview',
                'document_status' => 'all',
            ]))
            ->assertOk()
            ->assertSee('visible-paper.pdf')
            ->assertSee('Visible Researcher')
            ->assertDontSee('hidden-paper.pdf')
            ->assertDontSee('Hidden Researcher')
            ->assertSee(route('documents.view', $visibleDocument))
            ->assertSee(route('documents.download', $visibleDocument));
    }

    public function test_assigned_adviser_can_securely_view_but_other_adviser_cannot(): void
    {
        $adviser = $this->adviser();
        $otherAdviser = $this->adviser();
        $student = $this->student('File Owner');
        $this->enroll($adviser, $student);
        $document = $this->document($student, 'secure-paper.pdf');
        Storage::disk('local')->put($document->storage_path, '%PDF-1.7 secure');

        $this->actingAs($adviser)
            ->get(route('documents.view', $document))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($otherAdviser)
            ->get(route('documents.view', $document))
            ->assertForbidden();
    }

    public function test_assigned_adviser_can_post_sanitized_comment(): void
    {
        $adviser = $this->adviser();
        $student = $this->student('Comment Student');
        $this->enroll($adviser, $student);
        $document = $this->document($student, 'comment-paper.pdf');

        $this->actingAs($adviser)
            ->postJson(route('adviser.documents.comments.store', $document), [
                'comment' => 'Revise this section <script>alert(1)</script>',
                'severity' => 'critical',
                'page_number' => 12,
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Comment posted successfully.')
            ->assertJsonPath('comment.severity', 'critical')
            ->assertJsonMissingPath('comment.document_id');

        $this->assertDatabaseHas('document_review_comments', [
            'document_id' => $document->getKey(),
            'author_id' => $adviser->getKey(),
            'comment' => 'Revise this section alert(1)',
            'severity' => 'critical',
            'page_number' => 12,
        ]);
        $this->assertSame(DocumentStatus::UnderReview, $document->fresh()->status);
    }

    public function test_revision_and_rejection_decisions_require_notes(): void
    {
        $adviser = $this->adviser();
        $student = $this->student('Validation Student');
        $this->enroll($adviser, $student);
        $document = $this->document($student, 'validation-paper.pdf');

        $this->actingAs($adviser)
            ->patchJson(route('adviser.documents.review', $document), [
                'decision' => 'revision_requested',
                'review_notes' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('review_notes');

        $this->assertDatabaseCount('document_reviews', 0);
        $this->assertSame(DocumentStatus::Pending, $document->fresh()->status);
    }

    public function test_assigned_adviser_can_request_revision_once(): void
    {
        $adviser = $this->adviser();
        $student = $this->student('Revision Student');
        $this->enroll($adviser, $student);
        $document = $this->document($student, 'revision-paper.pdf');

        $this->actingAs($adviser)
            ->patchJson(route('adviser.documents.review', $document), [
                'decision' => 'revision_requested',
                'review_notes' => '<b>Please correct the methodology.</b>',
            ])
            ->assertOk()
            ->assertJsonPath('review.decision', 'revision_requested')
            ->assertJsonMissingPath('review.review_notes');

        $this->assertDatabaseHas('document_reviews', [
            'document_id' => $document->getKey(),
            'reviewer_id' => $adviser->getKey(),
            'decision' => 'revision_requested',
            'review_notes' => 'Please correct the methodology.',
        ]);
        $this->assertSame(DocumentStatus::RevisionRequested, $document->fresh()->status);

        $this->actingAs($adviser)
            ->patchJson(route('adviser.documents.review', $document), [
                'decision' => 'accepted',
            ])
            ->assertConflict()
            ->assertExactJson([
                'message' => 'This document has already received a final review decision.',
            ]);

        $this->assertDatabaseCount('document_reviews', 1);
    }

    public function test_adviser_cannot_review_another_advisers_document(): void
    {
        $assignedAdviser = $this->adviser();
        $otherAdviser = $this->adviser();
        $student = $this->student('Protected Student');
        $this->enroll($assignedAdviser, $student);
        $document = $this->document($student, 'protected-paper.pdf');

        $this->actingAs($otherAdviser)
            ->patchJson(route('adviser.documents.review', $document), [
                'decision' => 'accepted',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('document_reviews', 0);
    }

    public function test_comment_resolution_is_scoped_to_its_document(): void
    {
        $adviser = $this->adviser();
        $student = $this->student('Resolve Student');
        $this->enroll($adviser, $student);
        $document = $this->document($student, 'first-paper.pdf');
        $otherDocument = $this->document($student, 'second-paper.pdf');
        $comment = DocumentReviewComment::query()->create([
            'document_id' => $otherDocument->getKey(),
            'author_id' => $adviser->getKey(),
            'severity' => 'comment',
            'comment' => 'Comment on the other document.',
        ]);

        $this->actingAs($adviser)
            ->patchJson(route('adviser.documents.comments.resolve', [$document, $comment]))
            ->assertConflict()
            ->assertExactJson(['message' => 'The document comment was not found.']);

        $this->assertNull($comment->fresh()->resolved_at);
    }

    private function adviser(): User
    {
        $adviser = User::factory()->create();
        $adviser->assignRole('research-adviser');

        return $adviser;
    }

    private function student(string $name): User
    {
        $student = User::factory()->create(['name' => $name]);
        $student->assignRole('student-researcher');

        return $student;
    }

    private function enroll(User $adviser, User $student): void
    {
        $researchClass = new ResearchClass([
            'adviser_id' => $adviser->getKey(),
            'creation_token' => (string) Str::uuid(),
            'name' => 'Research Class '.$student->getKey(),
            'max_students' => 50,
            'is_active' => true,
        ]);
        $researchClass->setJoinCode(Str::upper(Str::random(8)));
        $researchClass->save();

        ResearchClassEnrollment::query()->create([
            'research_class_id' => $researchClass->getKey(),
            'student_id' => $student->getKey(),
            'status' => 'active',
            'requested_at' => now()->subDay(),
            'joined_at' => now(),
            'reviewed_by' => $adviser->getKey(),
            'reviewed_at' => now(),
        ]);
    }

    private function document(User $student, string $filename): Document
    {
        return Document::query()->create([
            'user_id' => $student->getKey(),
            'submission_token' => (string) Str::uuid(),
            'original_filename' => $filename,
            'stored_filename' => Str::uuid().'.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'storage_disk' => 'local',
            'storage_path' => 'documents/'.Str::uuid().'.pdf',
            'content_sha256' => hash('sha256', $filename),
            'submitted_at' => now(),
            'status' => DocumentStatus::Pending,
        ]);
    }
}
