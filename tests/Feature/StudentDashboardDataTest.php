<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Modules\ResearchProgress\Queries\GetResearchGroupProgress;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentDashboardDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        Schema::disableForeignKeyConstraints();

        foreach (['research_milestones', 'consultation_requests', 'adviser_assignments', 'research_projects', 'research_group_members', 'research_groups', 'student_profiles'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        Schema::create('student_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->unsignedBigInteger('program_id')->nullable();
            $table->string('student_number');
            $table->unsignedSmallInteger('year_level')->nullable();
        });

        Schema::create('research_group_members', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('research_group_id');
            $table->foreignId('student_profile_id');
            $table->string('member_role')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
        });

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

    public function test_dashboard_displays_only_the_authenticated_students_research_data(): void
    {
        $student = $this->student('Authenticated Student');
        $otherStudent = $this->student('Other Student');

        $this->attachProject(
            $student,
            10,
            'Authenticated Student Research',
            'Only the authenticated student should read this abstract.',
        );
        $this->attachProject(
            $otherStudent,
            20,
            'Other Student Private Research',
            'This record must not be disclosed.',
        );

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Authenticated Student Research')
            ->assertSee('Only the authenticated student should read this abstract.')
            ->assertDontSee('Other Student Private Research')
            ->assertDontSee('Machine Learning Applications in Agricultural Pest Detection')
            ->assertDontSee('Maria Santos');
    }

    public function test_dashboard_uses_empty_states_when_student_has_no_research_records(): void
    {
        $student = $this->student('New Student');

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('No research project is associated with your account yet.');

        $this->actingAs($student)
            ->get(route('student.dashboard', ['tab' => 'repository']))
            ->assertOk()
            ->assertSee('No documents match your current repository view.');
    }

    public function test_dashboard_renders_uploaded_file_size_and_restored_sidebar_items(): void
    {
        $student = $this->student('Student With Document');
        $group = $this->activeClassGroupFor($student);

        Document::query()->create([
            'user_id' => $student->getKey(),
            'research_class_group_id' => $group->getKey(),
            'submission_token' => (string) Str::uuid(),
            'original_filename' => 'research-paper.pdf',
            'stored_filename' => Str::uuid().'.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'storage_disk' => 'local',
            'storage_path' => 'documents/test/research-paper.pdf',
            'content_sha256' => str_repeat('a', 64),
            'submitted_at' => now(),
            'status' => 'pending',
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertViewHas('dashboardOverview', fn (array $overview): bool => $overview['document_count'] === 1
                && $overview['pending_document_count'] === 1)
            ->assertSee('2.0 KB')
            ->assertSee('Official Forms')
            ->assertSee('Book Consultation')
            ->assertSee('Notifications')
            ->assertSee('Settings');

        $this->actingAs($student)
            ->get(route('student.dashboard', ['dashboard_q' => 'research-paper']))
            ->assertOk()
            ->assertViewHas(
                'dashboardSearchResults',
                fn ($results): bool => $results->count() === 1
                    && $results->first()['title'] === 'research-paper.pdf',
            )
            ->assertSee('Search results for')
            ->assertSee('research-paper.pdf');
    }

    public function test_student_feature_tabs_only_load_their_required_document_data(): void
    {
        $student = $this->student('Tab Scoped Student');
        $group = $this->activeClassGroupFor($student);

        Document::query()->create([
            'user_id' => $student->getKey(),
            'research_class_group_id' => $group->getKey(),
            'submission_token' => (string) Str::uuid(),
            'original_filename' => 'tab-scoped-paper.pdf',
            'stored_filename' => Str::uuid().'.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'storage_disk' => 'local',
            'storage_path' => 'documents/test/tab-scoped-paper.pdf',
            'content_sha256' => str_repeat('b', 64),
            'submitted_at' => now(),
            'status' => 'pending',
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard', ['tab' => 'classes']))
            ->assertOk()
            ->assertViewHas('activeDashboardTab', 'classes')
            ->assertViewHas('documents', fn ($documents): bool => $documents->isEmpty());

        $this->actingAs($student)
            ->get(route('student.dashboard', ['tab' => 'repository']))
            ->assertOk()
            ->assertViewHas('activeDashboardTab', 'repository')
            ->assertViewHas('documents', fn ($documents): bool => $documents->count() === 1)
            ->assertSee('tab-scoped-paper.pdf');
    }

    public function test_dashboard_calculates_progress_from_real_milestone_records(): void
    {
        $student = $this->student('Milestone Student');
        $classGroup = $this->activeClassGroupFor($student);

        DB::table('research_groups')->insert([
            'id' => 30,
        ]);

        $this->attachProject(
            $student,
            30,
            'Milestone Based Research',
            'Progress is calculated from database milestones.',
        );

        $milestones = app(GetResearchGroupProgress::class)->for($classGroup)['milestones'];
        $milestones->first()->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => $classGroup->researchClass->facilitator_id,
        ]);
        $milestones->get(1)->update(['due_at' => now()->subDay()]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertViewHas('dashboardOverview', fn (array $overview): bool => $overview['progress_percentage'] === 8
                && $overview['completed_milestones'] === 1
                && $overview['total_milestones'] === 13
                && $overview['urgent_task_count'] === 12)
            ->assertSee('1 of 13 milestones')
            ->assertSee($milestones->first()->definition->name)
            ->assertSee($milestones->get(1)->definition->name);
    }

    private function student(string $name): User
    {
        $student = User::factory()->create(['name' => $name]);
        $student->assignRole('student-researcher');

        return $student;
    }

    private function activeClassGroupFor(User $student): ResearchClassGroup
    {
        if (! Schema::hasTable('research_groups')) {
            Schema::create('research_groups', function (Blueprint $table): void {
                $table->id();
            });
        }

        $facilitator = User::factory()->create();
        $facilitator->assignRole('research-facilitator');

        $researchClass = new ResearchClass([
            'facilitator_id' => $facilitator->getKey(),
            'creation_token' => (string) Str::uuid(),
            'name' => 'Dashboard Class '.Str::random(8),
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
            'name' => 'Dashboard Group '.Str::random(8),
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

    private function attachProject(User $student, int $groupId, string $title, string $abstract): int
    {
        $profileId = DB::table('student_profiles')->insertGetId([
            'user_id' => $student->getKey(),
            'student_number' => 'STU-'.$student->getKey(),
        ]);

        DB::table('research_group_members')->insert([
            'research_group_id' => $groupId,
            'student_profile_id' => $profileId,
            'member_role' => 'researcher',
            'joined_at' => now(),
        ]);

        return DB::table('research_projects')->insertGetId([
            'research_group_id' => $groupId,
            'title' => $title,
            'abstract' => $abstract,
            'keywords' => json_encode(['security', 'research'], JSON_THROW_ON_ERROR),
            'category' => 'thesis',
            'status' => 'in_progress',
            'created_by' => $student->getKey(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
