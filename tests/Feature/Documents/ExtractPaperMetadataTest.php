<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Document;
use App\Models\Program;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\ResearchGroup;
use App\Models\ResearchProject;
use App\Models\User;
use App\Modules\Documents\Services\ExtractPaperMetadata;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class ExtractPaperMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_extracts_abstract_and_keywords_from_docx_file(): void
    {
        $this->seed(AcademicStructureSeeder::class);
        Storage::fake('local');

        // Create a minimal valid docx in memory
        $tempPath = tempnam(sys_get_temp_dir(), 'test_docx_');
        $zip = new ZipArchive;
        $zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>Research Title</w:t></w:r></w:p>
        <w:p><w:r><w:t>Abstract</w:t></w:r></w:p>
        <w:p><w:r><w:t>This is the first paragraph of the research abstract discussing the problem and methodology.</w:t></w:r></w:p>
        <w:p><w:r><w:t>This is the second paragraph summarizing the key findings and results of the study.</w:t></w:r></w:p>
        <w:p><w:r><w:t>Keywords: artificial intelligence, machine learning, deep learning, neural networks</w:t></w:r></w:p>
        <w:p><w:r><w:t>Chapter 1</w:t></w:r></w:p>
        <w:p><w:r><w:t>Introduction</w:t></w:r></w:p>
    </w:body>
</w:document>');
        $zip->close();

        $storagePath = 'documents/test_sample.docx';
        Storage::disk('local')->put($storagePath, file_get_contents($tempPath));
        @unlink($tempPath);

        $user = User::factory()->create();
        $program = Program::query()->firstOrFail();
        $year = AcademicYear::query()->create([
            'name' => '2026–2027',
            'starts_at' => '2026-08-01',
            'ends_at' => '2027-05-31',
        ]);
        $term = AcademicTerm::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'First Semester',
            'starts_at' => '2026-08-01',
            'ends_at' => '2026-12-20',
        ]);
        $researchGroup = ResearchGroup::query()->create([
            'name' => 'Test Group',
            'program_id' => $program->id,
            'academic_term_id' => $term->id,
            'created_by' => $user->id,
        ]);
        $project = ResearchProject::query()->create([
            'research_group_id' => $researchGroup->id,
            'title' => 'Test Project',
            'status' => 'approved',
            'created_by' => $user->id,
        ]);
        $researchClass = new ResearchClass([
            'facilitator_id' => $user->getKey(),
            'creation_token' => (string) Str::uuid(),
            'name' => 'Test Class',
            'max_students' => 50,
            'is_active' => true,
        ]);
        $researchClass->setJoinCode(Str::upper(Str::random(8)));
        $researchClass->save();

        $classGroup = ResearchClassGroup::query()->create([
            'research_class_id' => $researchClass->id,
            'leader_student_id' => $user->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Class Group 1',
            'research_group_id' => $researchGroup->id,
            'created_by' => $user->id,
            'status' => 'active',
        ]);

        $document = Document::query()->create([
            'submission_token' => (string) Str::uuid(),
            'user_id' => $user->id,
            'research_class_group_id' => $classGroup->id,
            'original_filename' => 'manuscript.docx',
            'stored_filename' => 'sample.docx',
            'file_type' => 'docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'document_stage' => DocumentStage::FinalDefense,
            'version_number' => 1,
            'is_current' => true,
            'file_size' => 1024,
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
            'content_sha256' => 'sample_hash',
            'submitted_at' => now(),
            'status' => DocumentStatus::Accepted,
        ]);

        $service = app(ExtractPaperMetadata::class);
        $extracted = $service->extract($document);

        $this->assertNotNull($extracted['abstract']);
        $this->assertStringContainsString('first paragraph of the research abstract', $extracted['abstract']);
        $this->assertStringContainsString('second paragraph summarizing the key findings', $extracted['abstract']);
        $this->assertCount(4, $extracted['keywords']);
        $this->assertContains('artificial intelligence', $extracted['keywords']);
        $this->assertContains('machine learning', $extracted['keywords']);

        // Test sync
        $syncedProject = $service->extractAndSync($document);
        $this->assertNotNull($syncedProject);
        $this->assertStringContainsString('first paragraph of the research abstract', $syncedProject->abstract);
        $this->assertCount(4, $syncedProject->keywords);
    }

    public function test_student_can_fetch_paper_metadata_via_dashboard_action(): void
    {
        $this->seed(AcademicStructureSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_docx_');
        $zip = new ZipArchive;
        $zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>Abstract</w:t></w:r></w:p>
        <w:p><w:r><w:t>This abstract was automatically extracted upon button click.</w:t></w:r></w:p>
        <w:p><w:r><w:t>Keywords: automation, docx parsing, laravel</w:t></w:r></w:p>
        <w:p><w:r><w:t>Chapter 1</w:t></w:r></w:p>
    </w:body>
</w:document>');
        $zip->close();

        $storagePath = 'documents/sample_paper.docx';
        Storage::disk('local')->put($storagePath, file_get_contents($tempPath));
        @unlink($tempPath);

        $student = User::factory()->create();
        $student->assignRole('student-researcher');

        $program = Program::query()->firstOrFail();
        $year = AcademicYear::query()->create([
            'name' => '2026–2027',
            'starts_at' => '2026-08-01',
            'ends_at' => '2027-05-31',
        ]);
        $term = AcademicTerm::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'First Semester',
            'starts_at' => '2026-08-01',
            'ends_at' => '2026-12-20',
        ]);
        $researchGroup = ResearchGroup::query()->create([
            'name' => 'Group Alpha',
            'program_id' => $program->id,
            'academic_term_id' => $term->id,
            'created_by' => $student->id,
        ]);
        $project = ResearchProject::query()->create([
            'research_group_id' => $researchGroup->id,
            'title' => 'Project Alpha',
            'status' => 'approved',
            'created_by' => $student->id,
            'abstract' => null,
            'keywords' => [],
        ]);

        $facilitator = User::factory()->create();
        $facilitator->assignRole('research-facilitator');

        $researchClass = new ResearchClass([
            'facilitator_id' => $facilitator->getKey(),
            'creation_token' => (string) Str::uuid(),
            'name' => 'Class Alpha',
            'max_students' => 50,
            'is_active' => true,
        ]);
        $researchClass->setJoinCode(Str::upper(Str::random(8)));
        $researchClass->save();

        $classGroup = ResearchClassGroup::query()->create([
            'research_class_id' => $researchClass->id,
            'leader_student_id' => $student->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Class Group Alpha',
            'research_group_id' => $researchGroup->id,
            'created_by' => $facilitator->id,
            'status' => 'active',
        ]);

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
            'research_class_group_id' => $classGroup->getKey(),
            'research_class_id' => $researchClass->getKey(),
            'research_class_enrollment_id' => $enrollment->getKey(),
            'student_id' => $student->getKey(),
            'assigned_by' => $facilitator->getKey(),
        ]);

        Document::query()->create([
            'submission_token' => (string) Str::uuid(),
            'user_id' => $student->id,
            'research_class_group_id' => $classGroup->id,
            'original_filename' => 'paper.docx',
            'stored_filename' => 'paper.docx',
            'file_type' => 'docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'document_stage' => DocumentStage::FinalDefense,
            'version_number' => 1,
            'is_current' => true,
            'file_size' => 1024,
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
            'content_sha256' => 'sample_hash_2',
            'submitted_at' => now(),
            'status' => DocumentStatus::Accepted,
        ]);

        $response = $this->actingAs($student)
            ->post(route('student.research.fetch-metadata'));

        $response->assertRedirect(route('student.dashboard', ['tab' => 'research']));
        $response->assertSessionHas('research_success');

        $project->refresh();
        $this->assertSame('This abstract was automatically extracted upon button click.', $project->abstract);
        $this->assertEquals(['automation', 'docx parsing', 'laravel'], $project->keywords);
    }
}
