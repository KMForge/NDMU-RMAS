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
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdviserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_future_repository_scope_uses_the_documents_owning_group(): void
    {
        $adviser = $this->adviser();
        $otherAdviser = $this->adviser();
        $assignedUploader = $this->student();
        $hiddenUploader = $this->student();
        $assignedGroup = $this->group($adviser, $assignedUploader);
        $hiddenGroup = $this->group($otherAdviser, $hiddenUploader);
        $assigned = $this->document($assignedUploader, $assignedGroup, 'Assigned.pdf');
        $hidden = $this->document($hiddenUploader, $hiddenGroup, 'Hidden.pdf');

        $documents = app(DocumentReviewerAccess::class)
            ->scopeFor(Document::query(), $adviser)
            ->get();

        $this->assertTrue($documents->contains($assigned));
        $this->assertFalse($documents->contains($hidden));
    }

    public function test_group_document_scope_does_not_depend_on_the_uploader_membership_lookup(): void
    {
        $adviser = $this->adviser();
        $groupMember = $this->student();
        $historicalUploader = $this->student();
        $group = $this->group($adviser, $groupMember);
        $document = $this->document($historicalUploader, $group, 'Historical Uploader.pdf');

        $documents = app(DocumentReviewerAccess::class)
            ->scopeFor(Document::query(), $adviser)
            ->get();

        $this->assertTrue($documents->contains($document));
    }

    public function test_adviser_repository_upload_remains_disabled(): void
    {
        $adviser = $this->adviser();

        $this->actingAs($adviser)
            ->postJson(route('adviser.repository.documents.store'), [
                'submission_token' => (string) Str::uuid(),
                'document' => UploadedFile::fake()->createWithContent(
                    'Adviser Reference.pdf',
                    "%PDF-1.4\n%%EOF\n",
                ),
            ])
            ->assertStatus(410);

        $this->assertDatabaseCount('documents', 0);
    }

    private function group(User $adviser, User $student): ResearchClassGroup
    {
        $facilitator = User::factory()->create();
        $facilitator->assignRole('research-facilitator');

        $researchClass = new ResearchClass([
            'facilitator_id' => $facilitator->getKey(),
            'creation_token' => (string) Str::uuid(),
            'name' => 'Repository Class '.Str::random(8),
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
            'leader_student_id' => $student->getKey(),
            'creation_token' => (string) Str::uuid(),
            'name' => 'Repository Group '.Str::random(8),
            'adviser_id' => $adviser->getKey(),
            'created_by' => $facilitator->getKey(),
            'status' => 'active',
        ]);

        ResearchClassGroupMember::query()->create([
            'research_class_group_id' => $group->getKey(),
            'research_class_id' => $researchClass->getKey(),
            'research_class_enrollment_id' => $enrollment->getKey(),
            'student_id' => $student->getKey(),
            'assigned_by' => $facilitator->getKey(),
        ]);

        return $group;
    }

    private function document(
        User $uploader,
        ResearchClassGroup $group,
        string $filename,
    ): Document {
        return Document::query()->create([
            'user_id' => $uploader->getKey(),
            'research_class_group_id' => $group->getKey(),
            'submission_token' => (string) Str::uuid(),
            'original_filename' => $filename,
            'stored_filename' => Str::uuid().'.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'version_number' => 1,
            'is_current' => true,
            'file_size' => 1024,
            'storage_disk' => 'local',
            'storage_path' => 'documents/'.Str::uuid().'.pdf',
            'content_sha256' => hash('sha256', $filename),
            'submitted_at' => now(),
            'status' => DocumentStatus::Pending,
        ]);
    }

    private function adviser(): User
    {
        $adviser = User::factory()->create();
        $adviser->assignRole('research-adviser');

        return $adviser;
    }

    private function student(): User
    {
        $student = User::factory()->create();
        $student->assignRole('student-researcher');

        return $student;
    }
}
