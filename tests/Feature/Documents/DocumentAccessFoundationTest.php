<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Modules\Documents\Support\DocumentReviewerAccess;
use App\Policies\DocumentPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentAccessFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_active_member_can_access_a_document_uploaded_by_their_group_leader(): void
    {
        $leader = $this->student();
        $member = $this->student();
        $group = $this->groupWithMembers([$leader, $member], $leader);
        $document = $this->document($leader, $group);

        $this->assertTrue(app(DocumentPolicy::class)->view($member, $document));
        $this->assertNotSame($member->getKey(), $document->user_id);
    }

    public function test_student_cannot_access_another_groups_document_by_changing_its_id(): void
    {
        $studentA = $this->student();
        $studentB = $this->student();
        $this->groupWithMembers([$studentA], $studentA);
        $groupB = $this->groupWithMembers([$studentB], $studentB);
        $document = $this->document($studentB, $groupB);

        $this->assertFalse(app(DocumentPolicy::class)->view($studentA, $document));
    }

    public function test_only_the_current_assigned_adviser_can_review_a_group_document(): void
    {
        $assignedAdviser = $this->adviser();
        $otherAdviser = $this->adviser();
        $leader = $this->student();
        $group = $this->groupWithMembers([$leader], $leader, $assignedAdviser);
        $document = $this->document($leader, $group);
        $access = app(DocumentReviewerAccess::class);

        $this->assertTrue($access->canReview($assignedAdviser, $document));
        $this->assertFalse($access->canReview($otherAdviser, $document));
        $this->assertTrue(app(DocumentPolicy::class)->download($assignedAdviser, $document));
        $this->assertFalse(app(DocumentPolicy::class)->download($otherAdviser, $document));
    }

    public function test_leader_change_does_not_change_group_document_ownership(): void
    {
        $originalLeader = $this->student();
        $newLeader = $this->student();
        $group = $this->groupWithMembers([$originalLeader, $newLeader], $originalLeader);
        $document = $this->document($originalLeader, $group);

        $group->update(['leader_student_id' => $newLeader->getKey()]);

        $this->assertTrue(app(DocumentPolicy::class)->view($originalLeader, $document));
        $this->assertTrue(app(DocumentPolicy::class)->view($newLeader, $document));
        $this->assertSame($originalLeader->getKey(), $document->user_id);
        $this->assertSame($group->getKey(), $document->research_class_group_id);
    }

    public function test_null_group_legacy_document_has_only_narrow_uploader_access(): void
    {
        $uploader = $this->student();
        $otherStudent = $this->student();
        $document = $this->document($uploader);

        $this->assertTrue(app(DocumentPolicy::class)->view($uploader, $document));
        $this->assertFalse(app(DocumentPolicy::class)->view($otherStudent, $document));
    }

    /**
     * @param  array<int, User>  $students
     */
    private function groupWithMembers(
        array $students,
        User $leader,
        ?User $adviser = null,
    ): ResearchClassGroup {
        $facilitator = User::factory()->create();
        $facilitator->assignRole('research-facilitator');

        $researchClass = new ResearchClass([
            'facilitator_id' => $facilitator->getKey(),
            'creation_token' => (string) Str::uuid(),
            'name' => 'Access Class '.Str::random(8),
            'max_students' => 50,
            'is_active' => true,
        ]);
        $researchClass->setJoinCode(Str::upper(Str::random(8)));
        $researchClass->save();

        $group = ResearchClassGroup::query()->create([
            'research_class_id' => $researchClass->getKey(),
            'leader_student_id' => $leader->getKey(),
            'creation_token' => (string) Str::uuid(),
            'name' => 'Access Group '.Str::random(8),
            'adviser_id' => $adviser?->getKey(),
            'created_by' => $facilitator->getKey(),
            'status' => 'active',
        ]);

        foreach ($students as $student) {
            $enrollment = ResearchClassEnrollment::query()->create([
                'research_class_id' => $researchClass->getKey(),
                'student_id' => $student->getKey(),
                'status' => 'active',
                'requested_at' => now()->subDay(),
                'joined_at' => now(),
                'reviewed_by' => $facilitator->getKey(),
                'reviewed_at' => now(),
            ]);

            ResearchClassGroupMember::query()->create([
                'research_class_group_id' => $group->getKey(),
                'research_class_id' => $researchClass->getKey(),
                'research_class_enrollment_id' => $enrollment->getKey(),
                'student_id' => $student->getKey(),
                'assigned_by' => $facilitator->getKey(),
            ]);
        }

        return $group;
    }

    private function document(User $uploader, ?ResearchClassGroup $group = null): Document
    {
        return Document::query()->create([
            'user_id' => $uploader->getKey(),
            'research_class_group_id' => $group?->getKey(),
            'submission_token' => (string) Str::uuid(),
            'original_filename' => 'group-paper.pdf',
            'stored_filename' => Str::uuid().'.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'version_number' => 1,
            'is_current' => true,
            'file_size' => 1024,
            'storage_disk' => 'local',
            'storage_path' => 'documents/'.Str::uuid().'.pdf',
            'content_sha256' => hash('sha256', (string) Str::uuid()),
            'submitted_at' => now(),
            'status' => DocumentStatus::Pending,
        ]);
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
}
