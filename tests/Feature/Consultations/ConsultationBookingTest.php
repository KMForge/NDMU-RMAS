<?php

namespace Tests\Feature\Consultations;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->createResearchTables();
    }

    public function test_guest_cannot_book_a_consultation(): void
    {
        $this->post(route('student.consultations.store'), $this->validPayload())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('consultation_requests', 0);
    }

    public function test_student_with_project_and_adviser_can_book_a_consultation(): void
    {
        [$student, $projectId, $assignmentId] = $this->studentWithProjectAndAdviser();

        $this->actingAs($student)
            ->postJson(route('student.consultations.store'), [
                ...$this->validPayload(),
                'agenda' => 'Discuss the methodology <script>alert(1)</script>',
                'research_project_id' => 999999,
                'adviser_assignment_id' => 999999,
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Consultation request submitted successfully.')
            ->assertJsonPath('consultation.status', 'pending')
            ->assertJsonMissingPath('consultation.adviser_assignment_id');

        $this->assertDatabaseHas('consultation_requests', [
            'research_project_id' => $projectId,
            'adviser_assignment_id' => $assignmentId,
            'requested_by' => $student->getKey(),
            'consultation_mode' => 'online',
            'agenda' => 'Discuss the methodology alert(1)',
            'status' => 'pending',
        ]);
    }

    public function test_student_without_a_project_gets_a_safe_validation_response(): void
    {
        $student = $this->student();

        $this->actingAs($student)
            ->postJson(route('student.consultations.store'), $this->validPayload())
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'You need an active research project before booking a consultation.',
            ]);

        $this->assertDatabaseCount('consultation_requests', 0);
    }

    public function test_student_without_an_active_adviser_cannot_book(): void
    {
        [$student] = $this->studentWithProjectAndAdviser('ended');

        $this->actingAs($student)
            ->postJson(route('student.consultations.store'), $this->validPayload())
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'No active adviser is assigned to your research project.',
            ]);
    }

    public function test_duplicate_booking_token_cannot_create_two_requests(): void
    {
        [$student] = $this->studentWithProjectAndAdviser();
        $payload = $this->validPayload();

        $this->actingAs($student)
            ->postJson(route('student.consultations.store'), $payload)
            ->assertCreated();

        $this->actingAs($student)
            ->postJson(route('student.consultations.store'), $payload)
            ->assertConflict();

        $this->assertDatabaseCount('consultation_requests', 1);
    }

    public function test_missing_permission_is_rejected(): void
    {
        [$student] = $this->studentWithProjectAndAdviser();
        Role::findByName('student-researcher')->syncPermissions([
            'dashboards.student.view',
            'research.view-own',
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($student)
            ->postJson(route('student.consultations.store'), $this->validPayload())
            ->assertForbidden()
            ->assertExactJson([
                'message' => 'You do not have permission to book consultations.',
            ]);
    }

    public function test_past_schedule_and_unsupported_mode_are_rejected(): void
    {
        [$student] = $this->studentWithProjectAndAdviser();

        $this->actingAs($student)
            ->postJson(route('student.consultations.store'), [
                ...$this->validPayload(),
                'preferred_at' => Carbon::now('Asia/Manila')->subHour()->format('Y-m-d\TH:i'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('preferred_at');

        $this->actingAs($student)
            ->postJson(route('student.consultations.store'), [
                ...$this->validPayload(),
                'consultation_mode' => 'external_link',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('consultation_mode');
    }

    public function test_booking_rate_limiter_rejects_the_sixth_request(): void
    {
        [$student] = $this->studentWithProjectAndAdviser();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->actingAs($student)
                ->postJson(route('student.consultations.store'), $this->validPayload())
                ->assertCreated();
        }

        $this->actingAs($student)
            ->postJson(route('student.consultations.store'), $this->validPayload())
            ->assertTooManyRequests();

        $this->assertDatabaseCount('consultation_requests', 5);
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'request_token' => (string) Str::uuid(),
            'preferred_at' => Carbon::now('Asia/Manila')->addDays(2)->format('Y-m-d\TH:i'),
            'consultation_mode' => 'online',
            'agenda' => 'Discuss the current research methodology.',
        ];
    }

    private function student(): User
    {
        $student = User::factory()->create();
        $student->assignRole('student-researcher');

        return $student;
    }

    /**
     * @return array{User, int, int}
     */
    private function studentWithProjectAndAdviser(string $assignmentStatus = 'active'): array
    {
        $student = $this->student();
        $adviser = User::factory()->create();

        $profileId = DB::table('student_profiles')->insertGetId([
            'user_id' => $student->getKey(),
            'student_number' => 'STU-'.$student->getKey(),
        ]);

        DB::table('research_group_members')->insert([
            'research_group_id' => 100 + $student->getKey(),
            'student_profile_id' => $profileId,
            'member_role' => 'researcher',
            'joined_at' => now(),
        ]);

        $projectId = DB::table('research_projects')->insertGetId([
            'research_group_id' => 100 + $student->getKey(),
            'title' => 'Owned Research Project',
            'status' => 'in_progress',
            'created_by' => $student->getKey(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $facultyId = DB::table('faculty_profiles')->insertGetId([
            'user_id' => $adviser->getKey(),
        ]);

        $assignmentId = DB::table('adviser_assignments')->insertGetId([
            'research_project_id' => $projectId,
            'adviser_id' => $facultyId,
            'status' => $assignmentStatus,
            'assigned_at' => now(),
            'ended_at' => $assignmentStatus === 'active' ? null : now(),
        ]);

        return [$student, $projectId, $assignmentId];
    }

    private function createResearchTables(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (['consultation_requests', 'adviser_assignments', 'research_projects', 'research_group_members', 'student_profiles', 'faculty_profiles'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        Schema::create('student_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('student_number');
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
            $table->string('status');
            $table->foreignId('created_by');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('faculty_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
        });

        Schema::create('adviser_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_project_id');
            $table->foreignId('adviser_id');
            $table->string('status');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('ended_at')->nullable();
        });

        $this->createConsultationRequestsTable();
    }

    private function createConsultationRequestsTable(): void
    {
        Schema::create('consultation_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_project_id');
            $table->foreignId('adviser_assignment_id');
            $table->foreignId('requested_by');
            $table->uuid('request_token');
            $table->timestamp('preferred_at');
            $table->string('consultation_mode', 20);
            $table->text('agenda');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->unique(['requested_by', 'request_token']);
        });
    }
}
