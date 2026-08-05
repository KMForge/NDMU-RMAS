<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentReviewComment;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        $this->createResearchContextTables();
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
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'")
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');

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
        $this->assertDatabaseHas('revision_requests', [
            'document_id' => $document->getKey(),
            'requested_by' => $adviser->getKey(),
            'assigned_to' => $student->getKey(),
            'status' => 'open',
            'instructions' => 'Please correct the methodology.',
        ]);
        $this->assertDatabaseHas('research_proposals', [
            'document_id' => $document->getKey(),
            'submitted_by' => $student->getKey(),
            'reviewed_by' => $adviser->getKey(),
            'status' => 'revision_requested',
        ]);
        $this->assertDatabaseHas('document_review_audits', [
            'document_id' => $document->getKey(),
            'reviewer_id' => $adviser->getKey(),
            'student_id' => $student->getKey(),
            'action' => 'document_reviewed',
            'decision' => 'revision_requested',
        ]);
        $this->assertDatabaseCount('notifications', 1);
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

    public function test_accepted_document_creates_progress_and_proposal_records(): void
    {
        $adviser = $this->adviser();
        $student = $this->student('Accepted Student');
        $this->enroll($adviser, $student);
        $projectId = $this->attachProject($student);
        $milestoneId = DB::table('research_milestones')->insertGetId([
            'academic_term_id' => 1,
            'program_id' => 1,
            'name' => 'Proposal Review',
            'description' => 'Submit and pass proposal review.',
            'due_at' => now()->addWeek(),
            'sequence' => 1,
            'is_required' => true,
        ]);
        $document = $this->document($student, 'accepted-proposal.pdf');

        $this->actingAs($adviser)
            ->patchJson(route('adviser.documents.review', $document), [
                'decision' => 'accepted',
                'review_notes' => 'Looks good.',
            ])
            ->assertOk()
            ->assertJsonPath('review.decision', 'accepted');

        $this->assertDatabaseHas('research_proposals', [
            'research_project_id' => $projectId,
            'document_id' => $document->getKey(),
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('research_progress_updates', [
            'research_project_id' => $projectId,
            'milestone_id' => $milestoneId,
            'submitted_by' => $student->getKey(),
            'reviewed_by' => $adviser->getKey(),
            'evidence_document_id' => $document->getKey(),
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('document_review_audits', [
            'document_id' => $document->getKey(),
            'decision' => 'accepted',
        ]);
        $this->assertDatabaseCount('notifications', 1);
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

    public function test_classes_tab_does_not_query_hidden_consultation_or_document_tabs(): void
    {
        $adviser = $this->adviser();
        $student = $this->student('Lazy Tab Student');
        $this->enroll($adviser, $student);
        $queries = [];

        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->actingAs($adviser)
            ->get(route('adviser.dashboard', ['tab' => 'classes']))
            ->assertOk();

        $executedSql = implode("\n", $queries);

        $this->assertStringNotContainsString('consultation_requests', $executedSql);
        $this->assertStringNotContainsString('consultation_records', $executedSql);
        $this->assertStringNotContainsString('document_review_comments', $executedSql);
        $this->assertStringNotContainsString(' from "documents"', $executedSql);
    }

    public function test_class_authorized_document_view_avoids_schema_introspection_queries(): void
    {
        $adviser = $this->adviser();
        $student = $this->student('Fast File Student');
        $this->enroll($adviser, $student);
        $document = $this->document($student, 'fast-paper.pdf');
        Storage::disk('local')->put($document->storage_path, '%PDF-1.7 fast');
        $queries = [];

        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->actingAs($adviser)
            ->get(route('documents.view', $document))
            ->assertOk();

        $this->assertStringNotContainsString(
            'information_schema',
            implode("\n", $queries),
        );
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
        $facilitator = User::factory()->create();
        $facilitator->assignRole('research-facilitator');
        $researchClass = new ResearchClass([
            'facilitator_id' => $facilitator->getKey(),
            'creation_token' => (string) Str::uuid(),
            'name' => 'Research Class '.$student->getKey(),
            'max_students' => 50,
            'is_active' => true,
        ]);
        $researchClass->setJoinCode(Str::upper(Str::random(8)));
        $researchClass->save();

        $enrollment = ResearchClassEnrollment::query()->create([
            'research_class_id' => $researchClass->getKey(),
            'student_id' => $student->getKey(),
            'status' => 'active',
            'requested_at' => now()->subDay(),
            'joined_at' => now(),
            'reviewed_by' => $facilitator->getKey(),
            'reviewed_at' => now(),
        ]);

        $group = ResearchClassGroup::query()->create([
            'research_class_id' => $researchClass->getKey(),
            'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone Group '.$student->getKey(),
            'adviser_id' => $adviser->getKey(),
            'created_by' => $facilitator->getKey(),
        ]);
        ResearchClassGroupMember::query()->create([
            'research_class_group_id' => $group->getKey(),
            'research_class_id' => $researchClass->getKey(),
            'research_class_enrollment_id' => $enrollment->getKey(),
            'student_id' => $student->getKey(),
            'assigned_by' => $facilitator->getKey(),
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

    private function attachProject(User $student): int
    {
        $profileId = DB::table('student_profiles')->insertGetId([
            'user_id' => $student->getKey(),
            'student_number' => 'STU-'.$student->getKey(),
        ]);

        DB::table('research_groups')->insert([
            'id' => 100 + $student->getKey(),
            'program_id' => 1,
            'academic_term_id' => 1,
        ]);

        DB::table('research_group_members')->insert([
            'research_group_id' => 100 + $student->getKey(),
            'student_profile_id' => $profileId,
            'member_role' => 'researcher',
            'joined_at' => now(),
        ]);

        return DB::table('research_projects')->insertGetId([
            'research_group_id' => 100 + $student->getKey(),
            'title' => 'Backend Integrated Research',
            'abstract' => 'Used for document review integrations.',
            'keywords' => json_encode(['backend'], JSON_THROW_ON_ERROR),
            'category' => 'thesis',
            'status' => 'in_progress',
            'created_by' => $student->getKey(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createResearchContextTables(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (['research_milestones', 'consultation_requests', 'adviser_assignments', 'research_projects', 'research_group_members', 'research_groups', 'student_profiles'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        if (! Schema::hasTable('student_profiles')) {
            Schema::create('student_profiles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id');
                $table->string('student_number');
            });
        }

        if (! Schema::hasTable('research_groups')) {
            Schema::create('research_groups', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('program_id');
                $table->unsignedBigInteger('academic_term_id');
            });
        }

        if (! Schema::hasTable('research_group_members')) {
            Schema::create('research_group_members', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('research_group_id');
                $table->foreignId('student_profile_id');
                $table->string('member_role')->nullable();
                $table->timestamp('joined_at')->nullable();
                $table->timestamp('left_at')->nullable();
            });
        }

        if (! Schema::hasTable('research_projects')) {
            Schema::create('research_projects', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('research_group_id');
                $table->string('title');
                $table->text('abstract')->nullable();
                $table->json('keywords')->nullable();
                $table->string('category')->nullable();
                $table->string('status');
                $table->foreignId('created_by');
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('research_milestones')) {
            Schema::create('research_milestones', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('academic_term_id');
                $table->unsignedBigInteger('program_id');
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->unsignedInteger('sequence');
                $table->boolean('is_required')->default(true);
            });
        }
    }
}
