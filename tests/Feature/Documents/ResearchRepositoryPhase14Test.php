<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Models\Defense;
use App\Models\DefensePanelAssignment;
use App\Models\Document;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Modules\Classes\Actions\DisbandResearchClassGroup;
use App\Modules\Documents\Queries\GetDocumentRepositoryData;
use App\Modules\Documents\Queries\GetPanelistAssignedDocuments;
use App\Policies\DocumentPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResearchRepositoryPhase14Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_non_uploader_member_can_browse_current_group_documents_and_unrelated_student_cannot(): void
    {
        [$group, $facilitator, $leader] = $this->group();
        $member = $this->addMember($group, $facilitator);
        $unrelated = $this->student();
        $document = $this->document($leader, $group, 'Current Proposal.pdf');

        $memberData = app(GetDocumentRepositoryData::class)->for($member, []);
        $unrelatedData = app(GetDocumentRepositoryData::class)->for($unrelated, []);

        $this->assertSame([$document->getKey()], $memberData['repositoryDocuments']->pluck('id')->all());
        $this->assertTrue($unrelatedData['repositoryDocuments']->isEmpty());
        $this->assertTrue(app(DocumentPolicy::class)->view($member, $document));
        $this->assertFalse(app(DocumentPolicy::class)->view($unrelated, $document));

        Storage::disk('local')->put($document->storage_path, "%PDF-1.4\n%%EOF\n");
        $this->actingAs($unrelated)->get(route('documents.view', $document))->assertForbidden();
        $this->actingAs($unrelated)->get(route('documents.download', $document))->assertForbidden();
    }

    public function test_disbanded_group_members_retain_historical_read_access_only(): void
    {
        [$group, $facilitator, $leader, $class] = $this->group();
        $formerMember = $this->addMember($group, $facilitator);
        $document = $this->document($leader, $group, 'Historical.pdf');

        app(DisbandResearchClassGroup::class)->handle($facilitator, $class, $group);

        $this->assertDatabaseHas('research_class_group_member_histories', [
            'research_class_group_id' => $group->getKey(),
            'student_id' => $formerMember->getKey(),
        ]);
        $this->assertTrue(app(DocumentPolicy::class)->view($formerMember, $document));
        $this->assertTrue(app(GetDocumentRepositoryData::class)->for($formerMember, [])['repositoryDocuments']->contains($document));

        $outsider = $this->student();
        $this->assertFalse(app(DocumentPolicy::class)->view($outsider, $document));

        [$otherGroup, $otherFacilitator, $otherFormerMember, $otherClass] = $this->group();
        app(DisbandResearchClassGroup::class)->handle($otherFacilitator, $otherClass, $otherGroup);
        $this->assertFalse(app(DocumentPolicy::class)->view($otherFormerMember, $document));
    }

    public function test_repository_filters_search_sort_and_version_state_are_server_side(): void
    {
        [$group, $facilitator, $leader] = $this->group();
        $old = $this->document($leader, $group, 'Proposal Draft.pdf', DocumentStage::ProposalDefense, DocumentStatus::Pending, false, 1, now()->subDay());
        $current = $this->document($leader, $group, 'Proposal Final.pdf', DocumentStage::ProposalDefense, DocumentStatus::Accepted, true, 2, now());
        $this->document($leader, $group, 'Final Manuscript.docx', DocumentStage::FinalManuscript, DocumentStatus::Submitted, true, 1, now()->addMinute(), 'docx');

        $default = app(GetDocumentRepositoryData::class)->for($leader, []);
        $this->assertCount(2, $default['repositoryDocuments']);
        $this->assertFalse($default['repositoryDocuments']->contains($old));

        $filtered = app(GetDocumentRepositoryData::class)->for($leader, [
            'repository_q' => 'proposal',
            'repository_stage' => 'proposal_defense',
            'repository_status' => 'accepted',
            'repository_type' => 'pdf',
            'repository_version' => 'all',
            'repository_sort' => 'oldest',
        ]);
        $this->assertSame([$current->getKey()], $filtered['repositoryDocuments']->pluck('id')->all());

        $malformed = app(GetDocumentRepositoryData::class)->for($leader, [
            'repository_stage' => 'DROP TABLE documents',
            'repository_status' => '<script>',
            'repository_type' => 'exe',
            'repository_version' => 'void-only',
            'repository_sort' => 'random',
        ]);
        $this->assertSame('all', $malformed['repositoryFilters']['stage']);
        $this->assertSame('current', $malformed['repositoryFilters']['version']);
    }

    public function test_assigned_adviser_and_owning_facilitator_have_scoped_access(): void
    {
        $adviser = $this->adviser();
        [$group, $facilitator, $leader] = $this->group($adviser);
        $document = $this->document($leader, $group, 'Assigned.pdf');
        $otherAdviser = $this->adviser();
        $otherFacilitator = $this->facilitator();

        $this->assertTrue(app(DocumentPolicy::class)->view($adviser, $document));
        $this->assertTrue(app(DocumentPolicy::class)->view($facilitator, $document));
        $this->assertFalse(app(DocumentPolicy::class)->view($otherAdviser, $document));
        $this->assertFalse(app(DocumentPolicy::class)->view($otherFacilitator, $document));
    }

    public function test_panelist_has_no_broad_repository_access_without_an_authoritative_assignment(): void
    {
        [$group, $facilitator, $leader] = $this->group();
        $document = $this->document($leader, $group, 'Panel Scope.pdf');
        $panelist = User::factory()->create();
        $panelist->assignRole('panel-member');

        $this->assertFalse(app(DocumentPolicy::class)->view($panelist, $document));
        $this->assertTrue(app(GetDocumentRepositoryData::class)->for($panelist, [])['repositoryDocuments']->isEmpty());
    }

    public function test_active_panelist_can_access_only_the_current_document_matching_the_assigned_defense_stage(): void
    {
        [$group, $facilitator, $leader] = $this->group();
        $proposal = $this->document(
            $leader,
            $group,
            'Assigned Proposal.pdf',
            DocumentStage::ProposalDefense,
            DocumentStatus::Accepted,
        );
        $final = $this->document(
            $leader,
            $group,
            'Unassigned Final.pdf',
            DocumentStage::FinalDefense,
            DocumentStatus::Accepted,
        );
        $panelist = User::factory()->create();
        $panelist->assignRole('panel-member');
        $defense = Defense::query()->create([
            'research_class_group_id' => $group->id,
            'defense_type' => DocumentStage::ProposalDefense->value,
            'status' => 'scheduled',
            'created_by' => $facilitator->id,
        ]);
        $assignment = DefensePanelAssignment::query()->create([
            'defense_id' => $defense->id,
            'user_id' => $panelist->id,
            'panel_position' => 'chairperson',
            'assigned_by' => $facilitator->id,
            'assigned_at' => now(),
        ]);

        $this->assertTrue(app(DocumentPolicy::class)->view($panelist, $proposal));
        $this->assertFalse(app(DocumentPolicy::class)->view($panelist, $final));
        $this->assertSame(
            [$proposal->id],
            app(GetPanelistAssignedDocuments::class)->for($panelist)->pluck('id')->all(),
        );
        $this->assertSame(
            [$proposal->id],
            app(GetDocumentRepositoryData::class)->for($panelist, [])['repositoryDocuments']->pluck('id')->all(),
        );

        $assignment->update(['ended_at' => now()]);

        $this->assertFalse(app(DocumentPolicy::class)->view($panelist, $proposal));
        $this->assertTrue(app(GetPanelistAssignedDocuments::class)->for($panelist)->isEmpty());
    }

    public function test_leader_change_keeps_group_ownership_and_historical_uploader_identity(): void
    {
        [$group, $facilitator, $originalLeader] = $this->group();
        $newLeader = $this->addMember($group, $facilitator);
        $document = $this->document($originalLeader, $group, 'Leader Change.pdf');

        $group->update(['leader_student_id' => $newLeader->getKey()]);
        $document->refresh();

        $this->assertSame($originalLeader->getKey(), $document->user_id);
        $this->assertSame($group->getKey(), $document->research_class_group_id);
        $this->assertTrue(app(DocumentPolicy::class)->view($originalLeader, $document));
        $this->assertTrue(app(DocumentPolicy::class)->view($newLeader, $document));
    }

    public function test_repository_paginates_ten_records_and_preserves_validated_query_state(): void
    {
        [$group, $facilitator, $leader] = $this->group();

        foreach (range(1, 11) as $index) {
            $this->document(
                $leader,
                $group,
                "Proposal {$index}.pdf",
                DocumentStage::ProposalDefense,
                DocumentStatus::Pending,
                false,
                $index,
                now()->addMinutes($index),
            );
        }

        $filters = [
            'repository_q' => 'Proposal',
            'repository_stage' => 'proposal_defense',
            'repository_status' => 'pending',
            'repository_type' => 'pdf',
            'repository_version' => 'all',
            'repository_sort' => 'oldest',
        ];
        $this->app['request']->query->replace($filters);

        $data = app(GetDocumentRepositoryData::class)->for($leader, $filters);

        $documents = $data['repositoryDocuments'];
        $this->assertSame(10, $documents->perPage());
        $this->assertSame(11, $documents->total());
        $this->assertStringContainsString('repository_q=Proposal', (string) $documents->nextPageUrl());
        $this->assertStringContainsString('repository_stage=proposal_defense', (string) $documents->nextPageUrl());
        $this->assertStringContainsString('repository_version=all', (string) $documents->nextPageUrl());
        $this->assertStringContainsString('repository_sort=oldest', (string) $documents->nextPageUrl());
    }

    public function test_admin_access_requires_download_any_permission(): void
    {
        [$group, $facilitator, $leader] = $this->group();
        $document = $this->document($leader, $group, 'Admin Scope.pdf');
        $administrator = User::factory()->create();
        $administrator->assignRole('administrator');
        $limitedAdmin = User::factory()->create();
        $limitedAdmin->givePermissionTo('documents.download');

        $this->assertTrue(app(DocumentPolicy::class)->view($administrator, $document));
        $this->assertFalse(app(DocumentPolicy::class)->view($limitedAdmin, $document));
    }

    public function test_secure_view_download_and_void_history_are_authorized_and_audited(): void
    {
        [$group, $facilitator, $leader] = $this->group();
        $void = $this->document($leader, $group, 'Old Proposal.pdf', DocumentStage::ProposalDefense, DocumentStatus::Pending, false, 1);
        Storage::disk('local')->put($void->storage_path, "%PDF-1.4\n%%EOF\n");

        $this->actingAs($leader)->get(route('documents.view', [$void, 'raw' => 1]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-content-type-options', 'nosniff');
        $this->actingAs($leader)->get(route('documents.download', $void))->assertOk();
        $this->actingAs($leader)->get(route('documents.history', $void))
            ->assertOk()->assertSee('Old Proposal.pdf')->assertSee('VOID');

        $this->assertDatabaseHas('document_access_audits', ['document_id' => $void->id, 'action' => 'viewed']);
        $this->assertDatabaseHas('document_access_audits', ['document_id' => $void->id, 'action' => 'downloaded']);
    }

    public function test_docx_view_uses_metadata_page_and_missing_private_file_is_safe(): void
    {
        [$group, $facilitator, $leader] = $this->group();
        $docx = $this->document($leader, $group, 'Final.docx', DocumentStage::FinalManuscript, DocumentStatus::Pending, true, 1, now(), 'docx');
        Storage::disk('local')->put($docx->storage_path, 'private-docx');

        $this->actingAs($leader)->get(route('documents.view', $docx))
            ->assertOk()->assertSee('Rendering DOCX Document in System')->assertDontSee($docx->storage_path);

        Storage::disk('local')->delete($docx->storage_path);
        $this->actingAs($leader)->get(route('documents.download', $docx))
            ->assertNotFound()->assertDontSee($docx->storage_path);

        $this->assertDatabaseMissing('document_access_audits', [
            'document_id' => $docx->getKey(),
            'action' => 'downloaded',
        ]);

        $this->actingAs($leader)->get('/documents/999999999/view')->assertNotFound();
    }

    /** @return array{ResearchClassGroup, User, User, ResearchClass} */
    private function group(?User $adviser = null): array
    {
        $facilitator = $this->facilitator();
        $leader = $this->student();
        $class = new ResearchClass(['facilitator_id' => $facilitator->id, 'creation_token' => (string) Str::uuid(), 'name' => 'Repository '.Str::random(8), 'max_students' => 50, 'is_active' => true]);
        $class->setJoinCode(Str::upper(Str::random(8)));
        $class->save();
        $group = ResearchClassGroup::query()->create(['research_class_id' => $class->id, 'leader_student_id' => $leader->id, 'creation_token' => (string) Str::uuid(), 'name' => 'Group '.Str::random(8), 'adviser_id' => $adviser?->id, 'created_by' => $facilitator->id, 'status' => 'active']);
        $this->addMember($group, $facilitator, $leader);

        return [$group, $facilitator, $leader, $class];
    }

    private function addMember(ResearchClassGroup $group, User $facilitator, ?User $student = null): User
    {
        $student ??= $this->student();
        $enrollment = ResearchClassEnrollment::query()->create(['research_class_id' => $group->research_class_id, 'student_id' => $student->id, 'status' => 'active', 'requested_at' => now(), 'joined_at' => now(), 'reviewed_by' => $facilitator->id, 'reviewed_at' => now()]);
        ResearchClassGroupMember::query()->create(['research_class_group_id' => $group->id, 'research_class_id' => $group->research_class_id, 'research_class_enrollment_id' => $enrollment->id, 'student_id' => $student->id, 'assigned_by' => $facilitator->id]);

        return $student;
    }

    private function document(User $uploader, ResearchClassGroup $group, string $name, DocumentStage $stage = DocumentStage::ProposalDefense, DocumentStatus $status = DocumentStatus::Pending, bool $current = true, int $version = 1, $submittedAt = null, string $type = 'pdf'): Document
    {
        return Document::query()->create(['user_id' => $uploader->id, 'research_class_group_id' => $group->id, 'submission_token' => (string) Str::uuid(), 'original_filename' => $name, 'stored_filename' => Str::uuid().'.'.$type, 'file_type' => $type, 'mime_type' => $type === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'document_stage' => $stage, 'version_number' => $version, 'is_current' => $current, 'file_size' => 1024, 'storage_disk' => 'local', 'storage_path' => 'documents/'.Str::uuid().'.'.$type, 'content_sha256' => hash('sha256', $name), 'submitted_at' => $submittedAt ?? now(), 'status' => $status]);
    }

    private function student(): User
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        return $user;
    }

    private function adviser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('thesis-adviser');

        return $user;
    }

    private function facilitator(): User
    {
        $user = User::factory()->create();
        $user->assignRole('research-facilitator');

        return $user;
    }
}
