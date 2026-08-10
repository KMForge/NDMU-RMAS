<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentReview;
use App\Models\DocumentReviewComment;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        Storage::fake('private');
    }

    public function test_adviser_document_queue_is_scoped_to_assigned_groups(): void
    {
        $adviser = $this->adviser();
        $otherAdviser = $this->adviser();
        [$group, $leader] = $this->createGroupWithAdviser($adviser, 'Alpha Group');
        [$otherGroup, $otherLeader] = $this->createGroupWithAdviser($otherAdviser, 'Beta Group');

        $visibleDoc = $this->document($leader, $group, 'alpha-paper.pdf');
        $hiddenDoc = $this->document($otherLeader, $otherGroup, 'beta-paper.pdf');

        $this->actingAs($adviser)
            ->get(route('adviser.dashboard', [
                'tab' => 'docreview',
                'document_status' => 'all',
            ]))
            ->assertOk()
            ->assertSee('alpha-paper.pdf')
            ->assertSee('Alpha Group')
            ->assertDontSee('beta-paper.pdf')
            ->assertDontSee('Beta Group')
            ->assertSee(route('documents.view', $visibleDoc))
            ->assertSee(route('documents.download', $visibleDoc));
    }

    public function test_assigned_adviser_can_securely_view_document_stream(): void
    {
        $adviser = $this->adviser();
        $otherAdviser = $this->adviser();
        [$group, $leader] = $this->createGroupWithAdviser($adviser, 'Research Team');
        $document = $this->document($leader, $group, 'secure-paper.pdf');
        Storage::disk('private')->put($document->storage_path, '%PDF-1.7 content%%EOF');

        $this->actingAs($adviser)
            ->get(route('documents.view', $document))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'");

        $this->actingAs($otherAdviser)
            ->get(route('documents.view', $document))
            ->assertForbidden();
    }

    public function test_assigned_adviser_can_post_sanitized_comment_and_changes_status_to_under_review(): void
    {
        $adviser = $this->adviser();
        [$group, $leader] = $this->createGroupWithAdviser($adviser, 'Comment Group');
        $document = $this->document($leader, $group, 'comment-paper.pdf');

        $this->actingAs($adviser)
            ->postJson(route('adviser.documents.comments.store', $document), [
                'comment' => 'Revise this section <script>alert(1)</script>',
                'severity' => 'critical',
                'page_number' => 12,
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Comment posted successfully.')
            ->assertJsonPath('comment.severity', 'critical');

        $this->assertDatabaseHas('document_review_comments', [
            'document_id' => $document->getKey(),
            'author_id' => $adviser->getKey(),
            'comment' => 'Revise this section alert(1)',
            'severity' => 'critical',
            'page_number' => 12,
        ]);
        $this->assertDatabaseHas('document_review_audits', [
            'document_id' => $document->getKey(),
            'reviewer_id' => $adviser->getKey(),
            'action' => 'comment_added',
        ]);
        $this->assertSame(DocumentStatus::UnderReview, $document->fresh()->status);
    }

    public function test_unresolved_critical_or_revision_comment_blocks_accepted_decision(): void
    {
        $adviser = $this->adviser();
        [$group, $leader] = $this->createGroupWithAdviser($adviser, 'Blocking Group');
        $document = $this->document($leader, $group, 'blocking-paper.pdf');

        DocumentReviewComment::query()->create([
            'document_id' => $document->getKey(),
            'author_id' => $adviser->getKey(),
            'comment' => 'Must fix methodology',
            'severity' => 'critical',
        ]);

        $this->actingAs($adviser)
            ->patchJson(route('adviser.documents.review', $document), [
                'decision' => 'accepted',
                'review_notes' => 'Attempting acceptance.',
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Cannot accept document while it has unresolved revision or critical findings.');

        $this->assertDatabaseCount('document_reviews', 0);
        $this->assertSame(DocumentStatus::Pending, $document->fresh()->status);
    }

    public function test_informational_comment_does_not_block_accepted_decision(): void
    {
        $adviser = $this->adviser();
        [$group, $leader] = $this->createGroupWithAdviser($adviser, 'Info Group');
        $document = $this->document($leader, $group, 'info-paper.pdf');

        DocumentReviewComment::query()->create([
            'document_id' => $document->getKey(),
            'author_id' => $adviser->getKey(),
            'comment' => 'Nice formatting note.',
            'severity' => 'comment',
        ]);

        $this->actingAs($adviser)
            ->patchJson(route('adviser.documents.review', $document), [
                'decision' => 'accepted',
                'review_notes' => 'Approved.',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Document review decision saved successfully.');

        $this->assertDatabaseHas('document_reviews', [
            'document_id' => $document->getKey(),
            'reviewer_id' => $adviser->getKey(),
            'decision' => 'accepted',
            'review_notes' => 'Approved.',
        ]);
        $this->assertSame(DocumentStatus::Accepted, $document->fresh()->status);
    }

    public function test_assigned_adviser_can_resolve_comment_and_then_accept(): void
    {
        $adviser = $this->adviser();
        [$group, $leader] = $this->createGroupWithAdviser($adviser, 'Resolve Group');
        $document = $this->document($leader, $group, 'resolve-paper.pdf');

        $comment = DocumentReviewComment::query()->create([
            'document_id' => $document->getKey(),
            'author_id' => $adviser->getKey(),
            'comment' => 'Fix section 2',
            'severity' => 'revision',
        ]);

        $this->actingAs($adviser)
            ->patchJson(route('adviser.documents.comments.resolve', [$document, $comment]))
            ->assertOk()
            ->assertJsonPath('message', 'Comment resolved successfully.');

        $this->assertNotNull($comment->fresh()->resolved_at);
        $this->assertSame($adviser->getKey(), $comment->fresh()->resolved_by);

        $this->actingAs($adviser)
            ->patchJson(route('adviser.documents.review', $document), [
                'decision' => 'accepted',
                'review_notes' => 'All findings addressed.',
            ])
            ->assertOk();

        $this->assertSame(DocumentStatus::Accepted, $document->fresh()->status);
    }

    public function test_revision_and_rejection_decisions_require_notes(): void
    {
        $adviser = $this->adviser();
        [$group, $leader] = $this->createGroupWithAdviser($adviser, 'Validation Group');
        $document = $this->document($leader, $group, 'validation-paper.pdf');

        $this->actingAs($adviser)
            ->patchJson(route('adviser.documents.review', $document), [
                'decision' => 'revision_requested',
                'review_notes' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('review_notes');

        $this->assertDatabaseCount('document_reviews', 0);
    }

    public function test_controlled_decision_correction_preserves_history_and_updates_status(): void
    {
        $adviser = $this->adviser();
        [$group, $leader] = $this->createGroupWithAdviser($adviser, 'Correction Group');
        $document = $this->document($leader, $group, 'correction-paper.pdf');

        $this->actingAs($adviser)
            ->patchJson(route('adviser.documents.review', $document), [
                'decision' => 'revision_requested',
                'review_notes' => 'Please revise methodology.',
            ])
            ->assertOk();

        $originalReview = DocumentReview::query()->where('document_id', $document->getKey())->firstOrFail();

        $this->actingAs($adviser)
            ->patchJson(route('adviser.documents.review.correct', $document), [
                'decision' => 'rejected',
                'correction_reason' => 'Initial review did not check plagiarized sections.',
                'review_notes' => 'Paper rejected due to plagiarized sections.',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Document review decision corrected successfully.');

        $this->assertTrue($originalReview->fresh()->is_superseded);

        $this->assertDatabaseHas('document_reviews', [
            'document_id' => $document->getKey(),
            'reviewer_id' => $adviser->getKey(),
            'supersedes_review_id' => $originalReview->getKey(),
            'is_superseded' => false,
            'decision' => 'rejected',
            'correction_reason' => 'Initial review did not check plagiarized sections.',
        ]);

        $this->assertSame(DocumentStatus::Rejected, $document->fresh()->status);
        $this->assertDatabaseHas('document_review_audits', [
            'document_id' => $document->getKey(),
            'action' => 'review_decision_corrected',
            'decision' => 'rejected',
        ]);
    }

    public function test_cannot_review_or_correct_void_document_version(): void
    {
        $adviser = $this->adviser();
        [$group, $leader] = $this->createGroupWithAdviser($adviser, 'Void Group');
        $voidDoc = $this->document($leader, $group, 'void-v1.pdf');
        $voidDoc->update(['is_current' => false]);

        $this->actingAs($adviser)
            ->postJson(route('adviser.documents.comments.store', $voidDoc), [
                'comment' => 'Comment on void',
                'severity' => 'comment',
            ])
            ->assertStatus(409);

        $this->actingAs($adviser)
            ->patchJson(route('adviser.documents.review', $voidDoc), [
                'decision' => 'rejected',
                'review_notes' => 'Rejecting void document.',
            ])
            ->assertStatus(409);
    }

    public function test_unassigned_adviser_and_admin_without_assignment_cannot_review(): void
    {
        $assignedAdviser = $this->adviser();
        $otherAdviser = $this->adviser();
        $admin = $this->admin();
        [$group, $leader] = $this->createGroupWithAdviser($assignedAdviser, 'Protected Group');
        $document = $this->document($leader, $group, 'protected-paper.pdf');

        $this->actingAs($otherAdviser)
            ->patchJson(route('adviser.documents.review', $document), [
                'decision' => 'accepted',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patchJson(route('adviser.documents.review', $document), [
                'decision' => 'accepted',
            ])
            ->assertForbidden();
    }

    public function test_active_group_members_can_view_review_feedback(): void
    {
        $adviser = $this->adviser();
        [$group, $leader] = $this->createGroupWithAdviser($adviser, 'Member Group');
        $member = $this->student('Group Member');

        ResearchClassGroupMember::query()->create([
            'research_class_group_id' => $group->id,
            'research_class_id' => $group->research_class_id,
            'research_class_enrollment_id' => $this->enrollInClass($group->researchClass, $member),
            'student_id' => $member->id,
            'assigned_by' => $adviser->id,
        ]);

        $document = $this->document($leader, $group, 'group-manuscript.pdf');
        DocumentReviewComment::query()->create([
            'document_id' => $document->getKey(),
            'author_id' => $adviser->getKey(),
            'comment' => 'Add references section',
            'severity' => 'revision',
            'page_number' => 5,
        ]);

        $this->actingAs($member)
            ->get(route('student.classes.show', $group->researchClass))
            ->assertOk()
            ->assertSee('Add references section')
            ->assertSee('Page 5');
    }

    private function adviser(): User
    {
        $user = User::factory()->create([
            'user_type' => 'faculty',
            'status' => 'active',
            'approved_at' => now(),
        ]);
        $user->assignRole('thesis-adviser');

        return $user;
    }

    private function student(string $name = 'Student Researcher'): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'user_type' => 'student',
            'status' => 'active',
            'approved_at' => now(),
            'student_id' => 'STU-'.Str::random(6),
        ]);
        $user->assignRole('student');

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create([
            'user_type' => 'admin',
            'status' => 'active',
            'approved_at' => now(),
        ]);
        $user->assignRole('administrator');

        return $user;
    }

    /**
     * @return array{0: ResearchClassGroup, 1: User}
     */
    private function createGroupWithAdviser(User $adviser, string $groupName): array
    {
        $facilitator = User::factory()->create(['user_type' => 'faculty', 'status' => 'active', 'approved_at' => now()]);
        $facilitator->assignRole('research-facilitator');

        $class = new ResearchClass([
            'facilitator_id' => $facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone 101',
            'max_students' => 50,
            'is_active' => true,
        ]);
        $class->setJoinCode(Str::random(6));
        $class->save();

        $leader = $this->student($groupName.' Leader');
        $enrollmentId = $this->enrollInClass($class, $leader);

        $group = ResearchClassGroup::query()->create([
            'research_class_id' => $class->id,
            'leader_student_id' => $leader->id,
            'creation_token' => (string) Str::uuid(),
            'name' => $groupName,
            'adviser_id' => $adviser->id,
            'created_by' => $facilitator->id,
            'status' => 'active',
        ]);

        ResearchClassGroupMember::query()->create([
            'research_class_group_id' => $group->id,
            'research_class_id' => $class->id,
            'research_class_enrollment_id' => $enrollmentId,
            'student_id' => $leader->id,
            'assigned_by' => $facilitator->id,
        ]);

        return [$group, $leader];
    }

    private function enrollInClass(ResearchClass $class, User $student): int
    {
        return DB::table('research_class_enrollments')->insertGetId([
            'research_class_id' => $class->id,
            'student_id' => $student->id,
            'status' => 'active',
            'requested_at' => now(),
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function document(User $uploader, ResearchClassGroup $group, string $filename): Document
    {
        return Document::query()->create([
            'user_id' => $uploader->id,
            'research_class_group_id' => $group->id,
            'submission_token' => (string) Str::uuid(),
            'original_filename' => $filename,
            'stored_filename' => Str::random(40).'.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'document_stage' => DocumentStage::ProposalDefense->value,
            'version_number' => 1,
            'is_current' => true,
            'file_size' => 1024,
            'storage_disk' => 'private',
            'storage_path' => 'documents/'.Str::random(40).'.pdf',
            'content_sha256' => hash('sha256', $filename.Str::random(10)),
            'submitted_at' => now(),
            'status' => DocumentStatus::Pending,
        ]);
    }
}
