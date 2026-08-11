<?php

namespace Tests\Feature\Consultations;

use App\Enums\ConsultationMode;
use App\Enums\ConsultationStatus;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\ConsultationRecord;
use App\Models\ConsultationRequest;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\ResearchGroupMilestone;
use App\Models\ResearchGroupMilestoneEvent;
use App\Models\RevisionRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ConsultationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $facilitator;

    protected User $adviser;

    protected User $studentRequester;

    protected User $studentPeer;

    protected User $outsiderStudent;

    protected ResearchClass $researchClass;

    protected ResearchClassGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::findOrCreate('consultations.request');
        Permission::findOrCreate('consultations.manage-assigned');
        Permission::findOrCreate('dashboards.student.view');
        Permission::findOrCreate('dashboards.adviser.view');
        Permission::findOrCreate('dashboards.facilitator.view');

        $ay = AcademicYear::query()->forceCreate([
            'name' => '2025-2026',
            'starts_at' => now()->startOfYear(),
            'ends_at' => now()->endOfYear(),
            'is_current' => true,
        ]);
        $term = AcademicTerm::query()->forceCreate([
            'academic_year_id' => $ay->id,
            'name' => 'First Semester',
            'starts_at' => now()->startOfYear(),
            'ends_at' => now()->endOfYear(),
            'is_current' => true,
        ]);

        $this->facilitator = User::factory()->create(['user_type' => 'faculty', 'email_verified_at' => now()]);
        $this->facilitator->givePermissionTo(['dashboards.facilitator.view']);

        $this->adviser = User::factory()->create(['user_type' => 'faculty', 'email_verified_at' => now()]);
        $this->adviser->givePermissionTo(['dashboards.adviser.view', 'consultations.manage-assigned']);

        $this->studentRequester = User::factory()->create(['user_type' => 'student', 'email_verified_at' => now()]);
        $this->studentRequester->givePermissionTo(['dashboards.student.view', 'consultations.request']);

        $this->studentPeer = User::factory()->create(['user_type' => 'student', 'email_verified_at' => now()]);
        $this->studentPeer->givePermissionTo(['dashboards.student.view', 'consultations.request']);

        $this->outsiderStudent = User::factory()->create(['user_type' => 'student', 'email_verified_at' => now()]);
        $this->outsiderStudent->givePermissionTo(['dashboards.student.view', 'consultations.request']);

        $this->researchClass = ResearchClass::query()->forceCreate([
            'facilitator_id' => $this->facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Research Methods Class A',
            'join_code_hash' => hash('sha256', 'JOIN123'),
            'join_code_encrypted' => 'JOIN123',
            'is_active' => true,
        ]);

        $this->group = ResearchClassGroup::query()->forceCreate([
            'research_class_id' => $this->researchClass->id,
            'creation_token' => (string) Str::uuid(),
            'adviser_id' => $this->adviser->id,
            'created_by' => $this->studentRequester->id,
            'leader_student_id' => $this->studentRequester->id,
            'name' => 'Group Alpha',
            'status' => 'active',
        ]);

        $enrollmentRequester = ResearchClassEnrollment::query()->forceCreate([
            'research_class_id' => $this->researchClass->id,
            'student_id' => $this->studentRequester->id,
            'status' => 'enrolled',
        ]);

        $enrollmentPeer = ResearchClassEnrollment::query()->forceCreate([
            'research_class_id' => $this->researchClass->id,
            'student_id' => $this->studentPeer->id,
            'status' => 'enrolled',
        ]);

        ResearchClassGroupMember::query()->forceCreate([
            'research_class_group_id' => $this->group->id,
            'research_class_id' => $this->researchClass->id,
            'research_class_enrollment_id' => $enrollmentRequester->id,
            'student_id' => $this->studentRequester->id,
            'assigned_by' => $this->studentRequester->id,
        ]);

        ResearchClassGroupMember::query()->forceCreate([
            'research_class_group_id' => $this->group->id,
            'research_class_id' => $this->researchClass->id,
            'research_class_enrollment_id' => $enrollmentPeer->id,
            'student_id' => $this->studentPeer->id,
            'assigned_by' => $this->studentRequester->id,
        ]);
    }

    public function test_student_can_book_consultation_with_assigned_adviser(): void
    {
        $preferredAt = now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0);

        $response = $this->actingAs($this->studentRequester)->post(route('student.consultations.store'), [
            'preferred_at' => $preferredAt->toIso8601String(),
            'consultation_mode' => ConsultationMode::InPerson->value,
            'duration_minutes' => 60,
            'agenda' => 'We would like to discuss Chapter 1 methodology and instrumentation.',
        ]);

        $response->assertRedirect(route('student.dashboard', ['tab' => 'consultation']));
        $response->assertSessionHas('consultation_success');

        $this->assertDatabaseHas('consultation_requests', [
            'research_class_group_id' => $this->group->id,
            'assigned_adviser_id' => $this->adviser->id,
            'requested_by' => $this->studentRequester->id,
            'consultation_mode' => 'in_person',
            'status' => 'pending',
        ]);
    }

    public function test_assigned_adviser_can_see_the_consultation_request_on_dashboard(): void
    {
        ConsultationRequest::query()->create([
            'research_class_group_id' => $this->group->id,
            'assigned_adviser_id' => $this->adviser->id,
            'requested_by' => $this->studentRequester->id,
            'request_token' => (string) Str::uuid(),
            'preferred_at' => now()->addDays(2),
            'duration_minutes' => 60,
            'consultation_mode' => ConsultationMode::InPerson,
            'agenda' => 'Review our data gathering instrument before deployment.',
            'status' => ConsultationStatus::Pending,
        ]);

        $this->actingAs($this->adviser)
            ->get(route('adviser.dashboard', ['tab' => 'consultation']))
            ->assertOk()
            ->assertSee('Review our data gathering instrument before deployment.')
            ->assertSee($this->studentRequester->name)
            ->assertSee($this->group->name);
    }

    public function test_student_cannot_book_consultation_if_group_has_no_adviser(): void
    {
        $this->group->update(['adviser_id' => null]);
        $preferredAt = now()->addDays(2)->setHour(10)->setMinute(0);

        $response = $this->actingAs($this->studentRequester)->post(route('student.consultations.store'), [
            'preferred_at' => $preferredAt->toIso8601String(),
            'consultation_mode' => ConsultationMode::InPerson->value,
            'duration_minutes' => 60,
            'agenda' => 'Discussion regarding title proposal outline.',
        ]);

        $response->assertRedirect(route('student.dashboard', ['tab' => 'consultation']));
        $response->assertSessionHasErrors('consultation');
    }

    public function test_student_cannot_book_outside_allowed_window(): void
    {
        // Less than 6 hours advance notice
        $tooSoon = now()->addHours(2);
        $response = $this->actingAs($this->studentRequester)->post(route('student.consultations.store'), [
            'preferred_at' => $tooSoon->toIso8601String(),
            'consultation_mode' => ConsultationMode::InPerson->value,
            'agenda' => 'Emergency discussion regarding thesis topic.',
        ]);

        $response->assertRedirect(route('student.dashboard', ['tab' => 'consultation']));
        $response->assertSessionHasErrors('consultation');
    }

    public function test_student_cannot_book_if_unresolved_request_exists(): void
    {
        ConsultationRequest::query()->create([
            'research_class_group_id' => $this->group->id,
            'assigned_adviser_id' => $this->adviser->id,
            'requested_by' => $this->studentRequester->id,
            'request_token' => (string) Str::uuid(),
            'preferred_at' => now()->addDays(2),
            'duration_minutes' => 60,
            'consultation_mode' => 'in_person',
            'agenda' => 'Existing active request agenda.',
            'status' => ConsultationStatus::Pending,
        ]);

        $response = $this->actingAs($this->studentRequester)->post(route('student.consultations.store'), [
            'preferred_at' => now()->addDays(3)->toIso8601String(),
            'consultation_mode' => ConsultationMode::InPerson->value,
            'agenda' => 'Second booking attempt.',
        ]);

        $response->assertRedirect(route('student.dashboard', ['tab' => 'consultation']));
        $response->assertSessionHasErrors('consultation');
    }

    public function test_all_active_group_members_can_view_group_consultations(): void
    {
        $req = ConsultationRequest::query()->create([
            'research_class_group_id' => $this->group->id,
            'assigned_adviser_id' => $this->adviser->id,
            'requested_by' => $this->studentRequester->id,
            'request_token' => (string) Str::uuid(),
            'preferred_at' => now()->addDays(2),
            'duration_minutes' => 60,
            'consultation_mode' => 'in_person',
            'agenda' => 'Group consultation agenda item.',
            'status' => ConsultationStatus::Pending,
        ]);

        $response = $this->actingAs($this->studentPeer)->get(route('student.dashboard', ['tab' => 'consultation']));
        $response->assertOk();
        $response->assertSee('Group consultation agenda item.');
    }

    public function test_only_original_requester_can_cancel_or_respond_to_reschedule(): void
    {
        $req = ConsultationRequest::query()->create([
            'research_class_group_id' => $this->group->id,
            'assigned_adviser_id' => $this->adviser->id,
            'requested_by' => $this->studentRequester->id,
            'request_token' => (string) Str::uuid(),
            'preferred_at' => now()->addDays(2),
            'duration_minutes' => 60,
            'consultation_mode' => 'in_person',
            'agenda' => 'Original request agenda.',
            'status' => ConsultationStatus::Pending,
        ]);

        // Peer student attempts to cancel
        $response = $this->actingAs($this->studentPeer)->post(route('student.consultations.cancel', ['consultationRequest' => $req->id]), [
            'reason' => 'Unauthorized cancellation.',
        ]);
        $response->assertForbidden();

        // Original requester cancels
        $response2 = $this->actingAs($this->studentRequester)->post(route('student.consultations.cancel', ['consultationRequest' => $req->id]), [
            'reason' => 'Schedule conflict resolved.',
        ]);
        $response2->assertRedirect(route('student.dashboard', ['tab' => 'consultation']));
        $response2->assertSessionHas('consultation_success');

        $this->assertDatabaseHas('consultation_requests', [
            'id' => $req->id,
            'status' => 'cancelled',
            'cancelled_by' => $this->studentRequester->id,
        ]);
    }

    public function test_adviser_can_approve_consultation_without_overlap(): void
    {
        $startAt = now()->addDays(2)->setHour(14)->setMinute(0)->setSecond(0);

        $req = ConsultationRequest::query()->create([
            'research_class_group_id' => $this->group->id,
            'assigned_adviser_id' => $this->adviser->id,
            'requested_by' => $this->studentRequester->id,
            'request_token' => (string) Str::uuid(),
            'preferred_at' => $startAt,
            'duration_minutes' => 60,
            'consultation_mode' => 'in_person',
            'agenda' => 'Title proposal review agenda.',
            'status' => ConsultationStatus::Pending,
        ]);

        $response = $this->actingAs($this->adviser)->post(route('adviser.consultations.approve', ['consultationRequest' => $req->id]), [
            'confirmed_start_at' => $startAt->toIso8601String(),
            'duration_minutes' => 60,
            'location' => 'Building A Room 302',
        ]);

        $response->assertRedirect(route('adviser.dashboard', ['tab' => 'consultation']));
        $response->assertSessionHas('consultation_success');

        $this->assertDatabaseHas('consultation_requests', [
            'id' => $req->id,
            'status' => 'approved',
            'reviewed_by' => $this->adviser->id,
            'location' => 'Building A Room 302',
        ]);
    }

    public function test_adviser_can_publish_online_meeting_details_after_approval_without_changing_agenda(): void
    {
        $agenda = 'Review the group data gathering plan.';
        $request = ConsultationRequest::query()->create([
            'research_class_group_id' => $this->group->id,
            'assigned_adviser_id' => $this->adviser->id,
            'requested_by' => $this->studentRequester->id,
            'request_token' => (string) Str::uuid(),
            'preferred_at' => now()->addDays(2),
            'confirmed_start_at' => now()->addDays(2),
            'confirmed_end_at' => now()->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'consultation_mode' => ConsultationMode::InPerson,
            'agenda' => $agenda,
            'status' => ConsultationStatus::Approved,
        ]);

        $meetingUrl = 'https://meet.example.edu/group-alpha';

        $this->actingAs($this->adviser)
            ->patch(route('adviser.consultations.meeting-details.update', $request), [
                'consultation_mode' => ConsultationMode::Online->value,
                'meeting_url' => $meetingUrl,
            ])
            ->assertRedirect(route('adviser.dashboard', [
                'tab' => 'consultation',
                'consultation_status' => 'approved',
            ]));

        $this->assertDatabaseHas('consultation_requests', [
            'id' => $request->id,
            'consultation_mode' => ConsultationMode::Online->value,
            'meeting_url' => $meetingUrl,
            'agenda' => $agenda,
        ]);

        $this->actingAs($this->studentRequester)
            ->get(route('student.dashboard', ['tab' => 'consultation']))
            ->assertOk()
            ->assertSee($agenda)
            ->assertSee($meetingUrl)
            ->assertDontSee('Reply');
    }

    public function test_adviser_cannot_approve_overlapping_schedule(): void
    {
        $existingStart = CarbonImmutable::parse(now()->addDays(2)->setHour(14)->setMinute(0)->setSecond(0));
        $existingEnd = $existingStart->addMinutes(60);

        // Existing approved consultation for adviser
        ConsultationRequest::query()->create([
            'research_class_group_id' => $this->group->id,
            'assigned_adviser_id' => $this->adviser->id,
            'requested_by' => $this->studentRequester->id,
            'request_token' => (string) Str::uuid(),
            'preferred_at' => $existingStart,
            'confirmed_start_at' => $existingStart,
            'confirmed_end_at' => $existingEnd,
            'duration_minutes' => 60,
            'consultation_mode' => 'in_person',
            'agenda' => 'Existing approved consultation.',
            'status' => ConsultationStatus::Approved,
        ]);

        // Second group request
        $group2 = ResearchClassGroup::query()->forceCreate([
            'research_class_id' => $this->researchClass->id,
            'creation_token' => (string) Str::uuid(),
            'adviser_id' => $this->adviser->id,
            'created_by' => $this->studentPeer->id,
            'leader_student_id' => $this->studentPeer->id,
            'name' => 'Group Beta',
            'status' => 'active',
        ]);

        $req2 = ConsultationRequest::query()->create([
            'research_class_group_id' => $group2->id,
            'assigned_adviser_id' => $this->adviser->id,
            'requested_by' => $this->studentPeer->id,
            'request_token' => (string) Str::uuid(),
            'preferred_at' => $existingStart->addMinutes(30), // Overlapping!
            'duration_minutes' => 60,
            'consultation_mode' => 'in_person',
            'agenda' => 'Overlapping request agenda.',
            'status' => ConsultationStatus::Pending,
        ]);

        $response = $this->actingAs($this->adviser)->post(route('adviser.consultations.approve', ['consultationRequest' => $req2->id]), [
            'confirmed_start_at' => $existingStart->addMinutes(30)->toIso8601String(),
            'duration_minutes' => 60,
        ]);

        $response->assertRedirect(route('adviser.dashboard', ['tab' => 'consultation']));
        $response->assertSessionHasErrors('consultation');
    }

    public function test_adviser_reschedule_proposal_preserves_history(): void
    {
        $req = ConsultationRequest::query()->create([
            'research_class_group_id' => $this->group->id,
            'assigned_adviser_id' => $this->adviser->id,
            'requested_by' => $this->studentRequester->id,
            'request_token' => (string) Str::uuid(),
            'preferred_at' => now()->addDays(2),
            'duration_minutes' => 60,
            'consultation_mode' => 'in_person',
            'agenda' => 'Title defense discussion.',
            'status' => ConsultationStatus::Pending,
        ]);

        $propStart1 = now()->addDays(3)->setHour(10)->setMinute(0);
        $this->actingAs($this->adviser)->post(route('adviser.consultations.propose-reschedule', ['consultationRequest' => $req->id]), [
            'proposed_start_at' => $propStart1->toIso8601String(),
            'duration_minutes' => 60,
            'reason' => 'Faculty meeting during original time.',
        ]);

        // Student declines Proposal 1
        $this->actingAs($this->studentRequester)->post(route('student.consultations.respond', ['consultationRequest' => $req->id]), [
            'action' => 'decline',
        ]);

        // Adviser proposes Proposal 2
        $propStart2 = now()->addDays(4)->setHour(14)->setMinute(0);
        $this->actingAs($this->adviser)->post(route('adviser.consultations.propose-reschedule', ['consultationRequest' => $req->id]), [
            'proposed_start_at' => $propStart2->toIso8601String(),
            'duration_minutes' => 60,
            'reason' => 'Alternative afternoon slot.',
        ]);

        // Student accepts Proposal 2
        $this->actingAs($this->studentRequester)->post(route('student.consultations.respond', ['consultationRequest' => $req->id]), [
            'action' => 'accept',
        ]);

        $this->assertDatabaseCount('consultation_schedule_proposals', 2);
        $this->assertDatabaseHas('consultation_requests', [
            'id' => $req->id,
            'status' => 'approved',
            'confirmed_start_at' => $propStart2,
        ]);
    }

    public function test_adviser_can_reject_consultation_with_reason(): void
    {
        $req = ConsultationRequest::query()->create([
            'research_class_group_id' => $this->group->id,
            'assigned_adviser_id' => $this->adviser->id,
            'requested_by' => $this->studentRequester->id,
            'request_token' => (string) Str::uuid(),
            'preferred_at' => now()->addDays(2),
            'duration_minutes' => 60,
            'consultation_mode' => 'in_person',
            'agenda' => 'Unclear agenda submission.',
            'status' => ConsultationStatus::Pending,
        ]);

        $response = $this->actingAs($this->adviser)->post(route('adviser.consultations.reject', ['consultationRequest' => $req->id]), [
            'reason' => 'Please provide a more detailed agenda before requesting consultation.',
        ]);

        $response->assertRedirect(route('adviser.dashboard', ['tab' => 'consultation']));
        $response->assertSessionHas('consultation_success');

        $this->assertDatabaseHas('consultation_requests', [
            'id' => $req->id,
            'status' => 'rejected',
            'review_notes' => 'Please provide a more detailed agenda before requesting consultation.',
        ]);
    }

    public function test_adviser_can_record_completed_consultation_with_attendees(): void
    {
        $req = ConsultationRequest::query()->create([
            'research_class_group_id' => $this->group->id,
            'assigned_adviser_id' => $this->adviser->id,
            'requested_by' => $this->studentRequester->id,
            'request_token' => (string) Str::uuid(),
            'preferred_at' => now()->addDays(2),
            'confirmed_start_at' => now()->addDays(2),
            'confirmed_end_at' => now()->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'consultation_mode' => 'in_person',
            'agenda' => 'Chapter 2 Literature Review Review.',
            'status' => ConsultationStatus::Approved,
        ]);

        $response = $this->actingAs($this->adviser)->post(route('adviser.consultations.complete', ['consultationRequest' => $req->id]), [
            'discussion' => 'Reviewed 15 synthesis matrices. Student needs to add 5 recent international studies.',
            'recommendations' => 'Incorporate 2024 IEEE papers.',
            'attendees' => [$this->studentRequester->id, $this->studentPeer->id],
        ]);

        $response->assertRedirect(route('adviser.dashboard', ['tab' => 'consultation']));
        $response->assertSessionHas('consultation_success');

        $this->assertDatabaseHas('consultation_requests', [
            'id' => $req->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('consultation_records', [
            'consultation_request_id' => $req->id,
            'conducted_by' => $this->adviser->id,
            'is_superseded' => false,
        ]);

        $this->assertDatabaseCount('consultation_attendances', 2);
    }

    public function test_adviser_cannot_submit_non_group_member_attendee(): void
    {
        $req = ConsultationRequest::query()->create([
            'research_class_group_id' => $this->group->id,
            'assigned_adviser_id' => $this->adviser->id,
            'requested_by' => $this->studentRequester->id,
            'request_token' => (string) Str::uuid(),
            'preferred_at' => now()->addDays(2),
            'confirmed_start_at' => now()->addDays(2),
            'confirmed_end_at' => now()->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'consultation_mode' => 'in_person',
            'agenda' => 'Chapter 2 Literature Review.',
            'status' => ConsultationStatus::Approved,
        ]);

        $response = $this->actingAs($this->adviser)->post(route('adviser.consultations.complete', ['consultationRequest' => $req->id]), [
            'discussion' => 'Discussed methodology.',
            'attendees' => [$this->outsiderStudent->id], // Outsider!
        ]);

        $response->assertRedirect(route('adviser.dashboard', ['tab' => 'consultation']));
        $response->assertSessionHasErrors('consultation');
    }

    public function test_adviser_can_correct_completed_consultation_record(): void
    {
        $req = ConsultationRequest::query()->create([
            'research_class_group_id' => $this->group->id,
            'assigned_adviser_id' => $this->adviser->id,
            'requested_by' => $this->studentRequester->id,
            'request_token' => (string) Str::uuid(),
            'preferred_at' => now()->addDays(2),
            'confirmed_start_at' => now()->addDays(2),
            'confirmed_end_at' => now()->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'consultation_mode' => 'in_person',
            'agenda' => 'Initial consultation.',
            'status' => ConsultationStatus::Approved,
        ]);

        $record = ConsultationRecord::query()->create([
            'consultation_request_id' => $req->id,
            'research_class_group_id' => $this->group->id,
            'conducted_by' => $this->adviser->id,
            'consulted_at' => now(),
            'duration_minutes' => 60,
            'consultation_mode' => 'in_person',
            'agenda' => 'Initial consultation.',
            'discussion' => 'Initial discussion notes.',
            'is_superseded' => false,
        ]);

        $response = $this->actingAs($this->adviser)->post(route('adviser.consultations.records.correct', ['record' => $record->id]), [
            'discussion' => 'Corrected discussion notes with detailed recommendations.',
            'correction_reason' => 'Accidentally omitted attendee peer and summary points.',
            'attendees' => [$this->studentRequester->id, $this->studentPeer->id],
        ]);

        $response->assertRedirect(route('adviser.dashboard', ['tab' => 'consultation']));
        $response->assertSessionHas('consultation_success');

        $this->assertDatabaseHas('consultation_records', [
            'id' => $record->id,
            'is_superseded' => true,
        ]);

        $this->assertDatabaseHas('consultation_records', [
            'supersedes_record_id' => $record->id,
            'is_superseded' => false,
            'correction_reason' => 'Accidentally omitted attendee peer and summary points.',
        ]);
    }

    public function test_facilitator_has_read_only_view_of_class_group_consultations(): void
    {
        $req = ConsultationRequest::query()->create([
            'research_class_group_id' => $this->group->id,
            'assigned_adviser_id' => $this->adviser->id,
            'requested_by' => $this->studentRequester->id,
            'request_token' => (string) Str::uuid(),
            'preferred_at' => now()->addDays(2),
            'duration_minutes' => 60,
            'consultation_mode' => 'in_person',
            'agenda' => 'Facilitator monitored agenda.',
            'status' => ConsultationStatus::Pending,
        ]);

        // Facilitator cannot approve adviser consultation
        $response = $this->actingAs($this->facilitator)->post(route('adviser.consultations.approve', ['consultationRequest' => $req->id]), [
            'confirmed_start_at' => now()->addDays(2)->toIso8601String(),
        ]);
        $response->assertForbidden();
    }

    public function test_consultation_completion_has_no_side_effects_on_revisions_or_milestones(): void
    {
        $req = ConsultationRequest::query()->create([
            'research_class_group_id' => $this->group->id,
            'assigned_adviser_id' => $this->adviser->id,
            'requested_by' => $this->studentRequester->id,
            'request_token' => (string) Str::uuid(),
            'preferred_at' => now()->addDays(2),
            'confirmed_start_at' => now()->addDays(2),
            'confirmed_end_at' => now()->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'consultation_mode' => 'in_person',
            'agenda' => 'Chapter 3 methodology review.',
            'status' => ConsultationStatus::Approved,
        ]);

        $this->actingAs($this->adviser)->post(route('adviser.consultations.complete', ['consultationRequest' => $req->id]), [
            'discussion' => 'Verified methodology setup.',
            'attendees' => [$this->studentRequester->id],
        ]);

        $this->assertEquals(0, RevisionRequest::query()->count());
        $this->assertEquals(0, ResearchGroupMilestone::query()->where('status', 'completed')->count());
        $this->assertEquals(0, ResearchGroupMilestoneEvent::query()->count());
    }
}
