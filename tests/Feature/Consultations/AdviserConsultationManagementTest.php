<?php

namespace Tests\Feature\Consultations;

use App\Models\ConsultationRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdviserConsultationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->createResearchTables();
    }

    public function test_adviser_sees_only_requests_for_their_active_assignments(): void
    {
        $adviser = $this->adviser();
        $otherAdviser = $this->adviser();
        $student = $this->student('Visible Student');
        $hiddenStudent = $this->student('Hidden Student');

        $visibleRequest = $this->consultationRequest($student, $adviser, 'Visible Research');
        $this->consultationRequest($hiddenStudent, $otherAdviser, 'Hidden Research');

        $this->actingAs($adviser)
            ->get(route('adviser.dashboard', [
                'tab' => 'consultation',
                'consultation_status' => 'all',
            ]))
            ->assertOk()
            ->assertViewHas('consultationStats', [
                'pending' => 1,
                'approved' => 0,
                'completed' => 0,
                'rejected' => 0,
                'total' => 1,
            ])
            ->assertSee('Visible Student')
            ->assertSee('Visible Research')
            ->assertDontSee('Hidden Student')
            ->assertSee(route('adviser.consultations.approve', $visibleRequest));
    }

    public function test_assigned_adviser_can_approve_a_pending_request(): void
    {
        $adviser = $this->adviser();
        $request = $this->consultationRequest(
            $this->student('Approval Student'),
            $adviser,
            'Approval Research',
        );

        $this->actingAs($adviser)
            ->patchJson(route('adviser.consultations.approve', $request), [
                'review_notes' => 'Confirmed <script>alert(1)</script>',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Consultation request approved and scheduled.')
            ->assertJsonPath('consultation.status', 'approved')
            ->assertJsonMissingPath('consultation.adviser_assignment_id');

        $this->assertDatabaseHas('consultation_requests', [
            'id' => $request->getKey(),
            'status' => 'approved',
            'reviewed_by' => $adviser->getKey(),
            'review_notes' => 'Confirmed alert(1)',
        ]);

        $this->assertNotNull($request->fresh()->reviewed_at);
    }

    public function test_assigned_adviser_can_reject_with_a_sanitized_reason(): void
    {
        $adviser = $this->adviser();
        $request = $this->consultationRequest(
            $this->student('Rejected Student'),
            $adviser,
            'Rejected Research',
        );

        $this->actingAs($adviser)
            ->patchJson(route('adviser.consultations.reject', $request), [
                'review_notes' => '<b>Schedule conflict</b>',
            ])
            ->assertOk()
            ->assertJsonPath('consultation.status', 'rejected');

        $this->assertDatabaseHas('consultation_requests', [
            'id' => $request->getKey(),
            'status' => 'rejected',
            'review_notes' => 'Schedule conflict',
        ]);
    }

    public function test_adviser_cannot_review_another_advisers_request(): void
    {
        $assignedAdviser = $this->adviser();
        $otherAdviser = $this->adviser();
        $request = $this->consultationRequest(
            $this->student('Protected Student'),
            $assignedAdviser,
            'Protected Research',
        );

        $this->actingAs($otherAdviser)
            ->patchJson(route('adviser.consultations.approve', $request))
            ->assertForbidden();

        $this->assertDatabaseHas('consultation_requests', [
            'id' => $request->getKey(),
            'status' => 'pending',
            'reviewed_by' => null,
        ]);
    }

    public function test_a_reviewed_request_cannot_be_reviewed_twice(): void
    {
        $adviser = $this->adviser();
        $request = $this->consultationRequest(
            $this->student('Idempotent Student'),
            $adviser,
            'Idempotent Research',
        );

        $this->actingAs($adviser)
            ->patchJson(route('adviser.consultations.approve', $request))
            ->assertOk();

        $this->actingAs($adviser)
            ->patchJson(route('adviser.consultations.reject', $request))
            ->assertConflict()
            ->assertExactJson([
                'message' => 'This consultation request has already been reviewed.',
            ]);

        $this->assertDatabaseHas('consultation_requests', [
            'id' => $request->getKey(),
            'status' => 'approved',
        ]);
    }

    public function test_adviser_can_record_an_approved_consultation_once(): void
    {
        $adviser = $this->adviser();
        $request = $this->consultationRequest(
            $this->student('Completed Student'),
            $adviser,
            'Completed Research',
        );
        $request->update([
            'status' => 'approved',
            'reviewed_by' => $adviser->getKey(),
            'reviewed_at' => now()->subHour(),
        ]);

        $payload = [
            'consulted_at' => now(config('ndmu-rmas.timezone'))->subMinutes(10)->format('Y-m-d\TH:i'),
            'location' => 'Room 304 <script>alert(1)</script>',
            'meeting_url' => null,
            'discussion' => 'Reviewed the methodology and current findings.',
            'recommendations' => '<b>Revise the sampling procedure.</b>',
            'next_consultation_at' => now(config('ndmu-rmas.timezone'))->addWeek()->format('Y-m-d\TH:i'),
        ];

        $this->actingAs($adviser)
            ->postJson(route('adviser.consultations.complete', $request), $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Consultation completed and recorded successfully.')
            ->assertJsonPath('consultation_record.status', 'completed');

        $this->assertDatabaseHas('consultation_records', [
            'research_project_id' => $request->research_project_id,
            'adviser_assignment_id' => $request->adviser_assignment_id,
            'conducted_by' => $adviser->getKey(),
            'location' => 'Room 304 alert(1)',
            'discussion' => 'Reviewed the methodology and current findings.',
            'recommendations' => 'Revise the sampling procedure.',
        ]);
        $this->assertDatabaseHas('consultation_requests', [
            'id' => $request->getKey(),
            'status' => 'completed',
        ]);

        $this->actingAs($adviser)
            ->postJson(route('adviser.consultations.complete', $request), $payload)
            ->assertConflict()
            ->assertExactJson([
                'message' => 'Only an approved consultation can be marked as completed.',
            ]);

        $this->assertDatabaseCount('consultation_records', 1);
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

    private function consultationRequest(
        User $student,
        User $adviser,
        string $researchTitle,
    ): ConsultationRequest {
        $projectId = DB::table('research_projects')->insertGetId([
            'title' => $researchTitle,
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
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        return ConsultationRequest::query()->create([
            'research_project_id' => $projectId,
            'adviser_assignment_id' => $assignmentId,
            'requested_by' => $student->getKey(),
            'request_token' => (string) Str::uuid(),
            'preferred_at' => now()->addDays(2),
            'consultation_mode' => 'online',
            'agenda' => 'Discuss the current research methodology.',
            'status' => 'pending',
        ]);
    }

    private function createResearchTables(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (['consultation_records', 'consultation_requests', 'adviser_assignments', 'research_projects', 'faculty_profiles'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        Schema::create('research_projects', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
            $table->foreignId('created_by');
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

        Schema::create('consultation_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_project_id');
            $table->foreignId('adviser_assignment_id')->nullable();
            $table->foreignId('conducted_by')->nullable();
            $table->timestamp('consulted_at');
            $table->string('consultation_mode', 30);
            $table->string('location')->nullable();
            $table->text('meeting_url')->nullable();
            $table->text('agenda')->nullable();
            $table->text('discussion')->nullable();
            $table->text('recommendations')->nullable();
            $table->timestamp('next_consultation_at')->nullable();
            $table->timestamps();
        });
    }
}
