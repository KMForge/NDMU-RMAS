<?php

namespace Tests\Feature\Revisions;

use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Enums\RevisionStatus;
use App\Http\Requests\Revisions\StoreRevisionDocumentRequest;
use App\Models\Document;
use App\Models\DocumentUploadAudit;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\RevisionRequest;
use App\Models\User;
use App\Modules\Documents\Actions\CorrectDocumentReviewDecision;
use App\Modules\Documents\Actions\ReviewDocument;
use App\Modules\Documents\Exceptions\DocumentReviewException;
use App\Modules\Documents\Exceptions\DuplicateDocumentSubmission;
use App\Modules\Revisions\Actions\CreateRevisionCycleFromReview;
use App\Modules\Revisions\Actions\ReopenRevisionCycle;
use App\Modules\Revisions\Actions\ResolveRevisionCycle;
use App\Modules\Revisions\Actions\StartRevisionCycle;
use App\Modules\Revisions\Actions\SubmitRevisionDocument;
use App\Modules\Revisions\Exceptions\RevisionWorkflowException;
use App\Notifications\AcademicWorkflowNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RevisionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $adviser;

    private User $leader;

    private User $member;

    private User $facilitator;

    private ResearchClassGroup $group;

    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();

        $resolvePerm = Permission::findOrCreate('revisions.resolve');
        $uploadPerm = Permission::findOrCreate('documents.upload');
        $reviewPerm = Permission::findOrCreate('documents.review');
        $studentDashPerm = Permission::findOrCreate('dashboards.student.view');
        $adviserDashPerm = Permission::findOrCreate('dashboards.adviser.view');

        $adviserRole = Role::findOrCreate('Thesis Adviser');
        $adviserRole->givePermissionTo([$resolvePerm, $reviewPerm, $adviserDashPerm]);

        $studentRole = Role::findOrCreate('Student');
        $studentRole->givePermissionTo([$uploadPerm, $studentDashPerm]);

        $this->adviser = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $this->adviser->assignRole($adviserRole);

        $this->facilitator = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);

        $this->leader = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $this->leader->assignRole($studentRole);

        $this->member = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $this->member->assignRole($studentRole);

        $class = ResearchClass::query()->forceCreate([
            'facilitator_id' => $this->facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Research Methods Class A',
            'join_code_hash' => hash('sha256', 'JOIN123'),
            'join_code_encrypted' => 'JOIN123',
            'is_active' => true,
        ]);

        $this->group = ResearchClassGroup::query()->forceCreate([
            'research_class_id' => $class->id,
            'creation_token' => (string) Str::uuid(),
            'adviser_id' => $this->adviser->id,
            'created_by' => $this->leader->id,
            'leader_student_id' => $this->leader->id,
            'name' => 'Group Alpha',
            'status' => 'active',
            'disbanded_at' => null,
        ]);

        $leaderEnrollment = ResearchClassEnrollment::query()->forceCreate([
            'research_class_id' => $class->id,
            'student_id' => $this->leader->id,
            'status' => 'active',
        ]);

        ResearchClassGroupMember::query()->forceCreate([
            'research_class_group_id' => $this->group->id,
            'research_class_id' => $class->id,
            'research_class_enrollment_id' => $leaderEnrollment->id,
            'student_id' => $this->leader->id,
            'assigned_by' => $this->leader->id,
        ]);

        $memberEnrollment = ResearchClassEnrollment::query()->forceCreate([
            'research_class_id' => $class->id,
            'student_id' => $this->member->id,
            'status' => 'active',
        ]);

        ResearchClassGroupMember::query()->forceCreate([
            'research_class_group_id' => $this->group->id,
            'research_class_id' => $class->id,
            'research_class_enrollment_id' => $memberEnrollment->id,
            'student_id' => $this->member->id,
            'assigned_by' => $this->leader->id,
        ]);

        $this->document = Document::query()->create([
            'user_id' => $this->leader->id,
            'research_class_group_id' => $this->group->id,
            'submission_token' => (string) Str::uuid(),
            'original_filename' => 'proposal_v1.pdf',
            'stored_filename' => Str::uuid().'.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'document_stage' => DocumentStage::ProposalDefense,
            'version_number' => 1,
            'is_current' => true,
            'file_size' => 1024,
            'storage_disk' => 'local',
            'storage_path' => 'documents/'.Str::uuid().'.pdf',
            'content_sha256' => hash('sha256', 'proposal_v1.pdf'),
            'submitted_at' => now(),
            'status' => DocumentStatus::Pending,
        ]);
    }

    public function test_automatic_revision_cycle_creation_on_review(): void
    {
        $reviewAction = app(ReviewDocument::class);

        $review = $reviewAction->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Please revise Chapter 3 methodology.',
            '127.0.0.1'
        );

        $this->assertEquals(1, RevisionRequest::query()->count());
        $cycle = RevisionRequest::query()->first();

        $this->assertEquals($this->group->id, $cycle->research_class_group_id);
        $this->assertEquals($this->document->id, $cycle->document_id);
        $this->assertEquals($review->id, $cycle->source_document_review_id);
        $this->assertEquals(RevisionStatus::Open, $cycle->status);
        $this->assertEquals($this->adviser->id, $cycle->requested_by);
        $this->assertNull($cycle->assigned_to, 'Modern Phase 17 cycle must keep assigned_to null.');

        Notification::assertSentTo(
            [$this->leader, $this->member],
            AcademicWorkflowNotification::class,
            fn (AcademicWorkflowNotification $notification): bool => $notification->eventKey === 'document.review.decision-recorded',
        );
        Notification::assertNotSentTo($this->facilitator, AcademicWorkflowNotification::class);
    }

    public function test_adviser_revision_tracker_displays_scoped_revision_cycles(): void
    {
        app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Please revise Chapter 3 methodology.',
            '127.0.0.1'
        );

        $this->actingAs($this->adviser)
            ->get(route('adviser.dashboard', [
                'tab' => 'revisions',
                'revision_status' => 'all',
            ]))
            ->assertOk()
            ->assertSee('Revision Tracker')
            ->assertSee('Please revise Chapter 3 methodology.')
            ->assertSee('Group Alpha')
            ->assertSee('proposal_v1.pdf')
            ->assertSee('Source Document');
    }

    public function test_idempotent_cycle_creation(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Initial review.',
            '127.0.0.1'
        );

        $action = app(CreateRevisionCycleFromReview::class);
        $cycle1 = $action->handle($this->document, $review);
        $cycle2 = $action->handle($this->document, $review);

        $this->assertEquals($cycle1->id, $cycle2->id);
        $this->assertEquals(1, RevisionRequest::query()->count());
    }

    public function test_open_cycle_submission_is_denied(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Revisions requested.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        $this->assertEquals(RevisionStatus::Open, $cycle->status);

        $file = UploadedFile::fake()->create('revised.pdf', 100, 'application/pdf');

        $this->expectException(RevisionWorkflowException::class);

        try {
            app(SubmitRevisionDocument::class)->handle(
                $this->leader,
                $file,
                Str::uuid()->toString(),
                '127.0.0.1',
                $cycle
            );
        } finally {
            $cycle->refresh();
            $this->assertEquals(RevisionStatus::Open, $cycle->status);
            $this->assertNull($cycle->submitted_document_id);
            $this->document->refresh();
            $this->assertTrue($this->document->is_current);
            $this->assertEquals(1, Document::query()->count());
        }
    }

    public function test_in_progress_cycle_submission_is_approved(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Revisions requested.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);
        $cycle->refresh();
        $this->assertEquals(RevisionStatus::InProgress, $cycle->status);

        $file = UploadedFile::fake()->create('revised_proposal.pdf', 100, 'application/pdf');
        $newDoc = app(SubmitRevisionDocument::class)->handle(
            $this->leader,
            $file,
            Str::uuid()->toString(),
            '127.0.0.1',
            $cycle
        );

        $cycle->refresh();
        $this->assertEquals(RevisionStatus::Submitted, $cycle->status);
        $this->assertEquals($newDoc->id, $cycle->submitted_document_id);
        $this->assertEquals(2, $newDoc->version_number);
        $this->assertTrue($newDoc->is_current);

        $this->document->refresh();
        $this->assertFalse($this->document->is_current);
    }

    public function test_group_leader_relationship_based_authorization(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Revisions requested.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        $this->assertNull($cycle->assigned_to);

        $started = app(StartRevisionCycle::class)->handle($this->leader, $cycle);
        $this->assertEquals(RevisionStatus::InProgress, $started->status);
    }

    public function test_dynamic_group_leader_reassignment_transfers_authority(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Revisions requested.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);

        // Reassign group leader to $this->member
        $this->group->update(['leader_student_id' => $this->member->id]);
        $this->assertNull($cycle->assigned_to);

        // Former leader ($this->leader) cannot submit
        $file = UploadedFile::fake()->create('revised.pdf', 100, 'application/pdf');
        $this->expectException(AuthorizationException::class);
        app(SubmitRevisionDocument::class)->handle(
            $this->leader,
            $file,
            Str::uuid()->toString(),
            '127.0.0.1',
            $cycle
        );
    }

    public function test_new_group_leader_can_submit_without_changing_assigned_to(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Revisions requested.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);

        // Reassign group leader to $this->member
        $this->group->update(['leader_student_id' => $this->member->id]);

        $file = UploadedFile::fake()->create('new_leader_revised.pdf', 100, 'application/pdf');
        $newDoc = app(SubmitRevisionDocument::class)->handle(
            $this->member,
            $file,
            Str::uuid()->toString(),
            '127.0.0.1',
            $cycle
        );

        $cycle->refresh();
        $this->assertEquals(RevisionStatus::Submitted, $cycle->status);
        $this->assertEquals($newDoc->id, $cycle->submitted_document_id);
    }

    public function test_former_group_leader_cannot_mutate_revision_cycle(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Revisions requested.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        $this->group->update(['leader_student_id' => $this->member->id]);

        $this->expectException(AuthorizationException::class);
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);
    }

    public function test_secure_document_file_rejects_fake_content(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Revisions requested.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);

        // Fake PDF with invalid internal content signature
        $fakeFile = UploadedFile::fake()->createWithContent('malicious.pdf', 'NOT_A_VALID_PDF_HEADER');

        $request = new StoreRevisionDocumentRequest;
        $validator = validator([
            'submission_token' => Str::uuid()->toString(),
            'document' => $fakeFile,
        ], $request->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_oversized_revision_file_is_rejected(): void
    {
        $fakeFile = UploadedFile::fake()->create('huge_proposal.pdf', 11 * 1024, 'application/pdf');

        $request = new StoreRevisionDocumentRequest;
        $validator = validator([
            'submission_token' => Str::uuid()->toString(),
            'document' => $fakeFile,
        ], $request->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_invalid_submission_token_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf');

        $request = new StoreRevisionDocumentRequest;
        $validator = validator([
            'submission_token' => 'NOT-A-UUID',
            'document' => $file,
        ], $request->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_duplicate_content_submission_is_blocked(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Revisions requested.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);

        // Attempt to upload exact duplicate sha256 content of $this->document ('proposal_v1.pdf')
        $file = UploadedFile::fake()->createWithContent('proposal_v1.pdf', 'proposal_v1.pdf');

        $this->expectException(DuplicateDocumentSubmission::class);
        app(SubmitRevisionDocument::class)->handle(
            $this->leader,
            $file,
            Str::uuid()->toString(),
            '127.0.0.1',
            $cycle
        );
    }

    public function test_current_adviser_can_resolve_submitted_cycle(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Fix findings.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);

        $file = UploadedFile::fake()->create('revised.pdf', 100, 'application/pdf');
        app(SubmitRevisionDocument::class)->handle($this->leader, $file, Str::uuid()->toString(), '127.0.0.1', $cycle);

        $resolved = app(ResolveRevisionCycle::class)->handle($this->adviser, $cycle, 'Revisions verified.');
        $this->assertEquals(RevisionStatus::Resolved, $resolved->status);
        $this->assertNotNull($resolved->resolved_at);
    }

    public function test_former_adviser_cannot_resolve_revision_cycle(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Fix findings.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);

        $file = UploadedFile::fake()->create('revised.pdf', 100, 'application/pdf');
        app(SubmitRevisionDocument::class)->handle($this->leader, $file, Str::uuid()->toString(), '127.0.0.1', $cycle);

        // Reassign adviser
        $newAdviser = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $adviserRole = Role::findOrCreate('Thesis Adviser');
        $newAdviser->assignRole($adviserRole);
        $this->group->update(['adviser_id' => $newAdviser->id]);

        $this->expectException(AuthorizationException::class);
        app(ResolveRevisionCycle::class)->handle($this->adviser, $cycle);
    }

    public function test_facilitator_cannot_mutate_revision_state(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Fix findings.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();

        $this->expectException(AuthorizationException::class);
        app(StartRevisionCycle::class)->handle($this->facilitator, $cycle);
    }

    public function test_review_correction_with_v2_response_accepted(): void
    {
        $reviewAction = app(ReviewDocument::class);
        $correctAction = app(CorrectDocumentReviewDecision::class);

        $review = $reviewAction->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Needs revision.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);

        $file = UploadedFile::fake()->create('revised_v2.pdf', 100, 'application/pdf');
        $v2Doc = app(SubmitRevisionDocument::class)->handle(
            $this->leader,
            $file,
            Str::uuid()->toString(),
            '127.0.0.1',
            $cycle
        );

        $cycle->refresh();
        $this->assertEquals(RevisionStatus::Submitted, $cycle->status);

        // Correct V1 review decision to Accepted
        $correctAction->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::Accepted->value,
            'Original decision was accidental.',
            null,
            '127.0.0.1'
        );

        $cycle->refresh();
        $this->assertEquals(RevisionStatus::Cancelled, $cycle->status);
        $this->assertNotNull($cycle->invalidated_at);
        $this->assertEquals('Original decision was accidental.', $cycle->invalidated_reason);

        // V2 response document and submitted_document_id remain preserved
        $this->assertEquals($v2Doc->id, $cycle->submitted_document_id);
        $this->assertTrue(Document::query()->whereKey($v2Doc->id)->exists());
    }

    public function test_review_correction_with_v2_response_rejected(): void
    {
        $reviewAction = app(ReviewDocument::class);
        $correctAction = app(CorrectDocumentReviewDecision::class);

        $review = $reviewAction->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Needs revision.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);

        $file = UploadedFile::fake()->create('revised_v2.pdf', 100, 'application/pdf');
        $v2Doc = app(SubmitRevisionDocument::class)->handle(
            $this->leader,
            $file,
            Str::uuid()->toString(),
            '127.0.0.1',
            $cycle
        );

        // Correct V1 review decision to Rejected
        $correctAction->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::Rejected->value,
            'Original decision should have been Rejected.',
            null,
            '127.0.0.1'
        );

        $cycle->refresh();
        $this->assertEquals(RevisionStatus::Cancelled, $cycle->status);
        $this->assertEquals($v2Doc->id, $cycle->submitted_document_id);
        $this->assertTrue(Document::query()->whereKey($v2Doc->id)->exists());
    }

    public function test_unrelated_newer_version_blocks_review_correction(): void
    {
        $reviewAction = app(ReviewDocument::class);
        $correctAction = app(CorrectDocumentReviewDecision::class);

        $review = $reviewAction->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::Accepted->value,
            'Accepted initial version.',
            '127.0.0.1'
        );

        // Create an unrelated newer version V2 (not tied to any revision cycle)
        Document::query()->create([
            'user_id' => $this->leader->id,
            'research_class_group_id' => $this->group->id,
            'submission_token' => (string) Str::uuid(),
            'original_filename' => 'proposal_v2_unrelated.pdf',
            'stored_filename' => Str::uuid().'.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'document_stage' => DocumentStage::ProposalDefense,
            'version_number' => 2,
            'is_current' => true,
            'file_size' => 1024,
            'storage_disk' => 'local',
            'storage_path' => 'documents/'.Str::uuid().'.pdf',
            'content_sha256' => hash('sha256', 'proposal_v2_unrelated.pdf'),
            'submitted_at' => now(),
            'status' => DocumentStatus::Pending,
        ]);

        $this->expectException(DocumentReviewException::class);
        $correctAction->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::Rejected->value,
            'Attempting to correct V1 when unrelated V2 exists.',
            null,
            '127.0.0.1'
        );
    }

    public function test_revision_workflow_sends_contextual_phase_23_notifications(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Revisions requested.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);

        $file = UploadedFile::fake()->create('revised.pdf', 100, 'application/pdf');
        app(SubmitRevisionDocument::class)->handle($this->leader, $file, Str::uuid()->toString(), '127.0.0.1', $cycle);
        app(ResolveRevisionCycle::class)->handle($this->adviser, $cycle);
        app(ReopenRevisionCycle::class)->handle($this->adviser, $cycle, 'Reopening for review.');

        Notification::assertSentTo(
            [$this->leader, $this->member],
            AcademicWorkflowNotification::class,
            fn (AcademicWorkflowNotification $notification): bool => in_array($notification->eventKey, [
                'document.review.decision-recorded',
                'revision.resolved',
                'revision.reopened',
            ], true),
        );
        Notification::assertSentTo(
            $this->adviser,
            AcademicWorkflowNotification::class,
            fn (AcademicWorkflowNotification $notification): bool => str_contains($notification->eventKey, 'document.'),
        );
        Notification::assertNotSentTo($this->facilitator, AcademicWorkflowNotification::class);
    }

    public function test_zero_phase_18_progress_changes(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Revisions requested.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);

        $file = UploadedFile::fake()->create('revised.pdf', 100, 'application/pdf');
        app(SubmitRevisionDocument::class)->handle($this->leader, $file, Str::uuid()->toString(), '127.0.0.1', $cycle);
        app(ResolveRevisionCycle::class)->handle($this->adviser, $cycle);

        $this->assertEquals(0, DB::table('research_group_milestones')->where('status', 'completed')->count());
        $this->assertEquals(0, DB::table('research_group_milestone_events')->count());
    }

    public function test_upload_validation_failure_audit(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Revisions requested.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);

        $fakeFile = UploadedFile::fake()->createWithContent('malicious.pdf', 'NOT_A_PDF');

        $response = $this->actingAs($this->leader)
            ->postJson("/student/revisions/{$cycle->id}/documents", [
                'submission_token' => Str::uuid()->toString(),
                'document' => $fakeFile,
            ]);

        $response->assertStatus(422);
        $this->assertGreaterThan(0, DocumentUploadAudit::query()->where('upload_status', 'failed')->count());
    }
}
