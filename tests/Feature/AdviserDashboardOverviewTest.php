<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdviserDashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_dashboard_uses_database_records_instead_of_demo_data(): void
    {
        $adviser = User::factory()->create([
            'name' => 'Database Adviser',
            'department' => 'Graduate School',
        ]);
        $adviser->assignRole('research-adviser');
        $student = User::factory()->create(['name' => 'Database Student']);
        $student->assignRole('student-researcher');
        $this->enroll($adviser, $student);
        $document = $this->document($student, 'database-paper.pdf');

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\DatabaseTestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $adviser->getKey(),
            'data' => json_encode([
                'type' => 'document',
                'title' => 'Database Notification',
                'message' => 'This notification came from the database.',
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($adviser)
            ->get(route('adviser.dashboard', ['tab' => 'dashboard']))
            ->assertOk()
            ->assertSee('Database Adviser')
            ->assertSee('Database Student')
            ->assertSee('Database Notification')
            ->assertDontSee('Juan Dela Cruz')
            ->assertDontSee('AI-Powered Traffic Management System')
            ->assertDontSee('Chapter 3 - Methodology');

        $this->actingAs($adviser)
            ->get(route('adviser.dashboard', ['tab' => 'docreview']))
            ->assertOk()
            ->assertSee("activeTab: 'docreview'", false)
            ->assertSee($document->original_filename);

        $this->actingAs($adviser)
            ->get(route('adviser.dashboard', ['tab' => 'evaluations']))
            ->assertOk()
            ->assertSee("activeTab: 'evaluations'", false)
            ->assertSee('Evaluation Records')
            ->assertDontSee('Evaluation records backend will be rebuilt in the evaluation phase.');
    }

    private function enroll(User $adviser, User $student): void
    {
        $facilitator = User::factory()->create();
        $facilitator->assignRole('research-facilitator');
        $researchClass = new ResearchClass([
            'facilitator_id' => $facilitator->getKey(),
            'creation_token' => (string) Str::uuid(),
            'name' => 'Database Research Class',
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
            'name' => 'Database Capstone Group',
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
        $group = ResearchClassGroup::query()->first();

        return Document::query()->create([
            'user_id' => $student->getKey(),
            'research_class_group_id' => $group?->getKey(),
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
