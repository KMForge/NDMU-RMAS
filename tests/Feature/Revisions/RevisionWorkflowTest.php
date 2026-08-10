<?php

namespace Tests\Feature\Revisions;

use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Enums\RevisionStatus;
use App\Models\Document;
use App\Models\DocumentReviewComment;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\RevisionRequest;
use App\Models\User;
use App\Modules\Documents\Actions\CorrectDocumentReviewDecision;
use App\Modules\Documents\Actions\ReviewDocument;
use App\Modules\Revisions\Actions\ReopenRevisionCycle;
use App\Modules\Revisions\Actions\ResolveRevisionCycle;
use App\Modules\Revisions\Actions\StartRevisionCycle;
use App\Modules\Revisions\Actions\SubmitRevisionDocument;
use App\Modules\Revisions\Actions\UpdateRevisionDueDate;
use App\Modules\Revisions\Exceptions\RevisionWorkflowException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

        $adviserRole = Role::findOrCreate('Thesis Adviser');
        $adviserRole->givePermissionTo([$resolvePerm, $reviewPerm]);

        $studentRole = Role::findOrCreate('Student');
        $studentRole->givePermissionTo([$uploadPerm]);

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

        // Verify zero notification dispatches (Strict Phase 23 boundary)
        Notification::assertNothingSent();
    }

    public function test_review_decision_correction_reconciliation_matrix(): void
    {
        $reviewAction = app(ReviewDocument::class);
        $correctAction = app(CorrectDocumentReviewDecision::class);

        // 1. Initial Review -> RevisionRequested
        $review = $reviewAction->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Needs revision.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        $this->assertEquals(RevisionStatus::Open, $cycle->status);

        // 2. Correct decision away to Accepted -> Cycle should be Cancelled
        $correctAction->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::Accepted->value,
            'Mistaken decision, document is accepted.',
            null,
            '127.0.0.1'
        );

        $cycle->refresh();
        $this->assertEquals(RevisionStatus::Cancelled, $cycle->status);
        $this->assertNotNull($cycle->invalidated_at);

        // 3. Correct decision back to RevisionRequested -> Exactly 1 new cycle created
        $this->document->refresh();
        $correctAction->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Re-evaluating, revision is actually required.',
            null,
            '127.0.0.1'
        );

        $this->assertEquals(2, RevisionRequest::query()->count());
        $newCycle = RevisionRequest::query()->where('status', RevisionStatus::Open)->first();
        $this->assertNotNull($newCycle);
    }

    public function test_group_leader_start_and_submit_workflow(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Revisions requested.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();

        // Non-leader member cannot start
        $this->expectException(AuthorizationException::class);
        app(StartRevisionCycle::class)->handle($this->member, $cycle);
    }

    public function test_group_leader_can_start_and_submit_revision(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Revisions requested.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();

        // 1. Group Leader starts cycle
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);
        $cycle->refresh();
        $this->assertEquals(RevisionStatus::InProgress, $cycle->status);

        // 2. Group Leader submits corrected document
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
        $this->assertEquals(DocumentStage::ProposalDefense, $newDoc->document_stage);
        $this->assertEquals(2, $newDoc->version_number);
        $this->assertTrue($newDoc->is_current);

        // Source V1 is now void (is_current = false)
        $this->document->refresh();
        $this->assertFalse($this->document->is_current);
    }

    public function test_blocking_findings_prevent_adviser_resolution(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Fix critical methodology.',
            '127.0.0.1'
        );

        // Add a blocking comment
        DocumentReviewComment::query()->create([
            'document_id' => $this->document->id,
            'author_id' => $this->adviser->id,
            'reviewer_id' => $this->adviser->id,
            'comment' => 'Critical error in methodology',
            'severity' => 'critical',
            'resolved_at' => null,
        ]);

        $cycle = RevisionRequest::query()->first();
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);

        $file = UploadedFile::fake()->create('revised.pdf', 100, 'application/pdf');
        app(SubmitRevisionDocument::class)->handle($this->leader, $file, Str::uuid()->toString(), '127.0.0.1', $cycle);

        $cycle->refresh();

        // Attempting to resolve with unresolved critical finding fails
        $this->expectException(RevisionWorkflowException::class);
        app(ResolveRevisionCycle::class)->handle($this->adviser, $cycle);
    }

    public function test_adviser_can_resolve_after_resolving_blocking_findings(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Fix findings.',
            '127.0.0.1'
        );

        $comment = DocumentReviewComment::query()->create([
            'document_id' => $this->document->id,
            'author_id' => $this->adviser->id,
            'reviewer_id' => $this->adviser->id,
            'comment' => 'Revision finding',
            'severity' => 'revision',
            'resolved_at' => null,
        ]);

        $cycle = RevisionRequest::query()->first();
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);
        $file = UploadedFile::fake()->create('revised2.pdf', 100, 'application/pdf');
        app(SubmitRevisionDocument::class)->handle($this->leader, $file, Str::uuid()->toString(), '127.0.0.1', $cycle);

        // Resolve blocking finding
        $comment->update(['resolved_at' => now(), 'resolver_id' => $this->adviser->id]);

        // Now resolve cycle succeeds
        $resolvedCycle = app(ResolveRevisionCycle::class)->handle($this->adviser, $cycle, 'Verified revisions.');
        $this->assertEquals(RevisionStatus::Resolved, $resolvedCycle->status);
        $this->assertNotNull($resolvedCycle->resolved_at);
    }

    public function test_controlled_reopen_returns_cycle_to_submitted_state(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Revisions required.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        app(StartRevisionCycle::class)->handle($this->leader, $cycle);
        $file = UploadedFile::fake()->create('revised3.pdf', 100, 'application/pdf');
        $newDoc = app(SubmitRevisionDocument::class)->handle($this->leader, $file, Str::uuid()->toString(), '127.0.0.1', $cycle);

        app(ResolveRevisionCycle::class)->handle($this->adviser, $cycle);
        $cycle->refresh();
        $this->assertEquals(RevisionStatus::Resolved, $cycle->status);

        // Controlled Reopen returns to SUBMITTED and preserves submitted_document_id
        app(ReopenRevisionCycle::class)->handle($this->adviser, $cycle, 'Accidental resolution mark.');
        $cycle->refresh();

        $this->assertEquals(RevisionStatus::Submitted, $cycle->status);
        $this->assertEquals($newDoc->id, $cycle->submitted_document_id);
        $this->assertNull($cycle->resolved_at);
    }

    public function test_due_date_management_by_current_adviser(): void
    {
        $review = app(ReviewDocument::class)->handle(
            $this->adviser,
            $this->document,
            DocumentStatus::RevisionRequested->value,
            'Revisions required.',
            '127.0.0.1'
        );

        $cycle = RevisionRequest::query()->first();
        $futureDate = now()->addDays(7)->toIso8601String();

        app(UpdateRevisionDueDate::class)->handle($this->adviser, $cycle, $futureDate);
        $cycle->refresh();

        $this->assertNotNull($cycle->due_at);
    }
}
