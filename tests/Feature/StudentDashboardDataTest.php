<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
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
            ->assertSee('No research project is associated with your account yet.')
            ->assertSee('No documents have been uploaded.');
    }

    public function test_dashboard_renders_uploaded_file_size_and_restored_sidebar_items(): void
    {
        $student = $this->student('Student With Document');

        Document::query()->create([
            'user_id' => $student->getKey(),
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
            ->assertSee('2.0 KB')
            ->assertSee('Official Forms')
            ->assertSee('Book Consultation')
            ->assertSee('Notifications')
            ->assertSee('Settings');
    }

    private function student(string $name): User
    {
        $student = User::factory()->create(['name' => $name]);
        $student->assignRole('student-researcher');

        return $student;
    }

    private function attachProject(User $student, int $groupId, string $title, string $abstract): void
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

        DB::table('research_projects')->insert([
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
