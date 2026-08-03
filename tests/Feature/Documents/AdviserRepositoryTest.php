<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdviserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_repository_contains_only_owned_and_assigned_research_documents(): void
    {
        $adviser = $this->adviser();
        $otherAdviser = $this->adviser();
        $assignedStudent = $this->student('Assigned Researcher');
        $hiddenStudent = $this->student('Hidden Researcher');
        $this->enroll($adviser, $assignedStudent);
        $this->enroll($otherAdviser, $hiddenStudent);

        $owned = $this->document($adviser, 'Adviser Resource.pdf', DocumentStatus::Accepted);
        $assignedPending = $this->document($assignedStudent, 'Assigned Proposal.pdf', DocumentStatus::Pending);
        $assignedEvaluation = $this->document($assignedStudent, 'Assigned Evaluation.pdf', DocumentStatus::UnderReview);
        $hidden = $this->document($hiddenStudent, 'Private Other Adviser.pdf', DocumentStatus::Accepted);

        $this->actingAs($adviser)
            ->get(route('adviser.dashboard', ['tab' => 'repository']))
            ->assertOk()
            ->assertViewHas(
                'repositoryDocuments',
                fn ($documents) => $documents->pluck('id')->contains($owned->getKey()),
            )
            ->assertSee($owned->original_filename)
            ->assertSee($assignedPending->original_filename)
            ->assertSee($assignedEvaluation->original_filename)
            ->assertDontSee($hidden->original_filename)
            ->assertSee(route('documents.view', $assignedPending))
            ->assertSee(route('documents.download', $assignedPending))
            ->assertViewHas('repositoryStats', [
                'total' => 3,
                'approved' => 1,
                'pending' => 1,
                'evaluation' => 1,
            ]);
    }

    public function test_repository_search_and_status_filters_are_applied_server_side(): void
    {
        $adviser = $this->adviser();
        $student = $this->student('Repository Student');
        $this->enroll($adviser, $student);
        $approved = $this->document($student, 'Unique Approved Paper.pdf', DocumentStatus::Accepted);
        $this->document($student, 'Pending Paper.pdf', DocumentStatus::Pending);

        $this->actingAs($adviser)
            ->get(route('adviser.dashboard', [
                'tab' => 'repository',
                'repository_q' => 'Unique Approved',
                'repository_status' => 'approved',
            ]))
            ->assertOk()
            ->assertSee($approved->original_filename)
            ->assertDontSee('Pending Paper.pdf')
            ->assertViewHas('repositorySearch', 'Unique Approved')
            ->assertViewHas('repositoryStatus', 'approved');
    }

    public function test_adviser_can_upload_a_secure_repository_document(): void
    {
        $adviser = $this->adviser();

        $this->actingAs($adviser)
            ->postJson(route('adviser.repository.documents.store'), [
                'submission_token' => (string) Str::uuid(),
                'document' => UploadedFile::fake()->createWithContent(
                    'Adviser Reference.pdf',
                    "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n",
                ),
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Document uploaded to the repository successfully.')
            ->assertJsonPath('document.original_filename', 'Adviser Reference.pdf')
            ->assertJsonMissingPath('document.storage_path')
            ->assertJsonMissingPath('document.stored_filename');

        $document = Document::query()->sole();

        $this->assertSame($adviser->getKey(), $document->user_id);
        Storage::disk('local')->assertExists($document->storage_path);
        $this->assertDatabaseHas('document_upload_audits', [
            'document_id' => $document->getKey(),
            'user_id' => $adviser->getKey(),
            'upload_status' => 'success',
        ]);
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
            'name' => "Repository Class {$student->getKey()}",
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
            'name' => 'Repository Group '.$student->getKey(),
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

    private function document(User $owner, string $filename, DocumentStatus $status): Document
    {
        $path = 'documents/'.Str::uuid().'.pdf';
        Storage::disk('local')->put($path, '%PDF-1.7 repository document');

        return Document::query()->create([
            'user_id' => $owner->getKey(),
            'submission_token' => (string) Str::uuid(),
            'original_filename' => $filename,
            'stored_filename' => Str::uuid().'.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'storage_disk' => 'local',
            'storage_path' => $path,
            'content_sha256' => hash('sha256', $filename),
            'submitted_at' => now(),
            'status' => $status,
        ]);
    }
}
